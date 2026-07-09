<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Setting;

class BlogController extends Controller
{
    public function index()
    {
        $posts = BlogPost::with('approvedComments')
            ->where('is_published', true)
            ->latest('published_at')
            ->paginate(9);

        $heroImage = Setting::get('blog_hero_image', 'https://images.unsplash.com/photo-1646298032905-e7f0df5a751a?w=1600&q=80&auto=format&fit=crop');

        return view('blog.index', compact('posts', 'heroImage'));
    }

    public function show(BlogPost $post)
    {
        abort_unless($post->is_published, 404);

        $post->load('approvedComments.user');

        $userComments = auth()->check()
            ? $post->comments()->where('user_id', auth()->id())->latest()->get()
            : collect();

        return view('blog.show', compact('post', 'userComments'));
    }
}
