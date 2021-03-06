<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkareasSkill extends Model
{
    public $timestamps = false;

    protected $fillable = ['workarea_id', 'skill_id'];

    protected $casts = [];

    public function workarea()
    {
        return $this->belongsTo('App\Models\Workarea', 'workarea_id');
    }
    public function skill()
    {
        return $this->belongsTo('App\Models\Skill', 'skill_id');
    }
}
