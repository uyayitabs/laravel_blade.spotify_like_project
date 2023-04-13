<?php

/**
 * Created by NiNaCoder.
 * Date: 2019-05-24
 * Time: 13:24
 */

namespace App\Models;

use App\Scopes\ApprovedScope;
use App\Scopes\PublishedScope;
use App\Scopes\VisibilityScope;
use App\Traits\SanitizedRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Facades\URL;
use DB;
use Auth;
use Module;

class Song extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SanitizedRequest;

    protected $casts = [
        'released_at' => 'datetime:m/d/Y',
    ];

    protected $table = 'songs';

    protected $fillable = [
        'title',
        'genre',
        'mood',
        'album_id',
        'artworkId',
        'releasedOn',
        'copyright',
        'approve',
        'isrccode',
    ];

    protected $appends = [
        'artwork_url',
        'artists',
        'permalink_url',
        'stream_url',
        'favorite',
        'library',
        'streamable',
        'allow_download',
        'allow_high_quality_download'
    ];

    protected $hidden = [
        'media',
        'artistIds',
        'description',
        'user_id',
        'user'
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new VisibilityScope());
        static::addGlobalScope(new ApprovedScope());
        static::addGlobalScope(new PublishedScope());
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('sm')
            ->width(60)
            ->height(60)
            ->performOnCollections('artwork')->nonOptimized()->nonQueued();

        $this->addMediaConversion('md')
            ->width(120)
            ->height(120)
            ->performOnCollections('artwork')->nonOptimized()->nonQueued();

        $this->addMediaConversion('lg')
            ->width(300)
            ->height(300)
            ->performOnCollections('artwork')->nonOptimized()->nonQueued();
    }

    public function getArtworkUrlAttribute($value)
    {
        $media = $this->getFirstMedia('artwork');
        if (!$media) {
            if (isset($this->log) && isset($this->log->artwork_url)) {
                return $this->log->artwork_url;
            } elseif ($this->album) {
                return $this->album->artwork_url;
            } else {
                return asset('common/default/song.png');
            }
        } else {
            if ($media->disk == 's3') {
                return $media->getTemporaryUrl(Carbon::now()->addMinutes(intval(config('settings.s3_signed_time', 5))), 'lg');
            } else {
                return $media->getFullUrl('lg');
            }
        }
    }

    public function getArtistsAttribute()
    {
        $idsArray = array_filter(explode(',', $this->attributes['artistIds']));
        $ids = implode(',', $idsArray);

        return $this->attributes['artistIds'] ? Artist::whereIn('id', explode(',', $this->attributes['artistIds']))->orderBy(DB::raw('FIELD(id, ' .  $ids . ')', 'FIELD'))->get() : array();
    }

    public function getComposersAttribute()
    {
        $idsArray = array_filter(explode(',', $this->attributes['composerIds']));
        $ids = implode(',', $idsArray);

        return $this->attributes['composerIds'] ? Artist::whereIn('id', explode(',', $this->attributes['composerIds']))->orderBy(DB::raw('FIELD(id, ' .  $ids . ')', 'FIELD'))->get() : array();
    }

    public function getLyricistsAttribute()
    {
        $idsArray = array_filter(explode(',', $this->attributes['lyricistIds']));
        $ids = implode(',', $idsArray);

        return $this->attributes['lyricistIds'] ? Lyricist::whereIn('id', explode(',', $this->attributes['lyricistIds']))->orderBy(DB::raw('FIELD(id, ' .  $ids . ')', 'FIELD'))->get() : array();
    }

    public function getMoodsAttribute()
    {
        $this->attributes['moods'] = Mood::whereIn('id', explode(',', $this->attributes['mood']))->limit(4)->get();
        return $this->attributes['moods'];
    }

    public function getGenresAttribute($value)
    {
        $this->attributes['genres'] = Genre::whereIn('id', explode(',', $this->attributes['genre']))->limit(4)->get();
        return $this->attributes['genres'];
    }

    public function getMinutesAttribute($value)
    {

        $this->attributes['minutes'] = date('i:s', $this->attributes['duration']);

        return $this->attributes['minutes'];
    }

    public function getPermalinkUrlAttribute($value)
    {
        $songSlug = str_slug(html_entity_decode($this->attributes['title'])) ? str_slug(html_entity_decode($this->attributes['title'])) : str_replace(' ', '-', html_entity_decode($this->attributes['title']));
        return route(
            'frontend.song',
            [
                'id' => $this->attributes['id'],
                'slug' => $songSlug
            ]
        );
    }

    public function getTwitterPermalinkUrlAttribute($value)
    {
        $songSlug = str_slug(html_entity_decode($this->attributes['title'])) ? str_slug(html_entity_decode($this->attributes['title'])) : str_replace(' ', '-', html_entity_decode($this->attributes['title']));
        return route(
            'frontend.song',
            [
                'id' => $this->attributes['id'],
                'slug' => $songSlug,
                'to' => 'twitter',
                'by' => auth()->user()->id ?? null
            ]
        );
    }

    public function getFacebookPermalinkUrlAttribute($value)
    {
        $songSlug = str_slug(html_entity_decode($this->attributes['title'])) ? str_slug(html_entity_decode($this->attributes['title'])) : str_replace(' ', '-', html_entity_decode($this->attributes['title']));
        return route(
            'frontend.song',
            [
                'id' => $this->attributes['id'],
                'slug' => $songSlug,
                'to' => 'facebook',
                'by' => auth()->user()->id ?? null
            ]
        );
    }

    public function getAllowDownloadAttribute($value)
    {
        if (auth()->check() && auth()->user()->subscription) {
            if (isset($this->attributes['access'])) {
                $options = groupPermission($this->attributes['access']);
                if ($this->attributes['access'] && isset($options[Role::groupId()])) {
                    $permission = $options[Role::groupId()];
                    switch ($permission) {
                        case 1:
                            return false;
                            break;
                        case 2:
                            return true;
                            break;
                        case 3:
                            return false;
                            break;
                    }
                }
            }
        }

        if (Role::getValue('option_download')) {
            return true;
        } else {
            return false;
        }
    }

    public function getAllowHighQualityDownloadAttribute($value)
    {
        if (auth()->check() && auth()->user()->subscription) {
            if (isset($this->attributes['access'])) {
                $options = groupPermission($this->attributes['access']);
                if ($this->attributes['access'] && isset($options[Role::groupId()])) {
                    $permission = $options[Role::groupId()];
                    switch ($permission) {
                        case 1:
                            return false;
                            break;
                        case 2:
                            return true;
                            break;
                        case 3:
                            return false;
                            break;
                    }
                }
            }
        }

        if (Role::getValue('option_download_hd')) {
            return true;
        } else {
            return false;
        }
    }

    public function getStreamAbleAttribute($value)
    {
        if (isset($this->attributes['access'])) {
            $options = groupPermission($this->attributes['access']);
            if ($this->attributes['access'] && isset($options[Role::groupId()])) {
                $permission = $options[Role::groupId()];
                switch ($permission) {
                    case 1:
                    case 2:
                        return true;
                        break;
                    case 3:
                        return false;
                        break;
                }
            }
        }

        if (isset($this->album) && $this->album->selling && $this->album->purchased) {
            return boolval($this->album->purchased);
        }

        if (isset($this->album) && $this->album->selling && ! $this->album->purchased) {
            return false;
        }

        if ($this->selling) {
            if (Role::getValue('option_play_without_purchased')) {
                return true;
            }

            if ($this->purchased) {
                return true;
            } else {
                return false;
            }
        } else {
            if (isset($this->attributes['access'])) {
                $options = groupPermission($this->attributes['access']);
                if ($this->attributes['access'] && isset($options[Role::groupId()])) {
                    $permission = $options[Role::groupId()];
                    switch ($permission) {
                        case 1:
                            return true;
                            break;
                        case 3:
                            return false;
                            break;
                    }
                }
            }

            return Role::getValue('option_stream') ? true : false;
        }
    }

    private function giveStreamUrl()
    {
        if (isset($this->attributes['hls']) && $this->attributes['hls']) {
            return route('frontend.song.stream.hls', ['id' => $this->attributes['id']]);
        } else {
            return URL::temporarySignedRoute('frontend.song.stream.mp3', now()->addDay(), [
                'id' => $this->attributes['id']
            ]);
        }
    }

    public function getStreamUrlAttribute($value)
    {
        if ($this->stream_able) {
            return $this->giveStreamUrl();
        } else {
            if (isset($this->attributes['preview']) && $this->attributes['preview']) {
                return $this->getFirstMediaUrl('preview');
            } else {
                return false;
            }
        }
    }

    public function getFavoriteAttribute($value)
    {
        if (auth()->check()) {
            return Love::whereRaw('user_id = ?', [auth()->user()->id])
                ->whereRaw('loveable_id = ?', [$this->id])
                ->whereRaw('loveable_type = ?', [$this->getMorphClass()])
                ->exists();
        } else {
            return false;
        }
    }

    public function getPurchasedAttribute($value)
    {
        if (auth()->check() && $this->selling) {
            if (Role::getValue('option_play_without_purchased')) {
                return true;
            }
            return Order::whereRaw('user_id = ?', [auth()->user()->id])
                ->whereRaw('orderable_id = ?', [$this->id])
                ->whereRaw('orderable_type = ?', [$this->getMorphClass()])
                ->exists();
        } else {
            return false;
        }
    }

    public function getLibraryAttribute($value)
    {
        if (auth()->check()) {
            return Collection::whereRaw('user_id = ?', [auth()->user()->id])
                ->whereRaw('collectionable_id = ?', [$this->id])
                ->whereRaw('collectionable_type = ?', [$this->getMorphClass()])
                ->exists();
        } else {
            return false;
        }
    }

    public function getAlbumAttribute()
    {
        return Album::withoutGlobalScopes()->leftJoin('album_songs', 'album_songs.album_id', '=', 'albums.id')
            ->select('albums.*', 'album_songs.id AS host_id')
            ->whereRaw('album_songs.song_id = ?', [$this->id])
            ->first();
    }

    public function getSalesAttribute()
    {
        return Order::groupBy('amount')
            ->whereRaw('orderable_type = ?', [$this->getMorphClass()])
            ->whereRaw('orderable_id = ?', [$this->id])
            ->count();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function log()
    {
        return $this->hasOne(SongLog::class);
    }

    public function tags()
    {
        return $this->hasMany(SongTag::class);
    }

    public function lyric()
    {
        return $this->hasOne(Lyric::class);
    }

    public function similar()
    {
        return Song::whereRaw('id != ?', [$this->id])
            ->whereIn('genre', explode(',', $this->genre));
    }

    public function delete()
    {
        DB::table('playlist_songs')->whereRaw('song_id = ?', [$this->id])->delete();

        DB::table('album_songs')->whereRaw('song_id = ?', [$this->id])->delete();

        Comment::whereRaw('commentable_type = ?', [$this->getMorphClass()])
            ->whereRaw('commentable_id = ?', [$this->id])
            ->delete();

        Love::whereRaw('loveable_type = ?', [$this->getMorphClass()])
            ->whereRaw('loveable_id = ?', [$this->id])
            ->delete();

        Notification::whereRaw('notificationable_type = ?', [$this->getMorphClass()])
            ->whereRaw('notificationable_id = ?', [$this->id])
            ->delete();

        Activity::whereRaw('activityable_type = ?', [$this->getMorphClass()])
            ->whereRaw('activityable_id = ?', [$this->id])
            ->delete();

        Report::whereRaw('reportable_type = ?', [$this->getMorphClass()])
            ->whereRaw('reportable_id = ?', [$this->id])
            ->delete();

        SongLog::whereRaw('song_id = ?', [$this->id])->delete();

        SongTag::whereRaw('song_id = ?', [$this->id])->delete();

        Popular::whereRaw('song_id = ?', [$this->id])->delete();

        Lyric::whereRaw('song_id = ?', [$this->id])->delete();

        return parent::delete();
    }
}
