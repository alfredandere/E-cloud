<?php

namespace App\Http\Controllers;

use App\ShareableLink;
use Common\Core\BaseController;
use Hash;
use Illuminate\Http\Request;

class ShareableLinkPasswordController extends BaseController
{
    /**
     * @var ShareableLink
     */
    private $link;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param ShareableLink $link
     * @param Request $request
     */
    public function __construct(ShareableLink $link, Request $request)
    {
        $this->link = $link;
        $this->request = $request;
    }

    /**
     * Check whether link password matches.
     *
     * @param int $linkId
     * @return \Illuminate\Http\JsonResponse
     */
    public function check($linkId)
    {
        $link = $this->link->findOrFail($linkId);
        $password = $this->request->get('password');

        return $this->success([
            'matches' => Hash::check($password, $link->password)
        ]);
    }
}
