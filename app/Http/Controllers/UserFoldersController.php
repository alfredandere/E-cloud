<?php

namespace App\Http\Controllers;

use App\FileEntry;
use App\Folder;
use App\RootFolder;
use App\Services\Entries\SetPermissionsOnEntry;
use Common\Core\BaseController;
use Common\Workspaces\ActiveWorkspace;
use Illuminate\Http\JsonResponse;

class UserFoldersController extends BaseController
{
    /**
     * @var Folder
     */
    private $folder;

    /**
     * @param Folder $folder
     */
    public function __construct(Folder $folder)
    {
        $this->folder = $folder;
    }

    /**
     * @param $userId
     * @return JsonResponse
     */
    public function index($userId)
    {
        $this->authorize('index', [FileEntry::class, null, $userId]);

        $query = $this->folder
            ->where('workspace_id', app(ActiveWorkspace::class)->id ?? null);

        if ( ! app(ActiveWorkspace::class)->id) {
            $query->whereOwner($userId);
        }

        $folders = $query->select('file_entries.id', 'name', 'parent_id', 'path', 'type', 'workspace_id')
            ->with('users')
            ->orderByRaw('LENGTH(path)')
            ->limit(100)
            ->get();

        foreach ($folders as $key => $folder) {
            $folders[$key] = app(SetPermissionsOnEntry::class)->execute($folder);
        }

        return $this->success([
            'folders' => $folders,
            'rootFolder' => app(SetPermissionsOnEntry::class)->execute(new RootFolder()),
        ]);
    }
}
