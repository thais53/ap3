<?php

namespace App\Http\Controllers\API;

use App\User;
use App\Models\Company;
use App\Models\CompanyDetails;
use App\Models\Package;
use App\Models\Subscription;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class SubscriptionController extends BaseApiController
{
    protected static $index_load = ['company:companies.id,name', 'packages:packages.id,name,display_name'];
    protected static $index_append = null;
    protected static $show_load = ['company:companies.id,name', 'packages:packages.id,name,display_name'];
    protected static $show_append = null;

    protected static $store_validation_array = [
        'starts_at' => 'required',
        'ends_at' => 'required',
        'packages' => 'required',
        'is_trial' => 'required'
    ];

    protected static $update_validation_array = [
        'starts_at' => 'required',
        'ends_at' => 'required',
        'packages' => 'required',
        'is_trial' => 'required'
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(Subscription::class);
    }

    protected function filterIndexQuery(Request $request, $query)
    {
        if ($request->has('company_id')) {
            $item = Company::find($request->company_id);
            if (!$item) {
                throw new Exception("Paramètre 'company_id' n'est pas valide.");
            }

            $query->whereIn('id', $item->subscriptions->pluck('id'));
        }

        if (!$request->has('order_by')) {
            $query->orderByRaw("FIELD(state , 'active', 'pending', 'cancelled', 'inactive') ASC")->orderBy('ends_at', 'desc')->orderBy('starts_at', 'desc');
        }
    }

    /**
     * Display a listing of the package resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function packages()
    {
        if ($error = $this->permissionErrors('read')) {
            return $error;
        }

        $items = Package::select("id", "name", "display_name")->get();

        return $this->successResponse($items);
    }

    protected function storeItem(array $arrayRequest)
    {
        $item = new Subscription;
        $item->company_id = $arrayRequest['company_id'];
        return $this->createOrUpdateSubscription($arrayRequest, $item);
    }

    protected function updateItem($item, array $arrayRequest)
    {
        return $this->createOrUpdateSubscription($arrayRequest, $item);
    }

    /**
     * Creates or updates a subscription with new values
     */
    private function createOrUpdateSubscription(array $subscriptionArray, Subscription $item)
    {
        $isNewSubscription=true;
        if($item['starts_at'] && $item['ends_at']){
            $isNewSubscription=false;
        }
        try {
            $item->starts_at = Carbon::createFromFormat('Y-m-d H:i:s', $subscriptionArray['starts_at'] . ' 00:00:00');
        } catch (\Throwable $th) {
            throw new Exception("Paramètre 'subscription.starts_at' doit être du format 'd/m/Y'.");
        }
        try {
            $item->ends_at = Carbon::createFromFormat('Y-m-d H:i:s', $subscriptionArray['ends_at'] . ' 23:59:59');
        } catch (\Throwable $th) {
            throw new Exception("Paramètre 'subscription.ends_at' doit être du format 'd/m/Y'.");
        }

        $conflicts = Subscription::where('company_id', $item->company_id);
        if ($item->exists) {
            $conflicts = $conflicts->where('id', '!=', $item->id);
        }
        $conflicts = $conflicts->where(function ($query) use ($item) {
            $query->whereBetween('starts_at', [$item->starts_at, $item->ends_at])
                ->orWhereBetween('ends_at', [$item->starts_at, $item->ends_at])
                ->orWhere(function ($query) use ($item) {
                    $query->where('starts_at', '<', $item->starts_at)
                        ->where('ends_at', '>', $item->starts_at);
                })
                ->orWhere(function ($query) use ($item) {
                    $query->where('starts_at', '<', $item->ends_at)
                        ->where('ends_at', '>', $item->ends_at);
                });
        });
        if ($conflicts->exists()) {
            throw new Exception("Impossible d'avoir deux abonnements sur une même période");
        }

        $item->is_trial = $subscriptionArray['is_trial'];
        $item->save();

        try {
            $item->packages()->sync($subscriptionArray['packages']);
        } catch (\Throwable $th) {
            throw new Exception("Paramètre 'subscription.packages' contient des valeurs invalides.");
        }

        //si l'abonnement était à cancelled et passe à active, pending ou inactive -> email pour informer abonnement actif
        if($item->state == 'cancelled'){
            if (isset($subscriptionArray['is_cancelled'])) {
                if (!$subscriptionArray['is_cancelled']) {
                    //send email
                    $company_id=$subscriptionArray['company_id'];
                    $company=Company::where('id',$company_id)->get();
                    $companyDetails=$company[0]->details;
                    $adminCompany = new User();
                    $adminCompany->email = $companyDetails['contact_email'];
                    $adminFirstname = $companyDetails['contact_firstname'];
                    $adminLastname = $companyDetails['contact_lastname'];
                    $adminCompany->sendEmailActiveSubscriptionNotification($adminFirstname, $adminLastname,$item['starts_at'], $item['ends_at']);
                }
            }
            
        }

        //statut annulé par défaut à la création
        if($isNewSubscription){
            $item->state = 'cancelled';
        }
        else{
            if ($item->starts_at->isFuture()) {
                $item->state = 'pending';
            } else if ($item->ends_at->isFuture()) {
                $item->state = 'active';
            } else {
                $item->state = 'inactive';
            }
        }
        
        if (isset($subscriptionArray['is_cancelled'])) {
            if ($subscriptionArray['is_cancelled']) {
                $item->state = 'cancelled';
            }
        }

        $item->save();

        return $item;
    }
}
