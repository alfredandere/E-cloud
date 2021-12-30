<?php

namespace App\Services\Entries;

use Arr;

class DriveUploadResponseTransformer
{
    /**
     * @param array $response
     * @return array
     */
    public function transform($response)
    {
        if ( ! empty(Arr::get($response, 'fileEntry.users'))) {
            $response['fileEntry'] = app(SetPermissionsOnEntry::class)->execute($response['fileEntry']);

            if (isset($response['fileEntry']['parent']['users'])) {
                $response['fileEntry']['parent'] = app(SetPermissionsOnEntry::class)->execute($response['fileEntry']['parent']);
            }

            if (isset($response['fileEntry']['all_parents'])) {
                foreach ($response['fileEntry']['all_parents'] as $key => $parent) {
                    $response['fileEntry']['all_parents'][$key] = app(SetPermissionsOnEntry::class)->execute($response['fileEntry']['all_parents'][$key]);
                }
            }
        }

        return $response;
    }
}
