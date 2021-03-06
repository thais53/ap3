<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Models\DealingHours;
use App\Models\Unavailability;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class DealingHoursController extends Controller
{
    use SoftDeletes;

    public static $successStatus = 200;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $items = [];
        if ($user->is_admin) {
            $items = DealingHours::all();
        } else {
            $items = DealingHours::where('user_id', $user->id)->get();
        }

        return response()->json(['success' => $items], $this->successStatus);
    }

    /**
     * Display a listing of resource with specific year.
     *
     * @return \Illuminate\Http\Response
     */
    public static function getOvertimes($user_id=null, $check = false)
    {
        $user = Auth::user();
        if($user_id != "null"){
            $id=$user_id;
        }
        else{
            $id=$user->id;
        }

        // Check if overtimes
        $items = DealingHours::where('user_id', $id)->get();
        $totalOvertimes = 0;
        $nbUsedOvertimes = 0;
        $nbPayedOvertimes = 0;
        
        if (!$items->isEmpty()) {
            // Get total overtimes
            $user = User::where('id', $id)->get();
            $start_employment = Carbon::parse($user[0]->start_employment);
            if($user[0]->start_employment == null)
            {
                $totalOvertimes = DealingHours::where('user_id', $id)->where('date', '<', Carbon::now()->startOfWeek())->sum('overtimes');                      
            }
            else
            { 
                // $users = User::where('id', $id)->whereBetween('start_employment', [Carbon::now()->startOfWeek()->format('Y-m-d H:i:s'), Carbon::now()->endOfWeek()->format('Y-m-d H:i:s')])->get();
                $totalOvertimes = DealingHours::where('user_id', $id)->where('date', '>=', $start_employment->format('Y-m-d H:i:s'))->where('date', '<', Carbon::now()->startOfWeek())->orWhere('date', '=', '2001-01-01')->where('user_id', $id)->sum('overtimes');
            }

            // Get nb used and payed overtimes

            $usedOrvertimes = Unavailability::where([['user_id', $id], ['reason', 'Utilisation heures suppl??mentaires']])->get();
            $usedOvertimesPayed = Unavailability::where([['user_id', $id], ['reason', 'Heures suppl??mentaires pay??es']])->get();
            if (!$usedOrvertimes->isEmpty()) {
                foreach ($usedOrvertimes as $key => $used) {
                    $parseStartAt = Carbon::createFromFormat('Y-m-d H:i:s', $used->ends_at)->format('H:i');
                    $parseEndsAt = Carbon::createFromFormat('Y-m-d H:i:s', $used->starts_at)->format('H:i');
                    $hours = Carbon::create($parseEndsAt)->floatDiffInHours(Carbon::create($parseStartAt));
                    $nbUsedOvertimes += $hours;
                    //$nbUsedOvertimes += (floatval(explode(':', $parseStartAt)[0]) - floatval(explode(':', $parseEndsAt)[0]));
                }
            }
            if (!$usedOvertimesPayed->isEmpty()) {
                foreach ($usedOvertimesPayed as $key => $used) {
                    $parseStartAt = Carbon::createFromFormat('Y-m-d H:i:s', $used->ends_at)->format('H:i');
                    $parseEndsAt = Carbon::createFromFormat('Y-m-d H:i:s', $used->starts_at)->format('H:i');
                    $hours = Carbon::create($parseEndsAt)->floatDiffInHours(Carbon::create($parseStartAt));
                    $nbPayedOvertimes += $hours;
                    //$nbPayedOvertimes += (floatval(explode(':', $parseStartAt)[0]) - floatval(explode(':', $parseEndsAt)[0]));
                }
            }
            $result = array(
                "overtimes" => $totalOvertimes,
                "usedOvertimes" => $nbUsedOvertimes,
                "payedOvertimes" => $nbPayedOvertimes
            );
        } else {
            $result = array(
                "overtimes" => $totalOvertimes,
                "usedOvertimes" => $nbUsedOvertimes,
                "payedOvertimes" => $nbPayedOvertimes
            );
        }
        if ($check === true) {
            return $result;
        } else {
            return response()->json(['success' => $result], self::$successStatus);
        }
    }

    /**
     * Show the specified resource.
     *
     * @param  \App\Models\DealingHours $dealing_hours
     * @return \Illuminate\Http\Response
     */
    public function show(DealingHours $dealing_hours)
    {
        return response()->json(['success' => $dealing_hours], self::$successStatus);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'user_id' => 'required',
            'date' => 'required',
            'overtimes' => 'required',
            'used_hours' => 'required',
            'used_type' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $item = DealingHours::create($arrayRequest);
        return response()->json(['success' => $item], $this->successStatus);
    }

    /**
     * Update or Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeOrUpdateUsed(Request $request)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'date' => 'required',
            'used_hours' => 'required',
            'used_type' => 'required',
            'overtimes_to_use' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        // Parse used_hours from time to double
        $parts = explode(':', $arrayRequest['used_hours']);
        $arrayRequest['used_hours'] = $parts[0] + $parts[1] / 60 * 100 / 100;

        // Expected hours for this day
        $workDuration = HoursController::getTargetWorkHours($arrayRequest['user_id'], $arrayRequest['date']);
        if ($workDuration === 0) {
            setlocale(LC_TIME, "fr_FR", "French");
            $target_day = strftime("%A", strtotime($arrayRequest['date']));
            return response()->json(['error' => "V??rifier que l'utilisateur ai bien renseign?? des horraires de travail pour le " + $target_day], 401);
        }

        // Check have nb overtimes
        if ($arrayRequest['used_hours'] <= $arrayRequest['overtimes_to_use']) {
            // Check used_hours
            if ($arrayRequest['used_hours'] > $workDuration) {
                return response()->json(['error' => "Vous ne pouvez pas r??cup??rer plus de $workDuration heures par jour."]);
            }

            // Check if dealing_hour have value for this date
            $item = DealingHours::where('date', $arrayRequest['date'])->first();
            if ($item !== null) {
                //Yes -> update dealing hour
                //check if already overtime
                if ($item->overtimes < 0) {
                    // check if have nb hours to use
                    if (($item->overtimes + $arrayRequest['used_hours']) <= 0) {
                        // Check if same type
                        if ($item->used_type === $arrayRequest['used_type']) {
                            // Check if after update used_hour < workDuration
                            if (($item->used_hours + $arrayRequest['used_hours']) <= $workDuration) {
                                $item = DealingHours::where('id', $item->id)->update(['used_hours' => $arrayRequest['used_hours'], 'used_type' => $arrayRequest['used_type']]);
                                return response()->json(['success' => [$item, "update"]], $this->successStatus);
                            } else {
                                return response()->json(['error' => "Vous avez d??j?? r??cup??r??(e) $item->used_hours heures le $item->date, vous ne pouvez r??cup??rer que 7 heures pour ce jour."]);
                            }
                        } else {
                            return response()->json(['error' => "Vous avez d??j?? des heures $item->used_type pour le $item->date, veuillez utiliser vos heures suppl??mentaires de la m??me mani??re pour ce jour."]);
                        }
                    } else {
                        $max_hours = $item->overtimes * -1;
                        return response()->json(['error' => "Vous ne pouvez poser plus que $max_hours heures pour cette journ??e"]);
                    }
                } else {
                    return response()->json(['error' => "Vous avez d??j?? travaill??(e) pour la journ??e compl??te."]);
                }
            } else {
                //No -> create dealing hour
                $validator = Validator::make($arrayRequest, [
                    'user_id' => 'required',
                    'date' => 'required',
                    'overtimes' => 'required',
                    'used_hours' => 'required',
                    'used_type' => 'required'
                ]);
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()], 401);
                }
                $arrayRequest['overtimes'] += $arrayRequest['used_hours'];
                $item = DealingHours::create($arrayRequest);
                return response()->json(['success' => [$item, "create"]], $this->successStatus);
            }
        } else {
            return response()->json(['error' => "Vous ne disposez pas assez d'heures."]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DealingHours $dealing_hours
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DealingHours $dealing_hours)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'user_id' => 'required',
            'date' => 'required',
            'overtimes' => 'required',
            'used_hours' => 'required',
            'used_type' => 'required'
        ]);

        $dealing_hours->update(['user_id' => $arrayRequest['user_id'], 'date' => $arrayRequest['date'], 'overtimes' => $arrayRequest['overtimes'], 'used_hours' => $arrayRequest['used_hours'], 'used_type' => $arrayRequest['used_type']]);
        return response()->json(['success' => $dealing_hours], $this->successStatus);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DealingHours $dealing_hours
     * @return \Illuminate\Http\Response
     */
    public function destroy(DealingHours $dealing_hours)
    {
        return response()->json(['success' => $dealing_hours->delete()], $this->successStatus);
    }

    /**
     * forceDelete the specified resource from storage.
     *
     * @param  \App\Models\DealingHours $dealing_hours
     * @return \Illuminate\Http\Response
     */
    public function forceDelete(DealingHours $dealing_hours)
    {
        return response()->json(['success' => $dealing_hours->forceDelete()], $this->successStatus);
    }
}
