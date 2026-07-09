<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlogCommentRequest;
use App\Http\Requests\UpdateBlogCommentRequest;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\User;
use App\Notifications\NewBlogCommentSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class BlogCommentController extends Controller
{
    public function store(StoreBlogCommentRequest $request, BlogPost $post): RedirectResponse
    {
        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'name' => $request->user()->name,
            'comment' => $request->input('comment'),
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Notification::send(User::admins(), new NewBlogCommentSubmitted($comment));

        return back()->with('status', __('blog_comments.submitted'));
    }

    public function update(UpdateBlogCommentRequest $request, BlogComment $comment): RedirectResponse
    {
        $comment->update(['comment' => $request->input('comment')]);

        return back()->with('status', __('blog_comments.updated'));
    }

    public function destroy(Request $request, BlogComment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return back()->with('status', __('blog_comments.deleted'));
    }
}
