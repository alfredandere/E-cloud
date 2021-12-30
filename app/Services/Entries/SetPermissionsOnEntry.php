<?php

namespace App\Services\Entries;

use App\FileEntry;
use App\Policies\DriveFileEntryPolicy;
use App\User;
use Arr;
use Auth;
use Common\Workspaces\ActiveWorkspace;

/**
 * The "execute" method will be called a few hundred or more times per request
 * so it needs to be as fast as possible and cache as much stuff as possible.
 */
class SetPermissionsOnEntry
{
    /**
     * @var bool
     */
    private $isPersonalWorkspace;

    /**
     * @var array
     */
    private $workspacePermissions;

    /**
     * @var User
     */
    private $user;

    /**
     * @var DriveFileEntryPolicy
     */
    private $policy;

    /**
     * @var array
     */
    private $directPermissions = [];

    private $permissionToCheck = [
        'files.download',
        'files.update',
        'files.delete',
    ];

    public function __construct()
    {
        $this->user = Auth::user();
        $this->policy = app(DriveFileEntryPolicy::class);
        $this->isPersonalWorkspace = app(ActiveWorkspace::class)->personal();
    }

    /**
     * @param array|FileEntry|null if no entry is provided return permissions for "root" folder $entry
     * @return array|FileEntry
     */
    public function execute($entry = null)
    {
        $entryPermissions = [];
        $entryUser = Arr::first($entry['users'] ?? [], function($entryUser) {
            return $entryUser['id'] === $this->user->id;
        });

        foreach ($this->permissionToCheck as $permission) {
            $entryPermissions[$permission] = $this->hasDirectPermission($permission)
                || $this->policy->userOwnsEntryOrWasGrantedPermission($entryUser, $permission)
                || $this->userHasPermissionViaWorkspace($permission);
        }

        $entry['permissions'] = $entryPermissions;
        return $entry;
    }

    protected function hasDirectPermission(string $permission): bool
    {
        if (empty($this->directPermissions)) {
            foreach ($this->permissionToCheck as $permissionToCheck) {
                $this->directPermissions[$permissionToCheck] = $this->user->hasPermission($permissionToCheck);
            }
        }

        return in_array($permission, $this->directPermissions);
    }

    protected function userHasPermissionViaWorkspace(string $permission): bool
    {
        if ($this->isPersonalWorkspace) {
            return false;
        }

        if (!$this->workspacePermissions) {
            $this->workspacePermissions = app(ActiveWorkspace::class)->member($this->user->id)->permissions->pluck('name')->toArray();
        }

        return in_array($permission, $this->workspacePermissions);
    }
}
