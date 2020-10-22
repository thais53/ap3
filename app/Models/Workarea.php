<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workarea extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'max_users', 'company_id'];

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id', 'id')->withTrashed();
    }

    public function skills()
    {
        return $this->belongsToMany('App\Models\Skill', 'workareas_skills', 'workarea_id')->withTrashed();
    }

    public function documents()
    {
        return $this->belongsToMany(Document::class, ModelHasDocuments::class, 'model_id', 'document_id')->where('model', Workarea::class);
    }

    public function forceDeleteCascade()
    {
        foreach ($this->documents as $doc) {
            if ($doc->models()->count() == 1) {
                $doc->deleteFile();
            }
        }
        $this->forceDelete();
    }
}
