<?php

namespace App\Services;

use Auth;
use Common\Files\Actions\GetUserSpaceUsage;
use Common\Workspaces\ActiveWorkspace;

class GetDriveSpaceUsage
{
    public function execute(): array
    {
        $activeWorkspace = app(ActiveWorkspace::class);

        if ($activeWorkspace->personal()) {
            $usage = app(GetUserSpaceUsage::class)->execute(
                null,
                Auth::user()->entries(['owner' => true])->whereNull('workspace_id')
            );
        } else {
            $usage = app(GetUserSpaceUsage::class)->execute(
                $activeWorkspace->owner(), $activeWorkspace->workspace()->entries()
            );
        }

        return $usage;
    }
}
