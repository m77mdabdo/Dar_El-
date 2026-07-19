<?php

namespace Tests\Feature\Admin;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryAndBlogPostSeoFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    // ---------------------------------------------------------------
    // Category
    // ---------------------------------------------------------------

    public function test_admin_can_create_a_category_with_seo_fields(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.categories.store'), [
            'name_ar' => 'عبايات', 'name_en' => 'Abayas',
            'meta_title_ar' => 'عنوان ميتا مخصص', 'meta_title_en' => 'Custom Meta Title',
            'meta_description_ar' => 'وصف ميتا مخصص', 'meta_description_en' => 'Custom Meta Description',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', [
            'name_en' => 'Abayas',
            'meta_title_ar' => 'عنوان ميتا مخصص', 'meta_title_en' => 'Custom Meta Title',
            'meta_description_ar' => 'وصف ميتا مخصص', 'meta_description_en' => 'Custom Meta Description',
        ]);
    }

    public function test_admin_can_update_a_category_seo_fields(): void
    {
        $category = Category::create([
            'name_ar' => 'عبايات', 'name_en' => 'Abayas', 'slug' => 'abayas-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->admin())->put(route('admin.categories.update', $category), [
            'name_ar' => $category->name_ar, 'name_en' => $category->name_en,
            'meta_title_ar' => 'عنوان محدث', 'meta_title_en' => 'Updated Title',
            'meta_description_ar' => 'وصف محدث', 'meta_description_en' => 'Updated Description',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'meta_title_ar' => 'عنوان محدث', 'meta_title_en' => 'Updated Title',
            'meta_description_ar' => 'وصف محدث', 'meta_description_en' => 'Updated Description',
        ]);
    }

    public function test_category_seo_fields_are_optional(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.categories.store'), [
            'name_ar' => 'عبايات', 'name_en' => 'Abayas',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['name_en' => 'Abayas', 'meta_title_en' => null]);
    }

    public function test_category_seo_fields_respect_max_length(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.categories.store'), [
            'name_ar' => 'عبايات', 'name_en' => 'Abayas',
            'meta_title_en' => str_repeat('a', 256),
            'meta_description_en' => str_repeat('a', 501),
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors(['meta_title_en', 'meta_description_en']);
    }

    public function test_category_create_form_renders_seo_fields(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.categories.create'));

        $response->assertOk();
        $response->assertSee('name="meta_title_en"', false);
        $response->assertSee('name="meta_title_ar"', false);
        $response->assertSee('name="meta_description_en"', false);
        $response->assertSee('name="meta_description_ar"', false);
    }

    // ---------------------------------------------------------------
    // BlogPost
    // ---------------------------------------------------------------

    public function test_admin_can_create_a_blog_post_with_seo_fields(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.blog.store'), [
            'title_ar' => 'منشور', 'title_en' => 'A Post',
            'body_ar' => 'محتوى', 'body_en' => 'Body content',
            'meta_title_ar' => 'عنوان ميتا مخصص', 'meta_title_en' => 'Custom Meta Title',
            'meta_description_ar' => 'وصف ميتا مخصص', 'meta_description_en' => 'Custom Meta Description',
        ]);

        $response->assertRedirect(route('admin.blog.index'));
        $this->assertDatabaseHas('blog_posts', [
            'title_en' => 'A Post',
            'meta_title_ar' => 'عنوان ميتا مخصص', 'meta_title_en' => 'Custom Meta Title',
            'meta_description_ar' => 'وصف ميتا مخصص', 'meta_description_en' => 'Custom Meta Description',
        ]);
    }

    public function test_admin_can_update_a_blog_post_seo_fields(): void
    {
        $post = BlogPost::create([
            'title_ar' => 'منشور', 'title_en' => 'A Post', 'slug' => 'a-post-'.uniqid(),
            'body_ar' => 'محتوى', 'body_en' => 'Body content',
            'is_published' => false,
        ]);

        $response = $this->actingAs($this->admin())->put(route('admin.blog.update', $post), [
            'title_ar' => $post->title_ar, 'title_en' => $post->title_en,
            'body_ar' => $post->body_ar, 'body_en' => $post->body_en,
            'meta_title_ar' => 'عنوان محدث', 'meta_title_en' => 'Updated Title',
            'meta_description_ar' => 'وصف محدث', 'meta_description_en' => 'Updated Description',
        ]);

        $response->assertRedirect(route('admin.blog.index'));
        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'meta_title_ar' => 'عنوان محدث', 'meta_title_en' => 'Updated Title',
            'meta_description_ar' => 'وصف محدث', 'meta_description_en' => 'Updated Description',
        ]);
    }

    public function test_blog_post_seo_fields_are_optional(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.blog.store'), [
            'title_ar' => 'منشور', 'title_en' => 'A Post',
            'body_ar' => 'محتوى', 'body_en' => 'Body content',
        ]);

        $response->assertRedirect(route('admin.blog.index'));
        $this->assertDatabaseHas('blog_posts', ['title_en' => 'A Post', 'meta_title_en' => null]);
    }

    public function test_blog_post_seo_fields_respect_max_length(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.blog.store'), [
            'title_ar' => 'منشور', 'title_en' => 'A Post',
            'body_ar' => 'محتوى', 'body_en' => 'Body content',
            'meta_title_en' => str_repeat('a', 256),
            'meta_description_en' => str_repeat('a', 501),
        ]);

        $response->assertSessionHasErrors(['meta_title_en', 'meta_description_en']);
    }

    public function test_blog_post_create_form_renders_seo_fields(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.blog.create'));

        $response->assertOk();
        $response->assertSee('name="meta_title_en"', false);
        $response->assertSee('name="meta_title_ar"', false);
        $response->assertSee('name="meta_description_en"', false);
        $response->assertSee('name="meta_description_ar"', false);
    }

    /**
     * Same nullable-datetime-admin-field pattern already found and fixed on
     * Product's offer_ends_at/scheduled_publish_at: an empty datetime-local
     * submission isn't coerced to null before Eloquent's datetime cast hands
     * it to the query builder, so "clear the schedule" throws on real MySQL
     * (SQLite silently accepts it, masking the bug here unless checked
     * explicitly). See [[carbon3_and_nullable_date_gotchas]] memory.
     */
    public function test_admin_can_clear_a_blog_posts_published_at(): void
    {
        $admin = $this->admin();
        $post = BlogPost::create([
            'title_ar' => 'منشور', 'title_en' => 'Editable Post', 'slug' => 'editable-post-'.uniqid(),
            'body_ar' => 'محتوى', 'body_en' => 'Body content',
            'is_published' => true, 'published_at' => '2027-06-01 12:00:00',
        ]);

        $this->actingAs($admin)->put(route('admin.blog.update', $post), [
            'title_ar' => $post->title_ar, 'title_en' => $post->title_en,
            'body_ar' => $post->body_ar, 'body_en' => $post->body_en,
            'published_at' => '',
        ])->assertSessionHasNoErrors();

        $this->assertNull($post->fresh()->published_at);
    }
}
