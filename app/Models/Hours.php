<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Hours extends Model
{

    protected $fillable = ['start_at', 'end_at', 'description', 'user_id', 'project_id'];

    protected $appends = ['duration', 'durationInFloatHour'];

    public function getDurationAttribute()
    {
        $start_at = Carbon::parse($this->start_at);
        $end_at = Carbon::parse($this->end_at);

        return gmdate('H:i:s', $start_at->diffInSeconds($end_at));
    }

    public function getDurationInFloatHourAttribute()
    {
        $start_at = Carbon::parse($this->start_at);
        $end_at = Carbon::parse($this->end_at);

        return $start_at->floatDiffInHours($end_at);
    }


    public function project()
    {
        return $this->belongsTo('App\Models\Project', 'project_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}
