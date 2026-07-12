<?php

namespace App\Notifications;

use App\Mail\NewBlogCommentSubmittedMail;
use App\Models\BlogComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewBlogCommentSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BlogComment $comment)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): NewBlogCommentSubmittedMail
    {
        return new NewBlogCommentSubmittedMail($this->comment, $notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_blog_comment',
            'comment_id' => $this->comment->id,
            'blog_post_id' => $this->comment->blog_post_id,
            'blog_post_title' => $this->comment->blogPost->title_en,
            'blog_post_title_ar' => $this->comment->blogPost->title_ar,
            'blog_post_title_en' => $this->comment->blogPost->title_en,
        ];
    }
}
