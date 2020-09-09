<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class BaseModule extends Model
{
    protected $fillable = ['name', 'modulable_id', 'modulable_type', 'last_synced_at', 'is_active', 'company_id'];
    protected $appends = ['type'];

    public function getTypeAttribute()
    {
        return $this->modulable_type === SqlModule::class ? "sql" : "api";
    }

    public function modulable()
    {
        return $this->morphTo();
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id', 'id')->withTrashed();
    }

    public function moduleDataTypes()
    {
        return $this->hasMany(ModuleDataType::class, 'module_id', 'id');
    }

    public function sortedModuleDataTypes()
    {
        return $this->moduleDataTypes->load('dataType', 'moduleDataRows')->sortBy(function ($mdt) {
            return $mdt->dataType->order;
        });
    }

    public function sync()
    {
        try {
            DB::beginTransaction();
            foreach ($this->modulable->getData() as $tableName => $tableData) {
                $dataType = DataType::where('slug', $tableName)->firstOrFail();
                $table = app($dataType->model);
                foreach ($tableData as $rowData) {
                    $data = array_filter(get_object_vars($rowData), function ($key) {
                        return $key !== "id";
                    }, ARRAY_FILTER_USE_KEY);
                    $oldId = ModelHasOldId::firstOrNew(['old_id' => $rowData->id, 'model' => $dataType->model]);
                    if ($oldId->new_id) {
                        $table->find($oldId->new_id)->update($data);
                    } else {
                        $oldId->new_id = $table->create($data)->id;
                        $oldId->save();
                    }
                }
            }
            DB::commit();
            $this->last_synced_at = Carbon::now();
            $this->save();
            return true;
        } catch (\Throwable $th) {
            echo $th->getMessage();
            DB::rollBack();
            $controllerLog = new Logger('BaseModule');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('BaseModule', [$th->getMessage()]);
            return false;
        }
    }
}