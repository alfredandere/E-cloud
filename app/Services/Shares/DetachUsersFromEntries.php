<?php

namespace App\Services\Shares;

use App\User;
use Common\Files\Traits\LoadsAllChildEntries;
use DB;
use Illuminate\Support\Collection;

class DetachUsersFromEntries
{
    use LoadsAllChildEntries;

    /**
     * Detach (non owner) users from specified entries.
     *
     * @param Collection $entryIds
     * @param Collection $userIds
     */
    public function execute($entryIds, $userIds)
    {
        $entriesAndChildren = $this->loadChildEntries($entryIds);

        DB::table('file_entry_models')
            ->whereIn('file_entry_id', $entriesAndChildren->pluck('id'))
            ->whereIn('model_id', $userIds)
            ->where('model_type', User::class)
            ->where('owner', false)
            ->delete();
    }
}
