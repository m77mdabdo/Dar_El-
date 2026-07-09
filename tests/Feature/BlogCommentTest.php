<?php

namespace Tests\Feature;

use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\User;
use App\Notifications\NewBlogCommentSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BlogCommentTest extends TestCase
{
    use RefreshDatabase;

    protected function makePost(): BlogPost
    {
        return BlogPost::create([
            'title_ar' => 'مقال', 'title_en' => 'Post',
            'slug' => 'post-'.uniqid(),
            'body_ar' => 'محتوى', 'body_en' => 'Body content here.',
            'is_published' => true, 'published_at' => now(),
        ]);
    }

    protected function makeAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_guest_cannot_submit_a_comment(): void
    {
        $post = $this->makePost();

        $response = $this->post(route('blog.comments.store', $post), ['comment' => 'A nice comment here.']);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('blog_comments', 0);
    }

    public function test_authenticated_user_can_submit_a_pending_comment(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $post = $this->makePost();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('blog.comments.store', $post), [
            'comment' => 'Really enjoyed reading this article.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('blog_comments', [
            'blog_post_id' => $post->id, 'user_id' => $user->id, 'status' => 'pending',
        ]);

        Notification::assertSentTo($admin, NewBlogCommentSubmitted::class);
    }

    public function test_comment_length_validation(): void
    {
        $post = $this->makePost();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('blog.comments.store', $post), ['comment' => 'hi'])
            ->assertSessionHasErrors('comment');

        $this->actingAs($user)->post(route('blog.comments.store', $post), ['comment' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('comment');

        $this->assertDatabaseCount('blog_comments', 0);
    }

    public function test_user_can_leave_multiple_comments_on_the_same_post(): void
    {
        $post = $this->makePost();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('blog.comments.store', $post), ['comment' => 'First comment on this post.']);
        $this->actingAs($user)->post(route('blog.comments.store', $post), ['comment' => 'Second comment on this post.']);

        $this->assertDatabaseCount('blog_comments', 2);
    }

    public function test_owner_can_edit_a_pending_comment(): void
    {
        $post = $this->makePost();
        $user = User::factory()->create();

        $comment = BlogComment::create(['blog_post_id' => $post->id, 'user_id' => $user->id, 'name' => $user->name, 'comment' => 'Initial comment text.', 'status' => 'pending']);

        $response = $this->actingAs($user)->patch(route('blog.comments.update', $comment), ['comment' => 'Updated comment text.']);

        $response->assertRedirect();
        $this->assertDatabaseHas('blog_comments', ['id' => $comment->id, 'comment' => 'Updated comment text.']);
    }

    public function test_owner_cannot_edit_an_approved_comment(): void
    {
        $post = $this->makePost();
        $user = User::factory()->create();

        $comment = BlogComment::create(['blog_post_id' => $post->id, 'user_id' => $user->id, 'name' => $user->name, 'comment' => 'Initial comment text.', 'status' => 'approved']);

        $response = $this->actingAs($user)->patch(route('blog.comments.update', $comment), ['comment' => 'Trying to edit approved.']);

        $response->assertForbidden();
        $this->assertDatabaseHas('blog_comments', ['id' => $comment->id, 'comment' => 'Initial comment text.']);
    }

    public function test_owner_can_delete_comment_regardless_of_status(): void
    {
        $post = $this->makePost();
        $user = User::factory()->create();

        $comment = BlogComment::create(['blog_post_id' => $post->id, 'user_id' => $user->id, 'name' => $user->name, 'comment' => 'Initial comment text.', 'status' => 'approved']);

        $this->actingAs($user)->delete(route('blog.comments.destroy', $comment))->assertRedirect();

        $this->assertSoftDeleted('blog_comments', ['id' => $comment->id]);
    }

    public function test_another_user_cannot_update_or_delete_someone_elses_comment(): void
    {
        $post = $this->makePost();
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $comment = BlogComment::create(['blog_post_id' => $post->id, 'user_id' => $owner->id, 'name' => $owner->name, 'comment' => 'Owner comment text.', 'status' => 'pending']);

        $this->actingAs($intruder)->patch(route('blog.comments.update', $comment), ['comment' => 'Malicious edit.'])->assertForbidden();
        $this->actingAs($intruder)->delete(route('blog.comments.destroy', $comment))->assertForbidden();

        $this->assertDatabaseHas('blog_comments', ['id' => $comment->id, 'comment' => 'Owner comment text.', 'deleted_at' => null]);
    }

    public function test_only_approved_comments_appear_on_the_blog_post_page(): void
    {
        $post = $this->makePost();

        BlogComment::create(['blog_post_id' => $post->id, 'name' => 'Approved Commenter', 'comment' => 'An approved comment here.', 'status' => 'approved']);
        BlogComment::create(['blog_post_id' => $post->id, 'name' => 'Pending Commenter', 'comment' => 'A pending comment here.', 'status' => 'pending']);
        BlogComment::create(['blog_post_id' => $post->id, 'name' => 'Rejected Commenter', 'comment' => 'A rejected comment here.', 'status' => 'rejected']);

        $response = $this->get(route('blog.show', $post));

        $response->assertSee('Approved Commenter');
        $response->assertDontSee('Pending Commenter');
        $response->assertDontSee('Rejected Commenter');
    }
}
