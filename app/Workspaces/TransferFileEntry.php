<?php

namespace App\Workspaces;

use App\User;
use DB;
use Illuminate\Database\Query\Builder;

class TransferFileEntry
{
    public function execute(int $workspaceId, int $newOwner, int $oldOwner)
    {
        DB::table('file_entry_models')
            ->where('model_type', User::class)
            ->whereIn('file_entry_id', function(Builder $query) use($workspaceId, $oldOwner) {
                $query->select('id')
                    ->from('file_entries')
                    ->where('workspace_id', $workspaceId)
                    ->whereOwner($oldOwner);
            })->update(['model_id' => $newOwner]);

    }
}
