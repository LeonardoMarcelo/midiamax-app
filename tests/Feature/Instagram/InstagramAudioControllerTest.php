<?php

declare(strict_types=1);

use App\Enums\SocialAccount\Platform;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['account_id' => $this->user->account_id, 'user_id' => $this->user->id]);
    $this->workspace->members()->attach($this->user->id, membershipPivot('admin'));
    $this->user->update(['current_workspace_id' => $this->workspace->id]);
    $this->user->refresh();

    $this->account = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => Platform::InstagramFacebook,
        'platform_user_id' => 'ig_1',
        'access_token' => 'page-token',
    ]);

    $this->graph = config('trypost.platforms.instagram-facebook.graph_api');
});

test('searches instagram music for an account connected through facebook', function () {
    Http::fake([
        "{$this->graph}/ig_audio*" => Http::response(['audio' => [[
            'audio_id' => 'audio-1',
            'title' => 'Song',
            'display_artist' => 'Band',
            'cover_artwork_thumbnail_uri' => 'https://example.com/cover.jpg',
            'duration_in_ms' => 30000,
            'download_url' => 'https://example.com/preview.mp3',
            'audio_type' => 'music',
        ]]]),
    ]);

    $this->actingAs($this->user)
        ->getJson(route('app.instagram.audio.index', ['account' => $this->account, 'q' => 'song']))
        ->assertOk()
        ->assertExactJson([[
            'id' => 'audio-1',
            'title' => 'Song',
            'artist' => 'Band',
            'cover_url' => 'https://example.com/cover.jpg',
            'duration_ms' => 30000,
            'preview_url' => 'https://example.com/preview.mp3',
        ]]);

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), "{$this->graph}/ig_audio")
        && in_array($request['audio_type'], ['music', 'original_sound'], true)
        && $request['user_id'] === 'ig_1'
        && $request['search_query'] === 'song'
        && $request['access_token'] === 'page-token');
});

test('lists original sounds by their creator when music is unavailable', function () {
    Http::fake([
        "{$this->graph}/ig_audio?audio_type=music*" => Http::response(['error' => ['code' => 10]], 400),
        "{$this->graph}/ig_audio?audio_type=original_sound*" => Http::response(['audio' => [[
            'audio_id' => 'sound-1',
            'title' => 'Original audio',
            'ig_username' => 'creator',
            'profile_picture_url' => 'https://example.com/creator.jpg',
            'duration_in_ms' => 10000,
        ]]]),
    ]);

    $this->actingAs($this->user)
        ->getJson(route('app.instagram.audio.index', $this->account))
        ->assertOk()
        ->assertExactJson([[
            'id' => 'sound-1',
            'title' => 'Original audio',
            'artist' => 'creator',
            'cover_url' => 'https://example.com/creator.jpg',
            'duration_ms' => 10000,
            'preview_url' => null,
        ]]);
});

test('lists trending music without a search', function () {
    Http::fake(["{$this->graph}/ig_audio*" => Http::response(['audio' => []])]);

    $this->actingAs($this->user)
        ->getJson(route('app.instagram.audio.index', $this->account))
        ->assertOk()
        ->assertExactJson([]);

    Http::assertSent(fn (Request $request): bool => ! str_contains($request->url(), 'search_query'));
});

test('reports a failed instagram music search', function () {
    Http::fake(["{$this->graph}/ig_audio*" => Http::response(['error' => ['code' => 10]], 400)]);

    $this->actingAs($this->user)
        ->getJson(route('app.instagram.audio.index', $this->account))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['q' => __('posts.form.instagram.music.search_failed')]);
});

test('offers no music to an instagram account connected without facebook', function () {
    $instagram = SocialAccount::factory()->instagram()->create(['workspace_id' => $this->workspace->id]);
    Http::fake();

    $this->actingAs($this->user)
        ->getJson(route('app.instagram.audio.index', $instagram))
        ->assertNotFound();

    Http::assertNothingSent();
});

test('hides an account from another workspace', function () {
    $foreign = SocialAccount::factory()->create(['platform' => Platform::InstagramFacebook]);
    Http::fake();

    $this->actingAs($this->user)
        ->getJson(route('app.instagram.audio.index', $foreign))
        ->assertNotFound();

    Http::assertNothingSent();
});

test('a search keeps only the music naming the searched words, before original sounds', function () {
    Http::fake([
        "{$this->graph}/ig_audio?audio_type=music*" => Http::response(['audio' => [
            ['audio_id' => 'unrelated', 'title' => 'Grudge', 'display_artist' => 'Tash'],
            ['audio_id' => 'match', 'title' => 'In Da Club', 'display_artist' => '50 Cent'],
        ]]),
        "{$this->graph}/ig_audio?audio_type=original_sound*" => Http::response(['audio' => [
            ['audio_id' => 'sound-1', 'title' => 'Candy Shop 50 Cent', 'ig_username' => 'creator'],
        ]]),
    ]);

    $this->actingAs($this->user)
        ->getJson(route('app.instagram.audio.index', ['account' => $this->account, 'q' => '50 cent']))
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.id', 'match')
        ->assertJsonPath('1.id', 'sound-1');
});

test('an original sound shows its creator picture as the cover', function () {
    Http::fake([
        "{$this->graph}/ig_audio?audio_type=music*" => Http::response(['audio' => []]),
        "{$this->graph}/ig_audio?audio_type=original_sound*" => Http::response(['audio' => [
            ['audio_id' => 'sound-1', 'title' => 'In Da Club - 50 Cent', 'ig_username' => 'leveviolao'],
        ]]),
        "{$this->graph}/sound-1*" => Http::response(['audio_id' => 'sound-1', 'profile_picture_url' => 'https://example.com/leveviolao.jpg']),
    ]);

    $this->actingAs($this->user)
        ->getJson(route('app.instagram.audio.index', ['account' => $this->account, 'q' => '50 cent']))
        ->assertOk()
        ->assertJsonPath('0.cover_url', 'https://example.com/leveviolao.jpg');

    $this->actingAs($this->user)
        ->getJson(route('app.instagram.audio.index', ['account' => $this->account, 'q' => '50 cent']))
        ->assertJsonPath('0.cover_url', 'https://example.com/leveviolao.jpg');

    Http::assertSentCount(5);
});

test('looks up creator pictures for the first ten original sounds and remembers a failed lookup', function () {
    $sounds = collect(range(1, 12))->map(fn (int $i): array => ['audio_id' => "sound-{$i}", 'title' => "Sound {$i}", 'ig_username' => "creator{$i}"])->all();

    Http::fake([
        "{$this->graph}/ig_audio?audio_type=music*" => Http::response(['audio' => []]),
        "{$this->graph}/ig_audio?audio_type=original_sound*" => Http::response(['audio' => $sounds]),
        "{$this->graph}/sound-*" => Http::response(['error' => ['code' => 1]], 500),
    ]);

    $this->actingAs($this->user)->getJson(route('app.instagram.audio.index', ['account' => $this->account, 'q' => 'sound']))->assertOk();
    $this->actingAs($this->user)->getJson(route('app.instagram.audio.index', ['account' => $this->account, 'q' => 'sound']))->assertOk();

    Http::assertSentCount(2 + 10 + 2);
});
