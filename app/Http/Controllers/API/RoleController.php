<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends BaseApiController
{
    protected static $index_load = ['company:companies.id,name'];
    protected static $index_append = null;
    protected static $show_load = ['company:companies.id,name', 'permissions'];
    protected static $show_append = null;

    protected static $store_validation_array = [
        'name' => 'required',
        'description' => 'nullable',
        // 'company_id' => 'required',
        'is_public' => 'required',
        'permissions' => 'required|array'
    ];

    protected static $update_validation_array = [
        'name' => 'required',
        'description' => 'nullable',
        // 'company_id' => 'required',
        'is_public' => 'required',
        'permissions' => 'required|array'
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(Role::class);
    }

    protected function filterIndexQuery(Request $request, $query)
    {
        $user = Auth::user();
        if (!$user->is_admin) {
            $query->orWhere('is_public', true);
        }
    }

    protected function storeItem(array $arrayRequest)
    {
        $user = Auth::user();

        $item = Role::create([
            'name' => $arrayRequest['name'],
            'description' => $arrayRequest['description'],
            'company_id' => $user->company_id,
            'is_public' => $arrayRequest['is_public'] && $user->is_admin,
            'guard_name' => 'web'
        ]);

        $this->setPermissions($item, $arrayRequest['permissions']);

        return $item;
    }

    protected function updateItem($item, array $arrayRequest)
    {
        $user = Auth::user();

        $item->update([
            'name' => $arrayRequest['name'],
            'description' => $arrayRequest['description'],
            'company_id' => $user->company_id,
            'is_public' => $arrayRequest['is_public'] && $user->is_admin,
            'guard_name' => 'web'
        ]);

        $this->setPermissions($item, $arrayRequest['permissions']);

        return $item;
    }

    /**
     * Sets the permissions of the role.
     */
    protected function setPermissions(Role $item, array $permissionIds)
    {
        $user = Auth::user();

        $ids = collect($permissionIds);
        foreach ($ids as $id) {
            $permission = Permission::find($id);
            if (!$permission) {
                throw new ApiException("Permission '{$id}' inconnue.");
            }
            if (!$user->permissions->contains(function ($perm) use ($id) {
                return $perm->id == $id;
            })) {
                throw new ApiException("Accès non autorisé à la permission {$id}.");
            }
        }

        $item->syncPermissions($permissionIds);
    }
}
