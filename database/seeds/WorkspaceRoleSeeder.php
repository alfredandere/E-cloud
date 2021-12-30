<?php

use Common\Auth\Permissions\Permission;
use Common\Auth\Roles\Role;
use Common\Core\Values\ValueLists;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class WorkspaceRoleSeeder extends Seeder
{
    /**
     * @var Role
     */
    private $role;

    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if ($this->role->where('type', 'workspace')->count() === 0) {
            $permissions = $this->loadPermissions();
            $role = $this->role->create([
                'name' => 'Workspace Admin',
                'description' => 'Manage workspace content, members, settings and invite new members.',
                'type' => 'workspace',
            ]);
            $role->permissions()->sync($permissions);

            $editorPermissions = $permissions->filter(function(Permission $permission) {
                return $permission->group !== 'workspace_members';
            });
            $role = $this->role->create([
                'name' => 'Workspace Editor',
                'description' => "Add, edit, move and delete workspace files.",
                'type' => 'workspace',
            ]);
            $role->permissions()->sync($editorPermissions);

            $memberPermissions = $permissions->filter(function(Permission $permission) {
                return $permission->group !== 'workspace_members' && Str::endsWith($permission->name, 'view');
            });
            $role = $this->role->create([
                'name' => 'Workspace Contributor',
                'description' => "Add and edit files.",
                'type' => 'workspace',
            ]);
            $role->permissions()->sync($memberPermissions);
        }
    }

    private function loadPermissions(): Collection
    {
        return app(ValueLists::class)->workspacePermissions();
    }
}
