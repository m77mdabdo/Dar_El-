@php
    $djArticleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => trans_field($post, 'title'),
        'description' => \Illuminate\Support\Str::limit((string) trans_field($post, 'excerpt'), 300),
        'image' => $post->cover_image ? [setting_image_url($post->cover_image)] : [],
        'author' => [
            '@type' => 'Person',
            'name' => $post->author_name ?: \App\Models\Setting::get('store_name', config('app.name', 'Dar El Jamila')),
        ],
        'datePublished' => optional($post->published_at)->toAtomString(),
        'dateModified' => $post->updated_at->toAtomString(),
        'mainEntityOfPage' => route('blog.show', $post),
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($djArticleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
