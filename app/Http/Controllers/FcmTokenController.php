<?php

namespace App\Http\Controllers;

use Auth;
use Common\Core\BaseController;
use Illuminate\Http\Request;

class FcmTokenController extends BaseController
{
    /**
     * @var Request
     */
    private $request;

    public function __construct(Request  $request)
    {
        $this->request = $request;
    }

    public function store()
    {
        $data = $this->validate($this->request, [
            'token' => 'required|string',
            'deviceId' => 'required|string',
        ]);

        Auth::user()->fcmTokens()->where(['device_id' => $data['deviceId']])->delete();

        $model = Auth::user()->fcmTokens()->create([
            'token' => $data['token'],
            'device_id' => $data['deviceId'],
        ]);

        return $this->success(['token' => $model->token]);
    }
}
