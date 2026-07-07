<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Setting;

class BlogController extends Controller
{
    public function index()
    {
        $posts = BlogPost::where('is_published', true)
            ->latest('published_at')
            ->paginate(9);

        $heroImage = Setting::get('blog_hero_image', 'https://images.unsplash.com/photo-1646298032905-e7f0df5a751a?w=1600&q=80&auto=format&fit=crop');

        return view('blog.index', compact('posts', 'heroImage'));
    }

    public function show(BlogPost $post)
    {
        abort_unless($post->is_published, 404);

        return view('blog.show', compact('post'));
    }
}
