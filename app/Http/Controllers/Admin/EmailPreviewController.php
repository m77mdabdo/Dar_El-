<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AbandonedCartReminderMail;
use App\Mail\BlogCommentStatusMail;
use App\Mail\InvoiceMail;
use App\Mail\LoginAlertMail;
use App\Mail\NewBlogCommentSubmittedMail;
use App\Mail\NewContactMessageMail;
use App\Mail\NewCustomerRegisteredMail;
use App\Mail\NewOrderPlacedMail;
use App\Mail\NewProductReviewSubmittedMail;
use App\Mail\NewsletterWelcomeMail;
use App\Mail\OrderStatusMail;
use App\Mail\OtpMail;
use App\Mail\PaymentFailedMail;
use App\Mail\PaymentSuccessMail;
use App\Mail\ProductBackInStockMail;
use App\Mail\ProductLowStockMail;
use App\Mail\ProductOutOfStockMail;
use App\Mail\ReviewStatusMail;
use App\Mail\WishlistReminderMail;
use App\Models\BackInStockSubscription;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Invoice;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Review;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Response;

class EmailPreviewController extends Controller
{
    /**
     * Local-only, read-only rendering of every email template with sample
     * data — never sends anything. Gated by both the admin route group and
     * an explicit local-environment check for defense in depth.
     */
    public function show(string $type): Response
    {
        abort_unless(app()->environment('local'), 404);

        $mailable = match ($type) {
            'otp' => new OtpMail($this->sampleUser(), '482913', 10),
            'login-alert' => new LoginAlertMail($this->sampleUser(), '41.32.10.5', 'iPhone 15', 'Safari', now()),
            'order-confirmation' => new InvoiceMail($this->sampleOrder(), $this->sampleInvoice()),
            'order-status-updated' => new OrderStatusMail($this->sampleOrder()),
            'cart-abandoned-reminder' => new AbandonedCartReminderMail($this->sampleCart()),
            'review-approved' => new ReviewStatusMail($this->sampleReview('approved')),
            'review-rejected' => new ReviewStatusMail($this->sampleReview('rejected', 'Contains inappropriate language.')),
            'blog-comment-approved' => new BlogCommentStatusMail($this->sampleComment('approved')),
            'blog-comment-rejected' => new BlogCommentStatusMail($this->sampleComment('rejected', 'This looked like spam.')),
            'admin-new-order' => new NewOrderPlacedMail($this->sampleOrder(), $this->sampleUser()),
            'admin-new-review' => new NewProductReviewSubmittedMail($this->sampleReview('pending'), $this->sampleUser()),
            'admin-new-blog-comment' => new NewBlogCommentSubmittedMail($this->sampleComment('pending'), $this->sampleUser()),
            'admin-low-stock' => new ProductLowStockMail(...$this->sampleProductWithSize(2), admin: $this->sampleUser()),
            'admin-out-of-stock' => new ProductOutOfStockMail(...$this->sampleProductWithSize(0), admin: $this->sampleUser()),
            'admin-new-customer' => new NewCustomerRegisteredMail($this->sampleUser(), $this->sampleUser()),
            'admin-new-contact-message' => new NewContactMessageMail($this->sampleContactMessage(), $this->sampleUser()),
            'payment-success' => new PaymentSuccessMail($this->sampleOrder(), 1250),
            'payment-failed' => new PaymentFailedMail($this->sampleOrder(), 'Card declined by bank.'),
            'wishlist-reminder' => new WishlistReminderMail($this->sampleUser(), $this->sampleWishlists()),
            'back-in-stock' => (function () {
                [$product, $size] = $this->sampleProductWithSize(4);
                $subscription = BackInStockSubscription::make(['email' => 'layla@example.com']);

                return new ProductBackInStockMail($subscription, $product, $size);
            })(),
            'newsletter-welcome' => new NewsletterWelcomeMail($this->sampleNewsletterSubscriber()),
            default => abort(404),
        };

        return response($mailable->render());
    }

    protected function sampleUser(): User
    {
        return User::latest()->first() ?? User::make(['name' => 'Layla Hassan', 'email' => 'layla@example.com']);
    }

    protected function sampleProduct(): Product
    {
        if ($product = Product::latest()->first()) {
            return $product;
        }

        $product = Product::make([
            'name_en' => 'Silk Abaya', 'name_ar' => 'عباية حرير', 'price' => 1200, 'slug' => 'preview-silk-abaya',
        ]);
        $product->id = 999999;
        $product->setRelation('category', Category::make(['name_en' => 'Abayas', 'name_ar' => 'عبايات']));

        return $product;
    }

