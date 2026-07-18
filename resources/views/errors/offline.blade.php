@extends('errors.minimal')

@section('code', __('general.errors.offline_code'))
@section('title', __('general.errors.offline_title'))
@section('message', __('general.errors.offline_message'))
@section('cta_href', '#')
@section('cta_onclick', 'location.reload(); return false;')
@section('cta_label', __('general.errors.offline_retry'))
