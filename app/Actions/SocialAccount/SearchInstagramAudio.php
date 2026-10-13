<?php

declare(strict_types=1);

namespace App\Actions\SocialAccount;

use App\Models\SocialAccount;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Audio an Instagram account connected through Facebook may attach to a reel,
 * read from the Instagram Audio API, which lists them under `audio`: music
 * then original sounds, trending or matching a search.
 *
 * @see https://developers.facebook.com/docs/instagram-platform/content-publishing/audio-api/
 */
class SearchInstagramAudio
{
    private const array AUDIO_TYPES = ['music', 'original_sound'];

    /**
     * @return list<array{id: string, title: string, artist: string|null, cover_url: string|null, duration_ms: int|null, preview_url: string|null}>
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public static function execute(SocialAccount $account, ?string $query = null): array
    {
        $url = "{$account->platform->instagramGraphBaseUrl()}/ig_audio";

        $responses = Http::pool(fn (Pool $pool): array => collect(self::AUDIO_TYPES)
            ->map(fn (string $type) => $pool->as($type)
                ->connectTimeout(3)
                ->timeout(8)
                ->get($url, array_filter([
                    'audio_type' => $type,
                    'user_id' => $account->platform_user_id,
                    'search_query' => filled($query) ? $query : null,
                    'access_token' => $account->access_token,
                ])))
            ->all());

        $succeeded = collect($responses)->filter(fn (mixed $response): bool => $response instanceof Response && $response->successful());

        if ($succeeded->isEmpty()) {
            $failed = collect($responses)->first();

            throw $failed instanceof Response ? $failed->toException() ?? new ConnectionException('Instagram audio search failed.') : $failed;
        }

        $tracks = fn (string $type): Collection => Collection::make((array) data_get($succeeded->get($type)?->json(), 'audio', []))
            ->map(fn (mixed $track): array => self::present($track))
            ->filter(fn (array $track): bool => $track['id'] !== '');

        $music = blank($query)
            ? $tracks('music')
            : $tracks('music')->filter(fn (array $track): bool => self::matches($track, $query));

        return $music
            ->concat(self::withCreatorPictures($account, $tracks('original_sound')))
            ->unique('id')
            ->values()
            ->all();
    }

    /**
     * The search lists an original sound without any picture; its own lookup
     * carries the creator's profile picture, which stands in for the cover.
     * Looked up together and kept for a few hours, as the picture URL expires.
     *
     * @param  Collection<int, array{id: string, title: string, artist: string|null, cover_url: string|null, duration_ms: int|null, preview_url: string|null}>  $sounds
     * @return Collection<int, array{id: string, title: string, artist: string|null, cover_url: string|null, duration_ms: int|null, preview_url: string|null}>
     */
    private static function withCreatorPictures(SocialAccount $account, Collection $sounds): Collection
    {
        $missing = $sounds
            ->filter(fn (array $sound): bool => blank($sound['cover_url']) && ! Cache::has(self::pictureCacheKey($sound['id'])))
            ->pluck('id');

        if ($missing->isNotEmpty()) {
            $responses = Http::pool(fn (Pool $pool): array => $missing
                ->map(fn (string $id) => $pool->as($id)
                    ->connectTimeout(3)
                    ->timeout(5)
                    ->get("{$account->platform->instagramGraphBaseUrl()}/{$id}", [
                        'user_id' => $account->platform_user_id,
                        'access_token' => $account->access_token,
                    ]))
                ->all());

            collect($responses)
                ->filter(fn (mixed $response): bool => $response instanceof Response && $response->successful())
                ->each(fn (Response $response, string $id) => Cache::put(
                    self::pictureCacheKey($id),
                    (string) data_get($response->json(), 'profile_picture_url', ''),
                    now()->addHours(6),
                ));
        }

        return $sounds->map(fn (array $sound): array => [
            ...$sound,
            'cover_url' => $sound['cover_url'] ?? (filled($picture = Cache::get(self::pictureCacheKey($sound['id']))) ? $picture : null),
        ]);
    }

    private static function pictureCacheKey(string $audioId): string
    {
        return "instagram:audio-picture:{$audioId}";
    }

    /**
     * The music search answers with the closest tracks of the catalogue licensed
     * to third parties, related or not, when the searched artist is not in it;
     * only a track naming every searched word is kept.
     *
     * @param  array{title: string, artist: string|null}  $track
     */
    private static function matches(array $track, string $query): bool
    {
        $haystack = Str::lower(Str::ascii("{$track['title']} {$track['artist']}"));

        return collect(preg_split('/\s+/', Str::lower(Str::ascii($query))) ?: [])
            ->filter()
            ->every(fn (string $word): bool => str_contains($haystack, $word));
    }

    /**
     * @return array{id: string, title: string, artist: string|null, cover_url: string|null, duration_ms: int|null, preview_url: string|null}
     */
    private static function present(mixed $track): array
    {
        $duration = data_get($track, 'duration_in_ms');
        $username = data_get($track, 'ig_username');

        return [
            'id' => (string) data_get($track, 'audio_id', data_get($track, 'id', '')),
            'title' => (string) data_get($track, 'title', ''),
            'artist' => data_get($track, 'display_artist') ?? (filled($username) ? (string) $username : null),
            'cover_url' => data_get($track, 'cover_artwork_thumbnail_uri')
                ?? data_get($track, 'cover_artwork_thumbnail_url')
                ?? data_get($track, 'profile_picture_url'),
            'duration_ms' => is_numeric($duration) ? (int) $duration : null,
            'preview_url' => data_get($track, 'download_url'),
        ];
    }
}
