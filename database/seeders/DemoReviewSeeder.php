<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Approved demo reviews from a small pool of clearly-marked demo customer
 * accounts (demo.reviewer.N@example.com). Uses Eloquent directly rather
 * than ReviewController, so no admin-notification email fires for seeded
 * reviews (that only happens in the controller's store() path). Respects
 * one-review-per-user-per-product even though the DB has no unique
 * constraint for it, by tracking which pairs have already been used.
 */
class DemoReviewSeeder extends Seeder
{
    protected const REVIEWER_COUNT = 12;

    protected const COMMENTS = [
        ['rating' => 5, 'en' => 'Beautiful piece, exactly as pictured. The fabric feels even better in person and the fit was true to size.', 'ar' => 'قطعة جميلة جدًا ومطابقة تمامًا للصورة. القماش أفضل حتى مما توقعت، والمقاس مطابق تمامًا.'],
        ['rating' => 5, 'en' => 'I get compliments every time I wear this. Great quality for the price, will definitely order again.', 'ar' => 'أحصل على إطراء في كل مرة أرتديها. جودة ممتازة مقابل السعر، بالتأكيد سأطلب مرة أخرى.'],
        ['rating' => 4, 'en' => 'Lovely quality overall. Delivery took a bit longer than expected but the piece was worth the wait.', 'ar' => 'جودة رائعة بشكل عام. التوصيل تأخر قليلاً عن المتوقع لكن القطعة تستحق الانتظار.'],
        ['rating' => 5, 'en' => 'Exceeded my expectations. The embroidery detail is stunning and the color is rich in person.', 'ar' => 'فاقت توقعاتي. تفاصيل التطريز رائعة واللون غني جدًا عند الرؤية الفعلية.'],
        ['rating' => 4, 'en' => 'Very happy with this purchase. Runs slightly large so consider sizing down if you\'re between sizes.', 'ar' => 'سعيدة جدًا بهذا الشراء. المقاس واسع قليلاً، لذا ينصح باختيار مقاس أصغر إذا كنتِ مترددة.'],
        ['rating' => 3, 'en' => 'Good piece overall, though the color was slightly different from what I expected on screen.', 'ar' => 'قطعة جيدة بشكل عام، لكن اللون كان مختلفًا قليلاً عما ظهر على الشاشة.'],
        ['rating' => 5, 'en' => 'Perfect for the occasion I bought it for. Comfortable, elegant, and true to the photos.', 'ar' => 'مثالية للمناسبة التي اشتريتها من أجلها. مريحة وأنيقة ومطابقة للصور تمامًا.'],
        ['rating' => 4, 'en' => 'Great addition to my wardrobe. The fabric quality feels premium and well worth the price.', 'ar' => 'إضافة رائعة لخزانتي. جودة القماش تبدو فاخرة وتستحق السعر تمامًا.'],
        ['rating' => 5, 'en' => 'This is my second order from Dar El Jamila and it did not disappoint. Fast shipping too.', 'ar' => 'هذا طلبي الثاني من دار الجميلة ولم يخيّب ظني. الشحن كان سريعًا أيضًا.'],
        ['rating' => 3, 'en' => 'Nice piece but I wish there were more color options available for it.', 'ar' => 'قطعة جميلة لكن كنت أتمنى توفر خيارات ألوان أكثر لها.'],
    ];

    public function run(): void
    {
        $customerRole = Role::findOrCreate('customer', 'web');
        $reviewers = $this->demoReviewers($customerRole)->values();
        $reviewerCount = $reviewers->count();

        $products = Product::orderBy('id')->get();

        if ($products->isEmpty()) {
            $this->command?->warn('Skipping reviews — no products found. Run DemoProductSeeder first.');

            return;
        }

        // Deterministic selection (product id, not randomness) so a second
        // run always re-derives the exact same set of pairs and creates
        // zero new rows — "70% of products, 1-4 reviewers each" without
        // ever re-sampling differently between runs.
        foreach ($products as $product) {
            if ($product->id % 10 >= 7) {
                continue; // deterministic ~70% coverage
            }

            $reviewerSlots = ($product->id % 4) + 1;

            for ($slot = 0; $slot < $reviewerSlots; $slot++) {
                $reviewer = $reviewers[($product->id + $slot) % $reviewerCount];

                $exists = Review::where('product_id', $product->id)->where('user_id', $reviewer->id)->exists();

                if ($exists) {
                    continue;
                }

                $comment = self::COMMENTS[($product->id + $slot) % count(self::COMMENTS)];
                $useArabic = ($product->id + $slot) % 2 === 0;

                Review::create([
                    'product_id' => $product->id,
                    'user_id' => $reviewer->id,
                    'name' => $reviewer->name,
                    'rating' => $comment['rating'],
                    'comment' => $useArabic ? $comment['ar'] : $comment['en'],
                    'status' => 'approved',
                    'is_verified_purchase' => ($product->id + $slot) % 5 !== 0,
                    'approved_at' => now(),
                ]);
            }
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function demoReviewers(Role $customerRole): \Illuminate\Support\Collection
    {
        $names = [
            'Sara Ahmed', 'Mona Youssef', 'Nourhan Adel', 'Rana Mostafa', 'Salma Ibrahim',
            'Heba El-Sayed', 'Dina Hassan', 'Aya Mahmoud', 'Yasmin Fathy', 'Reem Nabil',
            'Marwa Kamal', 'Nadine Farouk',
        ];

        return collect($names)->map(function ($name, $i) use ($customerRole) {
            $email = 'demo.reviewer'.($i + 1).'@example.com';
            $user = User::firstWhere('email', $email);

            if (! $user) {
                $user = User::factory()->create(['name' => $name, 'email' => $email]);
            }

            if (! $user->hasRole('customer')) {
                $user->assignRole($customerRole);
            }

            return $user;
        });
    }
}
