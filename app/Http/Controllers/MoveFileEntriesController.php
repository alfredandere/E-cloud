<?php

namespace App\Http\Controllers;

use App\FileEntry;
use App\Services\Entries\SetPermissionsOnEntry;
use Common\Core\BaseController;
use Common\Files\Events\FileEntriesMoved;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Str;

class MoveFileEntriesController extends BaseController
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

    public function move( SetPermissionsOnEntry $setPermissions)
    {
        //should limit moves to 30 items (for now) for performance reasons
        $entryIds = collect($this->request->get('entryIds'))->take(30);
        $destination = $this->request->get('destination');

        $this->validate($this->request, [
            'entryIds' => 'required|array',
            'entryIds.*' => 'required|integer',
            'destination' => 'nullable|integer|exists:file_entries,id'
        ]);

        $this->authorize('update', [FileEntry::class, $entryIds->toArray()]);

        $entries = $this->getEntries($entryIds);
        $newParent = $this->getNewParent($destination);
        $entries = $this->removeInvalidEntries($entries, $newParent);

        // there was an issue with entries or parent, bail
        if ($entries->isEmpty()) return $this->error();

        $this->updateParent($destination, $entries);
        $source = $entries->first()->parent_id;

        $entries->each(function(FileEntry $entry) use($newParent, $destination) {
            $entry->parent_id = $destination;
            $oldPath = $entry->path;
            $newPath = !$newParent ? '' : $newParent->path;
            $oldParent = last(explode('/', $oldPath));
            $newPath .= "/$oldParent";
            $this->entry->updatePaths($oldPath, $newPath);
            $entry->path = $newPath;
        });

        event(new FileEntriesMoved($entries->pluck('id')->toArray(), $destination, $source));

        $entries = $entries->map(function(FileEntry $entry) use($setPermissions) {
            return $setPermissions->execute($entry);
        });

        return $this->success(['entries' => $entries]);
    }

    /**
     * Make sure entries can't be moved into themselves or their children.
     *
     * @param Collection $entries
     * @param int $parent
     * @return Collection
     */
    private function removeInvalidEntries(Collection $entries, $parent)
    {
        if ( ! $parent) return $entries;

        return $entries->filter(function($entry) use($parent) {
            return ! Str::contains($parent->path, $entry->id);
        });
    }

    /**
     * @param int|null $destination
     * @return FileEntry|null
     */
    private function getNewParent($destination)
    {
        if ( ! $destination) return null;
        return $this->entry->select('path', 'id')->find($destination);
    }

    /**
     * @param Collection $entryIds
     * @return Collection
     */
    private function getEntries(Collection $entryIds)
    {
        return $this->entry
            ->whereIn('id', $entryIds)
            ->get();
    }

    /**
     * @param int|null $destination
     * @param Collection $entries
     */
    private function updateParent($destination, Collection $entries)
    {
        $this->entry
            ->whereIn('id', $entries->pluck('id'))
            ->update(['parent_id' => $destination]);
    }
}
