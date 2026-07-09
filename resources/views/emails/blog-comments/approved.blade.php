@extends('emails.layouts.master')

@php
    $icon = 'check-circle';
    $headerTagline = __('emails.blog_comment_approved_tagline');
@endphp

@section('content')
    <h2 style="font-size:18px; color:#601526; margin-top:0; font-family:sans-serif;">
        {{ __('emails.blog_comment_approved_greeting', ['name' => $comment->user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family:sans-serif;">
        {{ __('emails.blog_comment_approved_intro', ['post' => $comment->blogPost->title_en]) }}
    </p>

    @include('emails.partials.button', ['href' => route('blog.show', $comment->blogPost), 'label' => __('emails.blog_comment_view_button')])
@endsection