    /**
     * @return array{0: Product, 1: ProductSize}
     */
    protected function sampleProductWithSize(int $stock): array
    {
        $product = $this->sampleProduct();
        $size = ProductSize::make(['size' => 'M', 'stock' => $stock]);
        $size->setRelation('product', $product);

        return [$product, $size];
    }

    protected function sampleOrder(): Order
    {
        $order = Order::with('items.product')->latest()->first();

        if ($order) {
            return $order;
        }

        $product = $this->sampleProduct();
        $order = Order::make([
            'order_number' => 'ORD-PREVIEW-1', 'customer_name' => 'Layla Hassan', 'customer_email' => 'layla@example.com',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => '123 Sample St',
            'subtotal' => 1200, 'shipping_fee' => 50, 'discount_amount' => 0, 'total' => 1250,
            'status' => 'shipped', 'payment_method' => Order::PAYMENT_METHOD_COD,
        ]);
        $order->id = 999999;
        $item = OrderItem::make(['product_name' => $product->name_en, 'size' => 'M', 'price' => 1200, 'quantity' => 1]);
        $item->setRelation('product', $product);
        $order->setRelation('items', collect([$item]));

        return $order;
    }

    protected function sampleInvoice(): Invoice
    {
        return Invoice::latest()->first() ?? Invoice::make(['invoice_number' => 'INV-PREVIEW-1', 'issued_at' => now()]);
    }

    protected function sampleCart(): Cart
    {
        $cart = Cart::with(['user', 'items'])->latest()->first();

        if ($cart) {
            return $cart;
        }

        $cart = Cart::make(['status' => 'abandoned', 'subtotal' => 1200, 'total' => 1200, 'items_count' => 1, 'last_activity_at' => now()->subHours(3)]);
        $cart->setRelation('user', $this->sampleUser());
        $item = CartItem::make(['product_name' => 'Silk Abaya', 'variant_snapshot' => ['size' => 'M'], 'quantity' => 1, 'price' => 1200, 'total' => 1200]);
        $cart->setRelation('items', collect([$item]));

        return $cart;
    }

    protected function sampleReview(string $status, ?string $rejectionReason = null): Review
    {
        $review = Review::with('user', 'product')->where('status', $status)->latest()->first();

        if ($review) {
            return $review;
        }

        $review = Review::make(['name' => 'Layla Hassan', 'rating' => 5, 'comment' => 'Beautiful quality!', 'status' => $status, 'rejection_reason' => $rejectionReason]);
        $review->id = 999999;
        $review->setRelation('user', $this->sampleUser());
        $review->setRelation('product', $this->sampleProduct());

        return $review;
    }

    protected function sampleBlogPost(): BlogPost
    {
        if ($post = BlogPost::latest()->first()) {
            return $post;
        }

        $post = BlogPost::make(['title_en' => 'Styling Tips', 'title_ar' => 'نصائح التنسيق', 'slug' => 'preview-styling-tips']);
        $post->id = 999999;

        return $post;
    }

    protected function sampleComment(string $status, ?string $rejectionReason = null): BlogComment
    {
        $comment = BlogComment::with('user', 'blogPost')->where('status', $status)->latest()->first();

        if ($comment) {
            return $comment;
        }

        $comment = BlogComment::make(['name' => 'Layla Hassan', 'comment' => 'Loved this post!', 'status' => $status, 'rejection_reason' => $rejectionReason]);
        $comment->id = 999999;
        $comment->setRelation('user', $this->sampleUser());
        $comment->setRelation('blogPost', $this->sampleBlogPost());

        return $comment;
    }

    protected function sampleContactMessage(): ContactMessage
    {
        return ContactMessage::latest()->first() ?? ContactMessage::make([
            'name' => 'Visitor Name', 'email' => 'visitor@example.com',
            'subject' => 'Question about sizing', 'message' => 'Hi, do you ship to Alexandria?',
        ]);
    }

    protected function sampleNewsletterSubscriber(): NewsletterSubscriber
    {
        return NewsletterSubscriber::latest()->first() ?? NewsletterSubscriber::make(['email' => 'subscriber@example.com']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Wishlist>
     */
    protected function sampleWishlists(): \Illuminate\Support\Collection
    {
        $wishlists = Wishlist::with('product')->latest()->take(3)->get();

        if ($wishlists->isNotEmpty()) {
            return $wishlists;
        }

        $wishlist = Wishlist::make([]);
        $wishlist->setRelation('product', $this->sampleProduct());

        return collect([$wishlist]);
    }
}
