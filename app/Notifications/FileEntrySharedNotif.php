<?php

namespace App\Notifications;

use App\FileEntry;
use App\User;
use Common\Notifications\GetsUserPreferredChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use NotificationChannels\Fcm\FcmMessage;
use Str;

class FileEntrySharedNotif extends Notification implements ShouldQueue
{
    use Queueable, GetsUserPreferredChannels;

    const NOTIF_ID = 'A01';

    /**
     * @var FileEntry[]|Collection
     */
    public $fileEntries;

    /**
     * @var User
     */
    public $sharer;

    /**
     * @param array[] $entryIds
     * @param User $sharer
     */
    public function __construct($entryIds, User $sharer)
    {
        $this->sharer = $sharer;
        $this->fileEntries = FileEntry::whereIn('id', $entryIds)->get();
    }

    /**
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        $message = (new MailMessage)
            ->subject(__('Files shared on :siteName', ['siteName' => config('app.name')]))
            ->line($this->getFirstLine());

            foreach ($this->getFileLines() as $line) {
                $message->line('- ' . $line['content']);
            }

            $message->action(__('View now'), url('drive/shares'));

            return $message;
    }

    public function toFcm($notifiable)
    {
        return FcmMessage::create()
            ->setData([
                'notifId' => self::NOTIF_ID,
                'multiple' => $this->fileEntries->count() > 1 ? 'true' : 'false',
                'entryId' => (string) $this->fileEntries->first()->id,
                'click_action' =>  'FLUTTER_NOTIFICATION_CLICK',
            ])
            ->setNotification(
                \NotificationChannels\Fcm\Resources\Notification::create()
                    ->setTitle(rtrim($this->getFirstLine(), ':'))
                    ->setBody($this->fileEntries->slice(0, 5)->map->name->implode(', '))
            );
    }

    /**
     * @param User $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $data = [
            'image' => 'people',
            'mainAction' => [
                'action' => '',
            ],
            'lines' => [
                [
                    'content' => $this->getFirstLine(),
                ],
            ],
        ];

        $data['lines'] = array_merge($data['lines'], $this->getFileLines());

        return $data;
    }

    /**
     * @return array
     */
    private function getFileLines()
    {
        $lines = [];

        foreach ($this->fileEntries as $fileEntry) {
            $lines[] = [
                'icon' => Str::kebab($fileEntry->type),
                'content' => $fileEntry->name,
                'action' => ['action' => '/drive/shares'],
            ];
        }

        return $lines;
    }

    /**
     * @return string
     */
    private function getFirstLine()
    {
        $fileCount = $this->fileEntries->count();
        $username = $this->sharer->display_name;

        if ($this->fileEntries->count() === 1) {
            return __(':username shared a file with you:', ['username' => $username]);
        } else {
            return __(':username has shared :count files with you:', ['username' => $username, 'count' => $fileCount]);
        }
    }
}
