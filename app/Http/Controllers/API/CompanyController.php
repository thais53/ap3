<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\WorkareasSkill;
use Carbon\Carbon;
use Exception;
use Validator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class CompanyController extends BaseApiControllerWithSoftDelete
{
    protected static $company_id_field = 'id';
    protected static $index_with = [];
    protected static $show_load = ['skills:id,name,company_id', 'subscriptions', 'subscriptions.packages'];
    protected static $show_append = ['active_subscription'];
    protected static $cascade = true;

    protected static $store_validation_array = [
        'name' => 'required',
        'siret' => 'required',
        'code' => 'required',
        'type' => 'required',
        'contact_firstname' => 'required',
        'contact_lastname' => 'required',
        'contact_function' => 'required',
        'contact_email' => 'required|email',
        'contact_tel1' => 'nullable',
        'contact_tel2' => 'nullable',
        'street_number' => 'required',
        'street_name' => 'required',
        'postal_code' => 'required',
        'city' => 'required',
        'country' => 'required',
        'subscription' => 'required',
    ];

    protected static $update_validation_array = [
        'name' => 'required',
        'siret' => 'required',
        'code' => 'required',
        'type' => 'required',
        'contact_firstname' => 'required',
        'contact_lastname' => 'required',
        'contact_function' => 'required',
        'contact_email' => 'required|email',
        'contact_tel1' => 'nullable',
        'contact_tel2' => 'nullable',
        'street_number' => 'required',
        'street_name' => 'required',
        'postal_code' => 'required',
        'city' => 'required',
        'country' => 'required',
    ];


    /**
     * Create a new controller instance.
     *
     * @param string $class Class name of the primary model
     * @return void
     */
    public function __construct()
    {
        parent::__construct(Company::class);
    }

    public function index(Request $request)
    {
        if ($result = $this->unauthorizedTo('read')) {
            return $result;
        }

        $extra = [];
        $payload = [];

        $user = Auth::user();
        try {
            if ($user->is_admin) {
                $payload = $this->indexItemsQuery($request)
                    ->leftJoin('subscriptions', function ($join) {
                        $join->on('companies.id', '=', 'subscriptions.company_id')
                            ->where('subscriptions.state', 'active');
                    })->groupBy('companies.id');

                if (!$request->has('order_by')) {
                    $payload->orderBy('subscriptions.ends_at', 'desc');
                }

                $payload = $this->paginateQuery($request, $payload, $extra)->get();
            } else {
                $payload = collect([Company::where('id', $user->company_id)->with(static::$index_with)->first()]);
            }
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }

        $payload->each->append(static::$show_append);

        return $this->successResponse($payload, 'Chargement terminé avec succès.', $extra);
    }

    protected function storeItem(array $arrayRequest)
    {
        if (!isset($arrayRequest['contact_tel1']) && !isset($arrayRequest['contact_tel2'])) {
            throw new Exception('Au moins un numéro de téléphone est obligatoire');
        }

        if (Company::withTrashed()->where('siret', $arrayRequest['siret'])->exists()) {
            throw new Exception('Siret déjà utilisé par une autre sociéte');
        }

        $item = Company::create([
            'name' => $arrayRequest['name'],
            'siret' => $arrayRequest['siret'],
            'code' => $arrayRequest['code'],
            'type' => $arrayRequest['type'],
            'contact_firstname' => $arrayRequest['contact_firstname'],
            'contact_lastname' => $arrayRequest['contact_lastname'],
            'contact_function' => $arrayRequest['contact_function'],
            'contact_email' => $arrayRequest['contact_email'],
            'contact_tel1' => $arrayRequest['contact_tel1'],
            'contact_tel2' => $arrayRequest['contact_tel2'],
            'street_number' => $arrayRequest['street_number'],
            'street_name' => $arrayRequest['street_name'],
            'postal_code' => $arrayRequest['postal_code'],
            'city' => $arrayRequest['city'],
            'country' => $arrayRequest['country'],
        ]);

        $subscriptionArray = $arrayRequest['subscription'];
        $validator = Validator::make($subscriptionArray, [
            'starts_at' => 'required',
            'ends_at' => 'required',
            'packages' => 'required',
            'is_trial' => 'required'
        ]);
        if ($validator->fails()) {
            throw new Exception($validator->errors());
        }

        $subscription = Subscription::create([
            'company_id' => $item->id,
            'is_trial' => $subscriptionArray['is_trial']
        ]);

        try {
            $subscription->starts_at = Carbon::createFromFormat('d/m/Y H:i:s', $subscriptionArray['starts_at'] . ' 00:00:00');
        } catch (\Throwable $th) {
            throw new Exception("Paramètre 'subscription.starts_at' doit être du format 'd/m/Y'.");
        }
        try {
            $subscription->ends_at = Carbon::createFromFormat('d/m/Y H:i:s', $subscriptionArray['ends_at'] . ' 23:59:59');
        } catch (\Throwable $th) {
            throw new Exception("Paramètre 'subscription.ends_at' doit être du format 'd/m/Y'.");
        }
        try {
            $subscription->packages()->sync($subscriptionArray['packages']);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
            throw new Exception("Paramètre 'subscription.packages' contient des valeurs invalides.");
        }

        if ($subscription->starts_at->isFuture()) {
            $subscription->state = 'pending';
        } else if ($subscription->ends_at->isFuture()) {
            $subscription->state = 'active';
        } else {
            $subscription->state = 'inactive';
        }

        $subscription->save();

        return $item;
    }

    protected function updateItem($item, array $arrayRequest)
    {
        if (!isset($arrayRequest['contact_tel1']) && !isset($arrayRequest['contact_tel2'])) {
            throw new Exception('Au moins un numéro de téléphone est obligatoire');
        }

        $item->update([
            'name' => $arrayRequest['name'],
            'siret' => $arrayRequest['siret'],
            'code' => $arrayRequest['code'],
            'type' => $arrayRequest['type'],
            'contact_firstname' => $arrayRequest['contact_firstname'],
            'contact_lastname' => $arrayRequest['contact_lastname'],
            'contact_function' => $arrayRequest['contact_function'],
            'contact_email' => $arrayRequest['contact_email'],
            'contact_tel1' => $arrayRequest['contact_tel1'],
            'contact_tel2' => $arrayRequest['contact_tel2'],
            'street_number' => $arrayRequest['street_number'],
            'street_name' => $arrayRequest['street_name'],
            'postal_code' => $arrayRequest['postal_code'],
            'city' => $arrayRequest['city'],
            'country' => $arrayRequest['country'],
        ]);

        return $item;
    }
}
