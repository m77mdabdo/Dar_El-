<?php

namespace App\Notifications;

use App\Mail\BlogCommentStatusMail;
use App\Models\BlogComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BlogCommentStatusUpdated extends Notification implements ShouldQueue
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

    public function toMail(object $notifiable): BlogCommentStatusMail
    {
        return new BlogCommentStatusMail($this->comment);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'blog_comment_'.$this->comment->status,
            'comment_id' => $this->comment->id,
            'blog_post_title' => $this->comment->blogPost->title_en,
            'status' => $this->comment->status,
        ];
    }
}
