<?php

/**
 * Created by NiNaCoder.
 * Date: 2019-05-25
 * Time: 08:47
 */

namespace App\Models;

use App\Scopes\ApprovedScope;
use App\Scopes\PublishedScope;
use App\Scopes\VisibilityScope;
use App\Traits\SanitizedRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use DB;
use Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Module;

class Album extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SanitizedRequest;

    protected $appends = [
        'artwork_url',
        'artists',
        'song_count',
        'favorite',
        'permalink_url',
        'purchased'
    ];

    protected $hidden = [
        'media',
        'user_id',
        'artistIds',
        'approved',
        'updated_at'
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
            } else {
                return asset('common/default/album.png');
            }
        } else {
            if ($media->disk == 's3') {
                return $media->getTemporaryUrl(now()->addMinutes(intval(config('settings.s3_signed_time', 5))), 'lg');
            } else {
                return $media->getFullUrl('lg');
            }
        }
    }

    public function getArtistsAttribute()
    {
        $idsArray = array_filter(explode(',', $this->attributes['artistIds']));
        $ids = implode(',', $idsArray);

        return Artist::whereIn('id', explode(',', $this->attributes['artistIds']))->orderBy(DB::raw('FIELD(id, ' .  $ids . ')', 'FIELD'))->get();
    }

    public function getComposersAttribute()
    {
        $idsArray = array_filter(explode(',', $this->attributes['composerIds']));
        $ids = implode(',', $idsArray);

        return $this->attributes['composerIds'] ? Artist::whereIn('id', explode(',', $this->attributes['composerIds']))->orderBy(DB::raw('FIELD(id, ' .  $ids . ')', 'FIELD'))->get() : array();
    }

    public function getMoodsAttribute()
    {
        return Mood::whereIn('id', explode(',', $this->attributes['mood']))->limit(4)->get();
    }

    public function getGenresAttribute($value)
    {
        return Genre::whereIn('id', explode(',', $this->attributes['genre']))->limit(4)->get();
    }

    public function getPermalinkUrlAttribute($value)
    {
        return route('frontend.album', ['id' => $this->attributes['id'], 'slug' => str_slug(html_entity_decode($this->attributes['title'])) ? str_slug(html_entity_decode($this->attributes['title'])) : str_replace(' ', '-', html_entity_decode($this->attributes['title']))]);
    }

    public function getSongCountAttribute($value)
    {
        return AlbumSong::whereRaw('album_id = ?', [$this->id])->count();
    }

    public function getSalesAttribute()
    {
        return Order::groupBy('amount')
                ->whereRaw('orderable_type = ?', [$this->getMorphClass()])
                ->whereRaw('orderable_id = ?', [$this->id])->count();
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function songs()
    {
        return Song::withoutGlobalScopes()
            ->leftJoin('album_songs', 'album_songs.song_id', '=', (new Song())->getTable() . '.id')
            ->select((new Song())->getTable() . '.*', 'album_songs.id as host_id')
            ->whereRaw('album_songs.album_id = ?', [$this->id])
            ->orderBy('album_songs.priority', 'asc')
            ->orderBy('album_songs.id', 'asc');
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

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function love()
    {
        return $this->morphOne(Love::class, 'loveable')
            ->whereRaw('user_id = ?', [auth()->user()->id]);
    }

    public function log()
    {
        return $this->hasOne(AlbumLog::class);
    }

    private function checkFavorite($album_id)
    {
        if (auth()->check()) {
            $row = DB::table('loves')
                ->select('loves.id')
                ->whereRaw('loves.user_id = ?', [auth()->user()->id])
                ->whereRaw('loves.item_id = ?', [$album_id])
                ->whereRaw('loves.type = ?', [3])
                ->first();

            if ((object) $row && isset($row->id)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function similar()
    {
        return Album::whereRaw('id != ?', [$this->id])
                ->whereIn('genre', explode(',', $this->genre));
    }

    public function delete()
    {
        DB::table('album_songs')
            ->whereRaw('album_id = ?', [$this->id])
            ->delete();

        Comment::whereRaw('commentable_type = ?', [$this->getMorphClass()])
            ->whereRaw('commentable_id = ?', [$this->id])
            ->delete();

        Love::whereRaw('loveable_type = ?', [$this->getMorphClass()])
            ->whereRaw('loveable_id = ?', [$this->id])
            ->delete();

        Activity::whereRaw('activityable_type = ?', [$this->getMorphClass()])
            ->whereRaw('activityable_id = ?', [$this->id])
            ->delete();

        AlbumLog::whereRaw('album_id = ?', [$this->id])->delete();

        AlbumSong::whereRaw('album_id = ?', [$this->id])->delete();

        Popular::whereRaw('album_id = ?', [$this->id])->delete();

        return parent::delete();
    }
}
