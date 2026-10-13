<?php

declare(strict_types=1);

use App\Enums\PostPlatform\ContentType;
use App\Enums\SocialAccount\Platform;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\Workspace;

function seedUnsavedChangesReel(): Post
{
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['user_id' => $user->id]);
    $workspace->members()->attach($user->id, membershipPivot('member'));
    $user->update(['current_workspace_id' => $workspace->id]);
    $account = SocialAccount::factory()->create(['workspace_id' => $workspace->id, 'platform' => Platform::Instagram]);
    $post = Post::factory()->forAccount($account, ContentType::InstagramReel)->create([
        'user_id' => $user->id,
        'content' => 'Unsaved changes',
        'meta' => [],
    ]);
    test()->actingAs($user);

    return $post;
}

function waitForUnsavedChangesCondition(mixed $page, string $condition): void
{
    $page->script(<<<JS
        (async () => {
            for (let i = 0; i < 300; i++) {
                if ({$condition}) return;
                await new Promise((resolve) => setTimeout(resolve, 50));
            }
        })();
    JS);
}

function waitForUnsavedChangesTestId(mixed $page, string $testId): void
{
    waitForUnsavedChangesCondition($page, "document.querySelector('[data-testid=\"{$testId}\"]')?.getBoundingClientRect().height > 0");
}

test('closing an edited post without changes asks nothing', function () {
    $post = seedUnsavedChangesReel();

    $page = visit(route('app.posts.edit', $post))->resize(1280, 900);
    waitForUnsavedChangesTestId($page, 'instagram-share-to-feed');
    $page->click('@composer-close');
    waitForUnsavedChangesCondition($page, "!document.querySelector('[data-testid=\"post-composer-dialog\"]')");

    $page->assertMissing('@post-composer-dialog')
        ->assertMissing('@unsaved-changes-dialog')
        ->assertNoJavaScriptErrors();
});

test('closing an edited post with unsaved changes asks first, keeps it open on cancel and drops the changes on discard', function () {
    $post = seedUnsavedChangesReel();

    $page = visit(route('app.posts.edit', $post))->resize(1280, 900);
    waitForUnsavedChangesTestId($page, 'instagram-share-to-feed');
    $page->click('@instagram-share-to-feed')
        ->click('@composer-close');
    waitForUnsavedChangesTestId($page, 'unsaved-changes-dialog');

    $page->click('@unsaved-changes-keep');
    waitForUnsavedChangesCondition($page, "!document.querySelector('[data-testid=\"unsaved-changes-dialog\"]')");
    $page->assertVisible('@post-composer-dialog')
        ->click('@composer-close');
    waitForUnsavedChangesTestId($page, 'unsaved-changes-discard');
    $page->click('@unsaved-changes-discard');
    waitForUnsavedChangesCondition($page, "!document.querySelector('[data-testid=\"post-composer-dialog\"]')");

    $page->assertMissing('@post-composer-dialog')->assertNoJavaScriptErrors();
    expect($post->fresh()->meta)->toBe([]);
});

test('saving an edited post closes it without asking', function () {
    $post = seedUnsavedChangesReel();

    $page = visit(route('app.posts.edit', $post))->resize(1280, 900);
    waitForUnsavedChangesTestId($page, 'instagram-share-to-feed');
    $page->click('@instagram-share-to-feed')
        ->click('@composer-save-draft');
    waitForUnsavedChangesCondition($page, "!document.querySelector('[data-testid=\"post-composer-dialog\"]')");

    $page->assertMissing('@unsaved-changes-dialog')->assertNoJavaScriptErrors();
    expect(data_get($post->fresh()->meta, 'share_to_feed'))->toBeFalse();
});
