<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\User;
use Illuminate\Support\Facades\DB;

class PermissionsRoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // [name, name_fr, isPublic]
        $Permkeys = [
            ['users', 'utilisateurs', true],
            ['roles', 'roles', true],

            ['permissions', 'permissions', false],
            ['companies', 'entreprises', false],
            ['workareas', 'îlots', true],
            ['skills', 'compétences', true],
            ['projects', 'projets', true],
            ['tasks', 'tâches', true],
            ['ranges', 'gammes', true],
            ['hours', 'heures', true],
            ['unavailabilities', 'indiponibilités', true],
            ['schedules', 'planning', true],
            ['dealingHours', 'gestion des heures', true],
            ['customers', 'clients', true]
        ];
        // create permissions
        foreach ($Permkeys as $Permkey) {
            Permission::firstOrCreate(['name' => 'read ' . $Permkey[0], 'name_fr' => $Permkey[1], 'isPublic' => $Permkey[2]]);
            Permission::firstOrCreate(['name' => 'edit ' . $Permkey[0], 'name_fr' => $Permkey[1], 'isPublic' => $Permkey[2]]);
            Permission::firstOrCreate(['name' => 'delete ' . $Permkey[0], 'name_fr' => $Permkey[1], 'isPublic' => $Permkey[2]]);
            Permission::firstOrCreate(['name' => 'publish ' . $Permkey[0], 'name_fr' => $Permkey[1], 'isPublic' => $Permkey[2]]);
        }

        $Rolekeys = [
            ['superAdmin', false],
            ['littleAdmin', false],
            ['Administrateur', true], // role publique
            ['Utilisateur', true] // role publique
        ];

        foreach ($Rolekeys as $roleKey) {
            $role = Role::firstOrCreate(['name' => $roleKey[0], 'isPublic' => $roleKey[1]]);
            foreach ($Permkeys as $PermkeyArray) {
                $Permkey = $PermkeyArray[0];
                $role->revokePermissionTo(['read ' . $Permkey, 'edit ' . $Permkey, 'delete ' . $Permkey, 'publish ' . $Permkey]);
            }
        }

        $role = Role::where(['name' => 'superAdmin'])->first();
        $role->givePermissionTo(Permission::all());

        $role = Role::where(['name' => 'littleAdmin'])->first();
        $role->givePermissionTo(Permission::all());

        $role = Role::where(['name' => 'Administrateur'])->first();
        $role->givePermissionTo(Permission::all()->filter(function ($perm) {
            return $perm->name_fr != 'permissions' && $perm->name_fr != 'companies';
        }));

        $role = Role::where(['name' => 'Utilisateur'])->first();
        $role->givePermissionTo(Permission::whereIn('name_fr', ['heures', 'projets', 'tâches', 'indiponibilités'])->orWhereIn('name', ['read skills'])->get());

        $admin = User::where('email', 'admin@numidev.fr')->first();
        if ($admin == null) {
            $admin = User::create([
                'firstname' => 'admin',
                'lastname' => 'NUMIDEV',
                'email' => 'admin@numidev.fr',
                'password' => Hash::make('password'),
                //'email_verified_at' => '2020-01-01 00:00:00.000000',
                'isTermsConditionAccepted' => true
            ]);
            $admin->syncRoles('superAdmin');
        } else {
            $admin->syncRoles('superAdmin');
        }
    }
}
