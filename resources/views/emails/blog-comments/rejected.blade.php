@extends('emails.layouts.master')

@php
    $icon = 'warning-triangle';
    $headerTagline = __('emails.blog_comment_rejected_tagline');
@endphp

@section('content')
    <h2 style="font-size:18px; color:#601526; margin-top:0; font-family:sans-serif;">
        {{ __('emails.blog_comment_rejected_greeting', ['name' => $comment->user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.blog_comment_rejected_intro', ['post' => $comment->blogPost->title_en]) }}
    </p>

    @if ($comment->rejection_reason)
        <p style="font-size:13.5px; line-height:1.7; color:#9C5064; background:#F7EFE4; border-radius:8px; padding:12px 16px; font-family:sans-serif;">
            {{ __('emails.blog_comment_rejected_reason', ['reason' => $comment->rejection_reason]) }}
        </p>
    @endif

    @include('emails.partials.button', ['href' => route('blog.show', $comment->blogPost), 'label' => __('emails.blog_comment_view_button')])
@endsection
