@extends('emails.layouts.master')

@php
    $icon = 'document';
    $headerTagline = __('emails.admin_tagline');
@endphp

@section('content')
    <p style="font-size:14px; line-height:1.8; color:#5a4448; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align:center; margin:0 0 26px;">
        {{ __('emails.admin_new_blog_comment_intro', ['post' => trans_field($comment->blogPost, 'title')]) }}
    </p>

    @include('emails.partials.button', ['href' => route('admin.blog-comments.show', $comment), 'label' => __('emails.admin_new_blog_comment_button')])
@endsection
