<?php

namespace App;

use Arr;
use Auth;
use Common\Workspaces\ActiveWorkspace;

class RootFolder extends FileEntry
{
    protected $id = null;
    protected $appends = ['name'];
    protected $casts = [];
    protected $relations = ['users'];

    protected $attributes = [
        'type' => 'folder',
        'id' => null,
        'hash' => '',
        'path' => '',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setCurrentUserAsOwner();
        $this->workspace_id = app(ActiveWorkspace::class)->id;
    }

    public function getNameAttribute(): string
    {
        return trans('All Files');
    }

    public function getHashAttribute(): string
    {
        return '';
    }

    private function setCurrentUserAsOwner(): void
    {
        $users = collect([]);
        if (app(ActiveWorkspace::class)->currentUserIsOwner()) {
            $user = Arr::only(Auth::user()->toArray(), ['first_name', 'last_name', 'display_name', 'email', 'id', 'avatar']);
            $user['owns_entry'] = true;
            $users[] = $user;
        }
        $this->users = $users;
    }
}
