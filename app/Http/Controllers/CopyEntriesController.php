<?php

namespace App\Http\Controllers;

use App\FileEntry;
use App\Services\Entries\SetPermissionsOnEntry;
use Auth;
use Common\Core\BaseController;
use Common\Files\Events\FileEntryCreated;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Str;

class CopyEntriesController extends BaseController
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var FileEntry
     */
    private $entry;

    public function __construct(Request $request, FileEntry $entry)
    {
        $this->request = $request;
        $this->entry = $entry;
    }

    public function copy(SetPermissionsOnEntry $setPermissions)
    {
        $destination = $this->request->get('destination');
        $entryIds = $this->request->get('entryIds');

        $this->validate($this->request, [
            'entryIds' => 'required|array',
            'entryIds.*' => 'required|integer',
            'destination' => 'nullable|integer|exists:file_entries,id'
        ]);

        $this->authorize('index', [FileEntry::class, $entryIds]);

        $copies = $this->copyEntries($entryIds, $destination);

        $copies = $copies->map(function(FileEntry $entry) use($setPermissions) {
            return $setPermissions->execute($entry);
        });

        return $this->success(['entries' => $copies]);
    }

    /**
     * @param array|\Illuminate\Support\Collection $entryIds
     * @param int|null $parentId
     * @return Collection
     */
    private function copyEntries($entryIds, $parentId = null)
    {
        $copies = collect();

        foreach ($this->entry->with('owner')->whereIn('id', $entryIds)->cursor() as $entry) {
            if ($entry->type === 'folder') {
                $copies[] = $this->copyFolderEntry($entry, $parentId);
            } else {
                $copies[] = $this->copyFileEntry($entry, $parentId);
            }
        }

        return $copies;
    }

    /**
     * @param FileEntry $original
     * @param int|null $parentId
     * @return FileEntry
     */
    private function copyFileEntry(FileEntry $original, $parentId = null)
    {
        $copy = $this->copyModel($original, $parentId);
        $this->copyFile($original, $copy);

        event(new FileEntryCreated($copy));

        return $copy;
    }

    /**
     * @param FileEntry $original
     * @param int|null $parentId
     * @return FileEntry
     */
    private function copyFolderEntry(FileEntry $original, $parentId = null)
    {
        $copy = $this->copyModel($original, $parentId);
        $this->copyChildEntries($copy, $original);

        return $copy;
    }

    /**
     * @param FileEntry $copy
     * @param FileEntry $original
     */
    private function copyChildEntries(FileEntry $copy, FileEntry $original)
    {
        $entryIds = $this->entry->where('parent_id', $original->id)->pluck('id');

        if ( ! $entryIds->isEmpty()) {
            $this->copyEntries($entryIds, $copy->id);
        }
    }

    /**
     * @param FileEntry $original
     * @param int|null $parentId
     * @return FileEntry
     */
    private function copyModel(FileEntry $original, $parentId = null)
    {
        $newName = $original->name;
        $newOwnerId = $this->getCopyOwnerId();
        $copyingIntoSameDrive = $newOwnerId === $original->getOwner()->id;

        // if no parent ID is specified and we are copying into the
        // same users drive, we can copy into the same folder as original
        if ( ! $parentId && $copyingIntoSameDrive) {
            $parentId = $original->parent_id;
        }

        // if we are copying into same folder, add " - Copy" to the end of copies name
        if ($parentId === $original->parent_id && $copyingIntoSameDrive) {
            $newName = "$original->name - " . __('Copy');
        }

        /**
         * @var $copy FileEntry
         */
        $copy = $original->replicate();
        $copy->name = $newName;
        $copy->path = null;
        $copy->file_name = Str::random(40);
        $copy->parent_id = $parentId;
        $copy->save();

        $copy->generatePath();

        // set owner
        $copy->users()->attach($newOwnerId, ['owner' => true]);

        $copy->load('users');

        return $copy;
    }

    /**
     * @param FileEntry $original
     * @param FileEntry $copy
     */
    private function copyFile(FileEntry $original, FileEntry $copy)
    {
        $paths = $original->getDisk()->files($original->file_name);
        foreach ($paths as $path) {
            $newPath = str_replace($original->file_name, $copy->file_name, $path);
            $original->getDisk()->copy($path, $newPath);
        }
    }

    /**
     * Get user to which entry copies should be attached.
     *
     * @return int
     */
    private function getCopyOwnerId()
    {
        return Auth::user()->id;
    }
}
