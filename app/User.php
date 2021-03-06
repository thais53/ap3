<?php

namespace App;

use App\Models\Company;
use App\Models\ModelHasOldId;
use App\Models\Role;
use App\Traits\HasCompany;
use App\Models\DealingHours;
use App\Models\Skill;
use App\Models\Task;
use App\Models\Unavailability;
use App\Models\WorkHours;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasCompany, HasRoles, HasApiTokens, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname', 'lastname', 'function', 'login', 'email', 'password', 'is_password_change', 'isTermsConditionAccepted', 'company_id', 'register_token','start_employment'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'register_token', 'roles'
    ];

    /**
     * The attibutes to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['role', 'is_admin', 'is_manager'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function workHours()
    {
        return $this->hasMany(WorkHours::class);
    }

    public function unavailabilities()
    {
        return $this->hasMany(Unavailability::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'users_skills', 'user_id', 'skill_id');
    }

    public function getModuleAttribute()
    {
        return $this->company && $this->company->module ? $this->company->module->load('module_data_types:module_data_types.id,data_type_id', 'module_data_types.data_type:data_types.id,slug') : null;
    }

    public function getClearPasswordAttribute()
    {
        return $this->clear_password;
    }

    public function setClearPasswordAttribute(string $password)
    {
        $this->clear_password = $password;
    }

    public function getRoleAttribute()
    {
        return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id', 'role_id')->where('model_type', User::class)->first();
    }

    public function getPermissionsAttribute()
    {
        if ($role = $this->role) {
            $permissions = $role->permissions;


            if (!$this->is_admin && ($company = Company::find($this->company_id))) {
                if ($company->has_active_subscription) {
                    $active_permissions = $company->active_subscription->permissions->pluck('name')->toArray();
                    $permissions = $permissions->whereIn('name', $active_permissions);
                }
            }


            return $permissions->flatten();
        }

        return [];
    }

    public function getIsAdminAttribute()
    {
        if ($role = $this->role) {
            return $role->is_admin;
        }

        return false;
    }

    public function getIsManagerAttribute()
    {
        if ($role = $this->role) {
            return $role->is_manager;
        }

        return false;
    }

    public function getRelatedUsersAttribute()
    {
        if (
            $this->company
            && $this->company->module
            && $this->company->module->hasModuleDataTypeForSlug('users')
            && !ModelHasOldId::where('model', User::class)->where('new_id', $this->id)->exists()
        ) {
            $relatedUsers = User::whereIn('id', ModelHasOldId::where('model', User::class)
                ->pluck('new_id')
                ->filter(function ($id) {
                    return $id !== $this->id;
                }))->get(['id', 'firstname', 'lastname', 'login']);
            return $relatedUsers->filter(function ($user) {
                similar_text($this->firstname, $user->firstname, $firstnamePercentage);
                similar_text($this->lastname, $user->lastname, $lastnamePercentage);
                return $firstnamePercentage + $lastnamePercentage >= 100;
            });
        }
        return [];
    }

    public function getHoursAttribute(){
        $date='2001-01-01';
        $findDealinHoursHeuresSupp = DealingHours::where('date', $date)->where('user_id', $this->id)->first();
        return $findDealinHoursHeuresSupp ? $findDealinHoursHeuresSupp->overtimes : 0;
    }

    public function hasPermissionTo($perm)
    {
        return $this->permissions->pluck('name')->contains($perm);
    }

    /**
     * Override the mail body for reset password notification mail.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\MailResetPasswordNotification($token));
    }
    /**
     * Override the mail body for verify email notification mail.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\MailVerifyEmailNotification());
    }
    /**
     * Override the mail body for add user email notification mail.
     */
    public function sendEmailAddUserNotification($id, $register_token)
    {
        $this->notify(new \App\Notifications\MailAddUserNotification($id, $register_token));
    }
    /**
     * Override the mail body for new user email notification mail.
     */
    public function sendEmailNewUserNotification($firstname, $lastname, $email, $company_name, $contact_tel1)
    {
        $this->notify(new \App\Notifications\MailNewUserNotification($firstname, $lastname, $email, $company_name, $contact_tel1));
    }
    /**
     * Override the mail body for registration link notification mail.
     */
    public function sendEmailRegistrationLinkNotification($mails, $companyName)
    {
        $this->notify(new \App\Notifications\MailRegistrationLinkNotification($mails, $companyName));
    }
    /**
     * Override the mail body for new bug notification mail.
     */
    public function sendEmailNewBugNotification($module,$type,$description,$user_firstname,$user_lastname,$company_name)
    {
        $this->notify(new \App\Notifications\MailNewBugNotification($module,$type,$description,$user_firstname,$user_lastname,$company_name));
    }
    /**
     * Override the mail body for active subscription notification mail.
     */
    public function sendEmailActiveSubscriptionNotification($admin_firstname, $admin_lastname, $start_date_subscription, $end_date_subscription)
    {
        $this->notify(new \App\Notifications\MailActiveSubscriptionNotification($admin_firstname, $admin_lastname, $start_date_subscription, $end_date_subscription));
    }
}
