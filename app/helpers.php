<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

if (! function_exists('trans_field')) {
    /**
     * Resolve a bilingual model field (e.g. "name") to its "_ar"/"_en" suffixed
     * column based on the current app locale.
     */
    function trans_field(Model $model, string $field): ?string
    {
        $suffix = app()->getLocale() === 'ar' ? '_ar' : '_en';

        return $model->{$field.$suffix} ?? $model->{$field.'_en'} ?? null;
    }
}

if (! function_exists('setting_image_url')) {
    /**
     * Resolve a Setting-stored image value to a displayable URL: a full
     * https:// URL (the Unsplash fallback default) is used as-is, while a
     * local upload path is prefixed with the public storage URL.
     */
    function setting_image_url(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Str::startsWith($value, ['http://', 'https://']) ? $value : asset('storage/'.$value);
    }
}
