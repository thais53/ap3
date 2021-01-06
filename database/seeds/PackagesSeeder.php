<?php

use App\Models\Package;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PackagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $package = $this->package('hours');
        if (!$package->exists) {
            $package->fill([
                'display_name' => 'Heures',
            ])->save();

            $package->givePermissionTo(Permission::whereIn('name_fr', ['utilisateurs', 'projects', 'clients', 'heures'])
                ->orWhereIn('name', ['read roles', 'read companies', 'read skills', 'read ranges', 'read clients', 'read workareas', 'read projects', 'read schedules', 'read users'])->get());
        }

        $package = $this->package('schedules');
        if (!$package->exists) {
            $package->fill([
                'display_name' => 'Plannings',
            ])->save();

            $package->givePermissionTo(Permission::whereIn('name_fr', ['roles', 'utilisateurs', 'projects', 'clients', 'pôles_de_productions', 'tâches', 'compétences', 'planning'])
                ->orWhereIn('name', ['read roles', 'read permissions', 'read companies', 'read skills', 'read ranges', 'read clients', 'read workareas', 'read projects', 'read schedules', 'read users'])->get());
        }
    }

    private function package($name)
    {
        return Package::firstOrNew(['name' => $name, 'guard_name' => 'web']);
    }
}