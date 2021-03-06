<?php

namespace App\Models;

use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes, HasDocuments;

    protected $fillable = ['name', 'order', 'description', 'date', 'date_end', 'estimated_time', 'tasks_bundle_id', 'workarea_id', 'created_by', 'status', 'user_id'];

    protected $appends = ['project_id', 'time_spent'];

    public function getTimeSpentAttribute()
    {
        return TaskTimeSpent::where('task_id', $this->id)->sum('duration');
    }

    public function taskTimeSpent()
    {
        return $this->hasMany(TaskTimeSpent::class, 'task_id')->orderBy('date');
    }

    public function periods()
    {
        return $this->hasMany('App\Models\TaskPeriod')->orderBy('start_time');
    }

    public function workarea()
    {
        return $this->belongsTo('App\Models\Workarea', 'workarea_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function skills()
    {
        return $this->belongsToMany('App\Models\Skill', 'tasks_skills', 'task_id', 'skill_id');
    }
    
    public function supplies()
    {
        return $this->hasMany('App\Models\TasksSupply');
    }
    
    public function comments()
    {
        return $this->hasMany('App\Models\TaskComment')->orderBy('created_at', 'DESC');
    }

    public function previousTasks()
    {
        return $this->hasMany('App\Models\PreviousTask', 'task_id');
    }

    public function project()
    {
        return $this->hasOneThrough('App\Models\Project', 'App\Models\TasksBundle', 'id', 'id', 'tasks_bundle_id', 'project_id');
    }

    public function getProjectIdAttribute()
    {
        return $this->hasOneThrough('App\Models\Project', 'App\Models\TasksBundle', 'id', 'id', 'tasks_bundle_id', 'project_id')->first()->id;
    }
}
