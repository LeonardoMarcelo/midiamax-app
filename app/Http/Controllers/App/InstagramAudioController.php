<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Actions\SocialAccount\SearchInstagramAudio;
use App\Enums\SocialAccount\Platform as SocialPlatform;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Instagram\IndexInstagramAudioRequest;
use App\Http\Resources\App\InstagramAudioResource;
use App\Models\SocialAccount;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class InstagramAudioController extends Controller
{
    public function index(IndexInstagramAudioRequest $request, SocialAccount $account): AnonymousResourceCollection
    {
        $this->authorize('view', $account);

        abort_unless($account->platform === SocialPlatform::InstagramFacebook, Response::HTTP_NOT_FOUND);

        $this->authorize('createPost', $request->user()->currentWorkspace);

        try {
            $tracks = SearchInstagramAudio::execute($account, $request->validated('q'));
        } catch (ConnectionException|RequestException) {
            throw ValidationException::withMessages(['q' => __('posts.form.instagram.music.search_failed')]);
        }

        return InstagramAudioResource::collection($tracks);
    }
}
