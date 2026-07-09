<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BlogCommentController extends Controller
{
    public function index(Request $request)
    {
        $comments = $request->user()->blogComments()->with('blogPost')->latest()->paginate(10);

        return view('account.blog-comments.index', compact('comments'));
    }
}
