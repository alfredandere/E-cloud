<?php

namespace App\Services\Entries;

use App\Folder;
use Arr;
use Common\Workspaces\ActiveWorkspace;

class CreateFolder
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
     * @param array $data
     * @return Folder
     */
    public function execute($data)
    {
        $userId = $data['userId'];
        $parentId = Arr::get($data, 'parentId');
        $folderName = $data['name'];

        $exists = $this->folder
            ->where('parent_id', $parentId)
            ->where('workspace_id', app(ActiveWorkspace::class)->id)
            ->where('name', $data['name'])
            ->where('type', 'folder')
            ->whereOwner($userId)
            ->first();

        if ( ! is_null($exists)) {
            throw new FolderExistsException();
        }

        $folder = $this->folder->create([
            'name' => $folderName,
            'file_name' => $folderName,
            'parent_id' => $parentId,
        ]);

        $folder->generatePath();

        $folder->users()->attach($userId, ['owner' => true]);

        return $folder;
    }
}
