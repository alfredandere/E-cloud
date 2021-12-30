<?php

namespace App\Http\Controllers;

use App;
use App\FileEntry;
use App\Notifications\FileEntrySharedNotif;
use App\Services\Shares\AttachUsersToEntry;
use App\Services\Shares\DetachUsersFromEntries;
use App\Services\Shares\GetUsersWithAccessToEntry;
use App\ShareableLink;
use App\User;
use Auth;
use Common\Core\BaseController;
use Common\Files\Traits\LoadsAllChildEntries;
use Common\Settings\Settings;
use Common\Validation\Validators\EmailsAreValid;
use DB;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Notification;

class SharesController extends BaseController
{
    use LoadsAllChildEntries;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @param Request $request
     * @param Settings $settings
     */
    public function __construct(Request $request, Settings $settings)
    {
        $this->request = $request;
        $this->settings = $settings;
    }

    /**
     * Import entry into current user's drive using specified shareable link.
     *
     * @param int $linkId
     * @param AttachUsersToEntry $action
     * @param ShareableLink $linkModel
     * @return JsonResponse
     */
    public function addCurrentUser($linkId, AttachUsersToEntry $action, ShareableLink $linkModel)
    {
        /* @var ShareableLink $link */
        $link = $linkModel->with('entry')->findOrFail($linkId);

        $this->authorize('show', [$link->entry, $link]);

        $permissions = [
            'view' => true,
            'edit' => $link->allow_edit,
            'download' => $link->allow_download,
        ];

        $action->execute(
            [$this->request->user()->email],
            [$link->entry_id],
            $permissions
        );

        $users = app(GetUsersWithAccessToEntry::class)
            ->execute($link->entry_id);

        return $this->success(['users' => $users]);
    }

    /**
     * Share drive entries with specified users.
     *
     * @param AttachUsersToEntry $action
     * @return Response
     */
    public function addUsers(AttachUsersToEntry $action)
    {
        $entryIds = $this->request->get('entryIds');
        $shareeEmails = $this->request->get('emails');

        $this->authorize('update', [FileEntry::class, $entryIds]);

        // TODO: refactor messages into custom validator, so can reuse elsewhere
        $emails =  $this->request->get('emails', []);

        $messages = [];
        foreach ($emails as $key => $email) {
            $messages["emails.$key"] = $email;
        }

        $this->validate($this->request, [
            'emails' => ['required', 'min:1', new EmailsAreValid()],
            'permissions' => 'required|array',
            'entryIds' => 'required|min:1',
            'entryIds.*' => 'required|integer',
        ], [], $messages);

        $sharees = $action->execute(
            $shareeEmails,
            $entryIds,
            $this->request->get('permissions')
        );

        if ($this->settings->get('drive.send_share_notification')) {
            try {
                Notification::send($sharees, new FileEntrySharedNotif($entryIds, Auth::user()));
            } catch (Exception $e) {
                //
            }
        }

        $users = app(GetUsersWithAccessToEntry::class)
            ->execute(head($entryIds));

        return $this->success(['users' => $users]);
    }

    public function changePermissions(int $memberId)
    {
        $this->request->validate([
            'permissions' => 'required|array',
            'entryIds' => 'required|array',
        ]);

        $entryIds = $this->request->get('entryIds');
        $this->authorize('update', [FileEntry::class, $entryIds]);

        DB::table('file_entry_models')
            ->where('model_id', $memberId)
            ->where('model_type', User::class)
            ->whereIn('file_entry_id', $this->loadChildEntries($entryIds)->pluck('id'))
            ->update(['permissions' => json_encode($this->request->get('permissions'))]);

        $users = app(GetUsersWithAccessToEntry::class)
            ->execute(head($entryIds));

        return $this->success(['users' => $users]);
    }

    /**
     * Detach user from specified entries.
     *
     * @param int $userId
     * @param DetachUsersFromEntries $action
     * @return JsonResponse
     */
    public function removeUser($userId, DetachUsersFromEntries $action)
    {
        $entryIds = $this->request->get('entryIds');

        // there's no need to authorize if user is
        // trying to remove himself from the entry
        if ((int) $userId !== $this->request->user()->id) {
            $this->authorize('update', [FileEntry::class, $entryIds]);
        }

        $action->execute(collect($entryIds), collect([$userId]));

        $users = app(GetUsersWithAccessToEntry::class)
            ->execute(head($entryIds));

        return $this->success(['users' => $users]);
    }
}
