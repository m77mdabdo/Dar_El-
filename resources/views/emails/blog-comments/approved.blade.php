@extends('emails.layouts.master')

@php
    $icon = 'check-circle';
    $headerTagline = __('emails.blog_comment_approved_tagline');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.blog_comment_approved_greeting', ['name' => $comment->user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 8px;">
        {{ __('emails.blog_comment_approved_intro', ['post' => trans_field($comment->blogPost, 'title')]) }}
    </p>

    @include('emails.partials.button', ['href' => route('blog.show', $comment->blogPost), 'label' => __('emails.blog_comment_view_button')])
@endsection
