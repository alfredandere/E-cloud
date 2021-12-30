<?php

namespace App\Http\Controllers;

use App\FileEntry;
use Common\Core\BaseController;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Common\Tags\Tag;

class StarredEntriesController extends BaseController
{
    const TAG_NAME = 'starred';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Tag
     */
    private $tag;

    /**
     * @param Request $request
     * @param Tag $tag
     */
    public function __construct(Request $request, Tag $tag)
    {
        $this->request = $request;
        $this->tag = $tag;
    }

    /**
     * Attach "starred" tag to specified entries.
     *
     * @return JsonResponse
     */
    public function add()
    {
        $entryIds = $this->request->get('entryIds');

        $this->validate($this->request, [
            'entryIds' => 'required|array|exists:file_entries,id'
        ]);

        $this->authorize('update', [FileEntry::class, $entryIds]);

        $tag = $this->tag->where('name', self::TAG_NAME)->first();

        $tag->attachEntries($entryIds, $this->request->user()->id);

        return $this->success(['tag' => $tag]);
    }

    /**
     * Detach "starred" tag from specified entries.
     *
     * @return JsonResponse
     */
    public function remove()
    {
        $entryIds = $this->request->get('entryIds');

        $this->validate($this->request, [
            'entryIds' => 'required|array|exists:file_entries,id'
        ]);

        $this->authorize('update', [FileEntry::class, $entryIds]);

        $tag = $this->tag->where('name', self::TAG_NAME)->first();

        $tag->detachEntries($entryIds, $this->request->user()->id);

        return $this->success(['tag' => $tag]);
    }
}
