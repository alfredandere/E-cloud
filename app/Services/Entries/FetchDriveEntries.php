<?php

namespace App\Services\Entries;

use App\FileEntry;
use App\RootFolder;
use Arr;
use Common\Database\Paginator;
use Common\Workspaces\ActiveWorkspace;
use DB;
use Illuminate\Database\Eloquent\Builder;

class FetchDriveEntries
{
    /**
     * @var FileEntry
     */
    private $entry;

    /**
     * @var Builder|FileEntry
     */
    private $query;

    /**
     * @var array
     */
    private $params;

    /**
     * @var bool
     */
    private $sharedOnly;

    /**
     * @var bool
     */
    private $searching;

    /**
     * @var FileEntry
     */
    private $activeFolder;

    /**
     * @var SetPermissionsOnEntry
     */
    private $setPermissionsOnEntry;

    public function __construct(FileEntry $entry, SetPermissionsOnEntry $setPermissionsOnEntry)
    {
        $this->entry = $entry;
        $this->setPermissionsOnEntry = $setPermissionsOnEntry;
    }

    /**
     * @param array $params
     * @return array
     */
    public function execute($params)
    {
        $params['perPage'] = $params['perPage'] ?? 50;
        $this->params = $params;
        $paginator = (new Paginator($this->entry, $params));
        $this->query = $paginator->query();
        $trashedOnly = $this->getBoolParam('deletedOnly');
        $starredOnly = $this->getBoolParam('starredOnly');
        $recentOnly = $this->getBoolParam('recentOnly');
        $this->sharedOnly = $this->getBoolParam('sharedOnly');
        $this->searching = Arr::get($params, 'query') || Arr::get($params, 'type');
        $entryIds = Arr::get($params, 'entryIds');
        $parentIds = Arr::get($params, 'parentIds');

        // folders should always be first
        $this->query->orderBy(DB::raw('type = "folder"'), 'desc')
            ->with('users', 'tags');

        $this->setActiveFolder($params);

        // fetch only entries that are children of specified parent,
        // in trash, show files/folders if their parent is not trashed
        if (!$trashedOnly && !$starredOnly && !$recentOnly && !$this->searching && !$this->sharedOnly && !$entryIds) {
            if ($parentIds) {
                $this->query->whereIn('parent_id', explode(',', $parentIds));
            } else {
                $this->query->where('parent_id', $this->activeFolder && $this->activeFolder->id ? $this->activeFolder->id : null);
            }
        }

        $this->filterByUser();

        // load entries with ids matching [entryIds], but only if their parent id is not in [entryIds]
        if ($entryIds) {
            $entryIds = explode(',', $entryIds);
            $this->query->whereIn('file_entries.id', $entryIds)->whereDoesntHave('parent', function($query) use($entryIds) {
                $query->whereIn('file_entries.id', $entryIds);
            });
        }

        // fetch only entries that are in trash
        if ($trashedOnly) {
            $this->query->onlyTrashed()->whereRootOrParentNotTrashed();
        }

        // fetch only files, if we need recent entries
        if ($recentOnly) {
            $this->query->where('type', '!=', 'folder');
        }

        // fetch only entries that are starred (favorited)
        if ($starredOnly) {
            $this->query->onlyStarred();
        }

        // fetch only entries matching specified type (image, text, audio etc)
        if ($type = Arr::get($params, 'type')) {
            $this->query->where('type', $type);
        }

        // make sure "public" uploads are not fetched
        $this->query->where('public', 0);

        if ($searchTerm = Arr::get($params, 'query')) {
            $paginator->searchCallback = function (Builder $q) use($searchTerm) {
                $q->where('name', 'like', "$searchTerm%")->orWhere('description', 'like', "$searchTerm%");
            };
        }

        // order by name in case updated_at date is the same
        if ($paginator->getOrder()['col'] != 'name') {
            $paginator->secondaryOrderCallback = function(Builder  $q) {
                $q->orderBy('name', 'asc');
            };
        }

        $results = $paginator->paginate()->toArray();
        $results['data'] = array_map(function($result) {
            return $this->setPermissionsOnEntry->execute($result);
        }, $results['data']);

        if ($this->activeFolder) {
            $results['folder'] = $this->activeFolder;
        }

        return $results;
    }

    protected function setActiveFolder(array $params)
    {
        if (array_key_exists('folderId', $params)) {
            if ( !$params['folderId'] || is_numeric($params['folderId'])) {
                $folderId = (int) $params['folderId'];
            // it's a folder hash, need to decode it
            } else {
                $folderId = $this->entry->decodeHash($params['folderId']);
            }

            // if no folderId specified, assume root folder
            $activeFolder = !$folderId ? new RootFolder() : $this->entry->with('users')->find($folderId);
            $this->activeFolder = $this->setPermissionsOnEntry->execute($activeFolder);
        }
    }

    private function filterByUser()
    {
        $userId = $this->params['userId'];
        $workspaceId = app(ActiveWorkspace::class)->workspace()->id ?? null;

        // shares page, get only entries user has access to, but did not upload
        if ($this->sharedOnly) {
            return $this->query->sharedWithUserOnly($userId);
        }

        // filter by workspace
        if ($workspaceId) {
            return $this->query->where('workspace_id', $workspaceId);
        } else {
            $this->query->whereNull('workspace_id');
        }

        // listing children of specific folder or searching.
        // get all children of folder that user has access to
        if (($this->activeFolder && $this->activeFolder->id) || $this->searching) {
            return $this->query->whereUser($userId);
        }

        // root folder or other pages (recent, trash etc.)
        // get only entries that user has created
        return $this->query->whereOwner($userId);
    }

    private function getBoolParam(string $name): bool
    {
        return filter_var(Arr::get($this->params, $name, false), FILTER_VALIDATE_BOOL);
    }
}
