<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = $request->user()->reviews()->with('product')->latest()->paginate(10);

        return view('account.reviews.index', compact('reviews'));
    }
}
