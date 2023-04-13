<?php

/**
 * Created by NiNaCoder.
 * Date: 2019-05-24
 * Time: 13:24
 */

namespace App\Models;

use App\Scopes\LatestScope;
use App\Traits\SanitizedRequest;
use Illuminate\Database\Eloquent\Model;
use DB;
use Auth;

class Report extends Model
{
    use SanitizedRequest;

    protected $table = 'reports';

    protected $fillable = [
        'user_id',
        'reportable_id',
        'reportable_type',
        'message'
    ];
    const REPORTABLE_CLASSES = [
        'App\\Models\\Song',
        'App\\Models\\Podcast',
        'App\\Models\\Episode',
        'App\\Models\\Comment',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new LatestScope());
    }

    public function getObjectAttribute()
    {
        if (in_array($this->reportable_type, self::REPORTABLE_CLASSES)) {
            return $this->reportable_type::withoutGlobalScopes()->find($this->reportable_id);
        }
        return [];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function objectCount()
    {
        $count = 0;
        foreach (Report::whereIn('reportable_type', Report::REPORTABLE_CLASSES)->get() as $report) {
            if ($report->object) {
                $count++;
            }
        }
        return $count;
    }
}