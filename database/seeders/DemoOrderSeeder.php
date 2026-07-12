<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Historical demo orders so the admin dashboard's revenue/order-count/
 * top-products widgets (DashboardController) show real numbers instead of
 * zeros. Created directly via Eloquent — Order has no creation-time side
 * effects (no observer, no model-level Mail/Notification dispatch; those
 * only fire from CheckoutController's live checkout flow) — so this never
 * emails a demo customer or touches real inventory. Stock is deliberately
 * left untouched: these represent already-completed historical sales, and
 * DemoProductSeeder already seeded realistic *current* stock levels
 * independently.
 *
 * Idempotent via a deterministic order_number ("DEMO-000001"..), so
 * re-running this seeder updates the same 220 orders rather than growing
 * the count on every run.
 */
class DemoOrderSeeder extends Seeder
{
    protected const ORDER_COUNT = 220;

    protected const GOVERNORATES = [
        ['gov' => 'Cairo', 'city' => 'Nasr City'], ['gov' => 'Giza', 'city' => 'Dokki'],
        ['gov' => 'Alexandria', 'city' => 'Smouha'], ['gov' => 'Qalyubia', 'city' => 'Shubra El-Kheima'],
        ['gov' => 'Sharqia', 'city' => 'Zagazig'], ['gov' => 'Dakahlia', 'city' => 'Mansoura'],
        ['gov' => 'Gharbia', 'city' => 'Tanta'], ['gov' => 'Beheira', 'city' => 'Damanhour'],
    ];

    /** status => weight (out of 100) */
    protected const STATUS_WEIGHTS = [
        'delivered' => 65, 'shipped' => 10, 'processing' => 8, 'pending' => 7, 'cancelled' => 10,
    ];

    public function run(): void
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command?->warn('Skipping orders — no products found. Run DemoProductSeeder first.');

            return;
        }

        $customers = $this->demoCustomers();

        for ($i = 1; $i <= self::ORDER_COUNT; $i++) {
            $customer = $customers->random();
            $items = $products->random(random_int(1, 3));
            $location = self::GOVERNORATES[array_rand(self::GOVERNORATES)];
            $status = $this->weightedStatus();
            $createdAt = now()->subDays(random_int(0, 120))->subHours(random_int(0, 23));

            $subtotal = 0;
            $lineItems = [];

            foreach ($items as $product) {
                $quantity = random_int(1, 2);
                $lineTotal = $product->price * $quantity;
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name_en,
                    'size' => $product->sizes->first()?->size ?? 'Free Size',
                    'price' => $product->price,
                    'quantity' => $quantity,
                ];
            }

            $shippingFee = 60;
            $total = $subtotal + $shippingFee;

            $order = Order::updateOrCreate(
                ['order_number' => 'DEMO-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT)],
                [
                    'user_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'customer_phone' => '01'.random_int(100000000, 299999999),
                    'governorate' => $location['gov'],
                    'city' => $location['city'],
                    'address' => random_int(1, 60).' '.__('Street').' '.$location['city'],
                    'locale' => random_int(0, 1) ? 'ar' : 'en',
                    'subtotal' => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'discount_amount' => 0,
                    'total' => $total,
                    'status' => $status,
                    'payment_method' => 'cod',
                ]
            );

            $order->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

            if (! $order->items()->exists()) {
                foreach ($lineItems as $lineItem) {
                    $order->items()->create($lineItem);
                }
            }
        }
    }

    protected function weightedStatus(): string
    {
        $roll = random_int(1, 100);
        $cumulative = 0;

        foreach (self::STATUS_WEIGHTS as $status => $weight) {
            $cumulative += $weight;

            if ($roll <= $cumulative) {
                return $status;
            }
        }

        return 'delivered';
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function demoCustomers(): \Illuminate\Support\Collection
    {
        $names = [
            'Sara Ahmed', 'Mona Youssef', 'Nourhan Adel', 'Rana Mostafa', 'Salma Ibrahim',
            'Heba El-Sayed', 'Dina Hassan', 'Aya Mahmoud', 'Yasmin Fathy', 'Reem Nabil',
            'Marwa Kamal', 'Nadine Farouk', 'Amira Wael', 'Doaa Sherif', 'Eman Tarek',
            'Farah Nasser', 'Ghada Samir', 'Hanan Ashraf', 'Iman Ragab', 'Jana Zakaria',
        ];

        $customerRole = Role::findOrCreate('customer', 'web');

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
