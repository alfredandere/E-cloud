<?php

namespace App\Policies;

use App\ShareableLink;
use App\User;
use Arr;
use Common\Core\Policies\FileEntryPolicy;
use Common\Files\FileEntry;
use Common\Workspaces\ActiveWorkspace;
use Hash;
use Illuminate\Http\Request;

class DriveFileEntryPolicy extends FileEntryPolicy
{
    /**
     * @var ActiveWorkspace
     */
    private $activeWorkspace;

    /**
     * @var Request
     */
    private $request;

    public function __construct(ActiveWorkspace $activeWorkspace, Request $request)
    {
        $this->request = $request;
        $this->activeWorkspace = $activeWorkspace;
    }

    public function index(User $currentUser, array $entryIds = null, int $userId = null): bool
    {
        // if we're requesting resources for a particular workspace,let user view the resources
        // as long as they are a member, even without explicit "files.view" permission
        if (!$entryIds && !$this->activeWorkspace->personal()) {
            return !!$this->activeWorkspace->member($currentUser->id);
        }

        return parent::index($currentUser, $entryIds, $userId);
    }

    public function show(?User $user, FileEntry $entry, ShareableLink $link = null): bool
    {
        if ($link = $this->getLinkForRequest($link)) {
            return $this->authorizeShareableLink($link, $entry);
        }

        return parent::show($user, $entry);
    }

    public function download(User $user, $entries, ShareableLink $link = null): bool
    {
        if ($link = $this->getLinkForRequest($link)) {
            // shareable link is always for one entry only
            return $this->authorizeShareableLink($link, $entries[0]);
        }

        return parent::download($user, $entries);
    }

    protected function userCan(User $currentUser, string $permission, $entries)
    {
        $entries = $this->findEntries($entries);

        // first run regular checks (user has global permission, or owns entry)
        if (parent::userCan($currentUser, $permission, $entries)) {
            return true;

        // if we're not in personal workspace, check if user has permissions via workspace
        } else if ( ! $this->activeWorkspace->personal()) {
            // first check if user is a member of active workspace
            if ($workspaceMember = $this->activeWorkspace->member($currentUser->id)) {
                // then check if user has specified permission for all the entries
                return $entries->every(function(FileEntry $entry) use($permission, $workspaceMember) {
                    $entryIsInWorkspace = $entry->workspace_id === $this->activeWorkspace->workspace()->id;
                    // user can view entries without any special permission by just being a member of workspace
                    if ($permission === 'files.view' || $permission === 'files.show') {
                        return $entryIsInWorkspace;
                    } else {
                        return $entryIsInWorkspace && $workspaceMember->hasPermission($permission);
                    }
                });
            }
        }

        return false;
    }

    private function authorizeShareableLink(ShareableLink $link, FileEntry $entry)
    {
        $password = $this->request->get('password');

        // check password first, if needed
        if ( ! $this->passwordIsValid($link, $password)) {
            return false;
        }

        // user can view this file if file or any of its parents is attached to specified link
        $entryPath = explode('/', $entry->path);
        return Arr::first($entryPath, function($entryId) use($link) {
            return (int) $entryId === $link->entry_id;
        });
    }

    private function getLinkForRequest(ShareableLink $link = null): ?ShareableLink {
        if ($link) return $link;

        if ($this->request->has('shareable_link')) {
            $linkId = $this->request->get('shareable_link');
            return app(ShareableLink::class)->findOrFail($linkId);
        }

        return null;
    }

    private function passwordIsValid(ShareableLink $link, ?string $password): bool
    {
        // link has no password
        if ( ! $link->password) return true;
        return Hash::check($password, $link->password);
    }
}
