<?php

namespace App\Listeners;

use App\FileEntry;
use Common\Files\Events\FileEntriesDeleted;
use Common\Files\Events\FileEntriesMoved;
use Common\Files\Events\FileEntriesRestored;
use Common\Files\Events\FileEntryCreated;
use DB;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;

class FolderTotalSizeSubscriber
{
    /**
     * @param  FileEntryCreated  $event
     * @return void
     */
    public function onEntryCreated(FileEntryCreated $event)
    {
        $entry = $event->fileEntry;
        if ($entry->type !== 'folder' && $entry->parent_id) {
            $entry->allParents()->increment('file_size', $entry->file_size);
        }
    }

    /**
     * @param FileEntriesDeleted|FileEntriesRestored $event
     */
    public function onEntriesDeletedOrRestored($event)
    {
        $groupedEntries = app(FileEntry::class)
            ->withTrashed()
            ->whereIn('id', $event->entryIds)
            ->whereNotNull('parent_id')
            ->get()
            ->groupBy('parent_id');

        $groupedEntries->each(function(Collection $entries, $parentId) use($event) {
            $fileSize = $entries->sum('file_size');
            if (is_a($event, FileEntriesDeleted::class)) {
                app(FileEntry::class)->where('id', $parentId)
                    ->where('file_size', '>', 0)
                    ->update(['file_size' => DB::raw("GREATEST(0, file_size - $fileSize)")]);
            } else {
                app(FileEntry::class)->where('id', $parentId)->increment('file_size', $fileSize);
            }

        });
    }

    public function onEntriesMoved(FileEntriesMoved $event)
    {
        $movedEntriesSize = app(FileEntry::class)
            ->whereIn('id', $event->entryIds)
            ->sum('file_size');

        // files could be moved from or to root
        if ($event->destination) {
            app(FileEntry::class)->where('id', $event->destination)->increment('file_size', $movedEntriesSize);
        }
        if ($event->source) {
            app(FileEntry::class)->where('id', $event->source)->decrement('file_size', $movedEntriesSize);
        }
    }

    /**
     * @param  Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            FileEntryCreated::class,
            self::class . '@onEntryCreated'
        );

        $events->listen(
            FileEntriesMoved::class,
            self::class . '@onEntriesMoved'
        );

        $events->listen(
            [FileEntriesDeleted::class, FileEntriesRestored::class],
            self::class . '@onEntriesDeletedOrRestored'
        );
    }
}
