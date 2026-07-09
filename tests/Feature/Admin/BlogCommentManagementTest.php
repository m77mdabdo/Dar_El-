<?php

namespace Tests\Feature\Admin;

use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\User;
use App\Notifications\BlogCommentStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BlogCommentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    protected function makePost(string $titleEn = 'Post'): BlogPost
    {
        return BlogPost::create([
            'title_ar' => 'مقال', 'title_en' => $titleEn,
            'slug' => 'post-'.uniqid(),
            'body_ar' => 'محتوى', 'body_en' => 'Body content here.',
            'is_published' => true, 'published_at' => now(),
        ]);
    }

    public function test_non_admin_is_forbidden_from_admin_blog_comment_routes(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(Role::findOrCreate('customer', 'web'));
        $comment = BlogComment::create(['blog_post_id' => $this->makePost()->id, 'name' => 'X', 'comment' => 'A comment of sufficient length.', 'status' => 'pending']);

        $this->actingAs($customer)->get(route('admin.blog-comments.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.blog-comments.show', $comment))->assertForbidden();
        $this->actingAs($customer)->patch(route('admin.blog-comments.approve', $comment))->assertForbidden();
    }

    public function test_admin_can_approve_a_pending_comment(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $user = User::factory()->create();
        $post = $this->makePost();
        $comment = BlogComment::create(['blog_post_id' => $post->id, 'user_id' => $user->id, 'name' => $user->name, 'comment' => 'A comment of sufficient length.', 'status' => 'pending']);

        $this->actingAs($admin)->patch(route('admin.blog-comments.approve', $comment))->assertRedirect();

        $comment->refresh();
        $this->assertSame('approved', $comment->status);
        $this->assertNotNull($comment->approved_at);
        $this->assertSame($admin->id, $comment->approved_by);

        Notification::assertSentTo($user, BlogCommentStatusUpdated::class);
        $this->assertDatabaseHas('activity_logs', ['action' => 'approved', 'subject_type' => BlogComment::class, 'subject_id' => $comment->id]);
    }

    public function test_admin_can_reject_with_a_reason(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $user = User::factory()->create();
        $post = $this->makePost();
        $comment = BlogComment::create(['blog_post_id' => $post->id, 'user_id' => $user->id, 'name' => $user->name, 'comment' => 'A comment of sufficient length.', 'status' => 'pending']);

        $this->actingAs($admin)->patch(route('admin.blog-comments.reject', $comment), ['reason' => 'Off-topic content.'])->assertRedirect();

        $comment->refresh();
        $this->assertSame('rejected', $comment->status);
        $this->assertSame('Off-topic content.', $comment->rejection_reason);
        $this->assertSame($admin->id, $comment->rejected_by);

        Notification::assertSentTo($user, BlogCommentStatusUpdated::class);
    }

    public function test_admin_can_soft_delete_a_comment(): void
    {
        $admin = $this->makeAdmin();
        $post = $this->makePost();
        $comment = BlogComment::create(['blog_post_id' => $post->id, 'name' => 'X', 'comment' => 'A comment of sufficient length.', 'status' => 'approved']);

        $this->actingAs($admin)->delete(route('admin.blog-comments.destroy', $comment))->assertRedirect(route('admin.blog-comments.index'));

        $this->assertSoftDeleted('blog_comments', ['id' => $comment->id]);
        $this->actingAs($admin)->get(route('admin.blog-comments.index'))->assertDontSee('A comment of sufficient length.');
    }

    public function test_index_filters_narrow_results_correctly(): void
    {
        $admin = $this->makeAdmin();
        $postA = $this->makePost('Alpha Article');
        $postB = $this->makePost('Beta Article');

        $pending = BlogComment::create(['blog_post_id' => $postA->id, 'name' => 'Pending Person', 'comment' => 'Still pending review here.', 'status' => 'pending']);
        $approved = BlogComment::create(['blog_post_id' => $postB->id, 'name' => 'Approved Person', 'comment' => 'Already approved here.', 'status' => 'approved']);

        $this->actingAs($admin);

        $response = $this->get(route('admin.blog-comments.index', ['status' => 'pending']));
        $response->assertSee('Pending Person')->assertDontSee('Approved Person');

        $response = $this->get(route('admin.blog-comments.index', ['status' => 'approved']));
        $response->assertSee('Approved Person')->assertDontSee('Pending Person');

        $response = $this->get(route('admin.blog-comments.index', ['blog_post_id' => $postA->id]));
        $response->assertSee('Pending Person')->assertDontSee('Approved Person');

        $response = $this->get(route('admin.blog-comments.index', ['search' => 'Beta Article']));
        $response->assertSee('Approved Person')->assertDontSee('Pending Person');

        unset($pending, $approved);
    }

    public function test_show_page_renders_rejection_reason(): void
    {
        $admin = $this->makeAdmin();
        $post = $this->makePost();
        $comment = BlogComment::create([
            'blog_post_id' => $post->id, 'name' => 'X', 'comment' => 'A comment of sufficient length.',
            'status' => 'rejected', 'rejection_reason' => 'Not appropriate for this article.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.blog-comments.show', $comment));

        $response->assertOk();
        $response->assertSee('Not appropriate for this article.');
    }
}
