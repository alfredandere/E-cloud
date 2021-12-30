<?php

namespace App\Http\Controllers;

use App\Services\GetDriveSpaceUsage;
use Auth;
use Common\Core\BaseController;
use Illuminate\Http\JsonResponse;

class SpaceUsageController extends BaseController
{
    public function index(): JsonResponse
    {
        $this->authorize('show', Auth::user());

        $usage = app(GetDriveSpaceUsage::class)->execute();

        return $this->success($usage);
    }
}
