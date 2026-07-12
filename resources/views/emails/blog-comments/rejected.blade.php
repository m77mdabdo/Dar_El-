@extends('emails.layouts.master')

@php
    $icon = 'warning-triangle';
    $headerTagline = __('emails.blog_comment_rejected_tagline');
@endphp

@section('content')
    <h2 style="font-size:20px; color:#601526; margin:0 0 4px; font-family: Georgia, 'Times New Roman', serif; text-align:center;">
        {{ __('emails.blog_comment_rejected_greeting', ['name' => $comment->user->name]) }}
    </h2>

    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 22px;">
        {{ __('emails.blog_comment_rejected_intro', ['post' => trans_field($comment->blogPost, 'title')]) }}
    </p>

    @if ($comment->rejection_reason)
        <p style="font-size:13.5px; line-height:1.7; color:#9C5064; background:#F7EFE4; border-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}:3px solid #601526; border-radius:10px; padding:14px 18px; margin:0 0 22px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
            {{ __('emails.blog_comment_rejected_reason', ['reason' => $comment->rejection_reason]) }}
        </p>
    @endif

    @include('emails.partials.button', ['href' => route('blog.show', $comment->blogPost), 'label' => __('emails.blog_comment_view_button')])
@endsection
