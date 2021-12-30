<?php

namespace App\Services\Shares;

use App\Services\Shares\Traits\CreatesUserEntryPivotRecords;
use App\Services\Shares\Traits\GeneratesSharePermissions;
use App\User;
use Common\Files\Traits\LoadsAllChildEntries;
use DB;
use Illuminate\Database\Eloquent\Collection;

class AttachUsersToEntry
{
    use CreatesUserEntryPivotRecords, GeneratesSharePermissions, LoadsAllChildEntries;

    /**
     * @var User
     */
    private $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Attach specified users to a file entries.
     *
     * @param array $emails
     * @param array $entries
     * @param array $permissions
     * @return User[]|Collection
     */
    public function execute($emails, $entries, $permissions)
    {
        $entryIds = collect($entries);

        // permissions on each user are expected
        $users = $this->user->whereIn('email', $emails)->get();

        $transformedUsers = $users->map(function(User $user) use($permissions) {
            return ['id' => $user->id, 'permissions' => $this->generateSharePermissions($permissions)];
        });

        $records = $this->createPivotRecords($transformedUsers, $entryIds);

        DB::table('file_entry_models')->insert($records->toArray());

        return $users;
    }
}
