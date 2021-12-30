<?php

namespace App\Http\Controllers;

use App\FileEntry;
use App\Services\Entries\FetchDriveEntries;
use Auth;
use Common\Files\Controllers\FileEntriesController;
use Illuminate\Http\Request;

class DriveEntriesController extends FileEntriesController
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var FileEntry
     */
    protected $entry;

    /**
     * @param Request $request
     * @param FileEntry $entry
     */
    public function __construct(
        Request $request,
        FileEntry $entry
    ) {
        parent::__construct($request, $entry);
        $this->request = $request;
        $this->entry = $entry;
    }

    /**
     * @return array
     */
    public function index()
    {
        $params = $this->request->all();

        $params['userId'] = $this->request->get('userId', Auth::user()->id);

        $this->authorize('index', [FileEntry::class, null, $params['userId']]);

        return app(FetchDriveEntries::class)->execute($params);
    }
}
