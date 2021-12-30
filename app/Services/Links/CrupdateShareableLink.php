<?php

namespace App\Services\Links;

use App\ShareableLink;
use Carbon\Carbon;
use Arr;
use Str;

class CrupdateShareableLink
{
    /**
     * @var ShareableLink
     */
    private $link;

    /**
     * @param ShareableLink $link
     */
    public function __construct(ShareableLink $link)
    {
        $this->link = $link;
    }

    /**
     * Create a new link or update existing one.
     *
     * @param array $params
     * @param ShareableLink $link
     * @return ShareableLink|\Illuminate\Database\Eloquent\Model
     */
    public function execute($params, ShareableLink $link = null) {
        if ($link) {
            $link->fill($this->transformParams($params))->save();
        } else {
            $link = $this->link->create($this->transformParams($params));
        }

        return $link;
    }

    private function transformParams($params)
    {
        $transformed = [
            'user_id' => $params['userId'],
            'password' => $params['password'] ?? null,
            'allow_download' => $params['allowDownload'] ?? true,
            'allow_edit' => $params['allowEdit'] ?? false,
            'expires_at' => Arr::get($params, 'expiresAt') ? Carbon::parse($params['expiresAt']) : null,
        ];

        // creating a new link
        if (isset($params['entryId'])) {
            $transformed['entry_id'] = $params['entryId'];
            $transformed['hash'] = Str::random(30);
        }

        return $transformed;
    }
}
