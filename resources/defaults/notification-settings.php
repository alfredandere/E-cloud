<?php

use App\Notifications\FileEntrySharedNotif;
use Common\Workspaces\Notifications\WorkspaceInvitation;

return [
    'available_channels' => ['email', 'browser', 'mobile'],
    'grouped_notifications' => [
        [
            'group_name' => 'Notify me when…',
            'notifications' => [
                ['name' => 'I am invited to workspace', 'notif_id' => WorkspaceInvitation::NOTIF_ID],
                ['name' => 'A file or folder is shared with me', 'notif_id' => FileEntrySharedNotif::NOTIF_ID],
            ]
        ],
    ]
];
