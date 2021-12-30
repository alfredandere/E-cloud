<?php

namespace App\Http\Controllers;

use App\FileEntry;
use App\Folder;
use App\Services\Entries\CreateFolder;
use App\Services\Entries\FolderExistsException;
use App\Services\Entries\SetPermissionsOnEntry;
use Common\Core\BaseController;
use Common\Files\Events\FileEntryCreated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FoldersController extends BaseController
{
    /**
     * @var Folder
     */
    private $folder;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param Folder $folder
     * @param Request $request
     */
    public function __construct(Folder $folder, Request $request)
    {
        $this->folder = $folder;
        $this->request = $request;
    }

    /**
     * Find a folder using specified params.
     *
     * @return JsonResponse
     */
    public function show()
    {
        if ($this->request->has('hash')) {
            $folder = $this->folder->with('users')->whereHash($this->request->get('hash'))->firstOrFail();
        }

        $this->authorize('show', $folder);

        return $this->success(['folder' => $folder]);
    }

    /**
     * Create a new folder.
     *
     * @return JsonResponse
     */
    public function store()
    {
        $name = $this->request->get('name');
        $parentId = $this->request->get('parentId');

        $this->validate($this->request, [
            'name' => 'required|string|min:3',
            'parentId' => 'nullable|integer|exists:file_entries,id',
        ]);

        $this->authorize('store', [FileEntry::class, $parentId]);

        try {
            $folder = app(CreateFolder::class)->execute([
                'name' => $name,
                'parentId' => $parentId,
                'userId' => $this->request->user()->id
            ]);
        } catch (FolderExistsException $e) {
            return $this->error('', ['name' => __('Folder with same name already exists.')]);
        }

        event(new FileEntryCreated($folder, $this->request->all()));

        return $this->success([
            'folder' => app(SetPermissionsOnEntry::class)->execute($folder->load('users'))
        ]);
    }
}
