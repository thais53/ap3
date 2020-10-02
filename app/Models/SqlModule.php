<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use stdClass;

class SqlModule extends BaseModule
{
    protected $fillable = ['driver', 'host', 'port', 'charset', 'database', 'username', 'password'];

    public $timestamps = false;

    public function module()
    {
        return $this->morphOne(BaseModule::class, 'modulable');
    }

    public function connectionData()
    {
        $data = [
            'driver' => $this->driver,
            'host' => $this->host,
            'charset' => $this->charset,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
        ];

        if ($this->driver !== 'sqlite') {
            $data['port'] = $this->port;
        }

        return $data;
    }

    public function getRows(ModuleDataType $mdt)
    {
        $rows = [];
        $lowestUniqueId = 0;
        try {
            Config::set('database.connections.' . $this->module->name, $this->connectionData());
            DB::purge($this->module->name);

            $query = DB::connection($this->module->name)->table($mdt->source);
            $onlyDefaultValueRows = [];
            foreach ($mdt->moduleDataRows as $mdr) {
                if ($mdr->source) {
                    $query->selectRaw($mdr->source . ' AS ' . $mdr->dataRow->field);
                } else {
                    // Set default value directly without getting from source
                    array_push($onlyDefaultValueRows, $mdr);
                }
            }

            foreach ($query->get() as $result) {
                $row = new stdClass();
                if (!in_array($mdt->dataType->slug, ["unavailabilities", "tasks"])) {
                    $row->company_id = $this->module->company_id;
                }

                foreach (get_object_vars($result) as $key => $value) {
                    $dataRow = DataRow::where('data_type_id', $mdt->data_type_id)->where('field', $key)->firstOrFail();
                    $mdr = ModuleDataRow::where('module_data_type_id', $mdt->id)->where('data_row_id', $dataRow->id)->firstOrFail();

                    $table = app($this->moduleDataType->dataType->model);
                    $details = json_decode($this->details);
                    $drDetails = json_decode($this->dataRow->details);

                    $newValue = $value ?? $mdr->default_value;
                    if (
                        $details
                        && $details->only_if_null
                        && $currentModel = $table->find(
                            ModelHasOldId::where('model', $drDetails->model)->where('old_id', $result->id)->firstOr(function () {
                                return new ModelHasOldId(); // Rendra new_id vide
                            })->new_id
                        )
                    ) {
                        $newValue = $currentModel->{$this->dataRow->field};
                    } else {
                        $newValue = $mdr->applyDetailsToValue($newValue, $lowestUniqueId);
                    }
                    $row->{$key} = $newValue;
                }

                foreach ($onlyDefaultValueRows as $mdr) {
                    $row->{$mdr->dataRow->field} = $mdr->default_value;
                }

                array_push($rows, $row);
            }
        } catch (\Throwable $th) {
            $rows = [];
            echo $th->getMessage() . "\n\r";
            $controllerLog = new Logger('SQLModule');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::ERROR);
            $controllerLog->error('SQLModule', [$th->getMessage()]);
        }

        return $rows;
    }
}
