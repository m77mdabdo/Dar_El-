<?php

return [
    // Shared layout
    'footer_contact' => 'Need help? Contact us at :email',
    'footer_whatsapp' => 'WhatsApp: :number',
    'footer_visit_website' => 'Visit our website',
    'footer_copyright' => 'Dar El Jamila. All rights reserved.',
    'footer_disclaimer' => 'This is an automated message from Dar El Jamila. Please do not reply directly to this email.',
    'ignore_note' => "If you did not request this email, please ignore it.",

    // OTP
    'otp_subject' => 'Your Dar El Jamila verification code',
    'otp_greeting' => 'Hello :name',
    'otp_intro' => 'Use the code below to verify your account.',
    'otp_expires' => 'This code will expire in :minutes minutes.',
    'otp_security_note' => 'If you did not request this code, you can safely ignore this email.',

    // Password reset
    'password_reset_subject' => 'Reset your Dar El Jamila password',
    'password_reset_tagline' => 'Password Reset',
    'password_reset_greeting' => 'Hello :name',
    'password_reset_intro' => 'We received a request to reset your password. Click the button below to choose a new one.',
    'password_reset_button' => 'Reset My Password',
    'password_reset_expires' => 'This link will expire in :minutes minutes.',

    // Login alert
    'login_alert_subject' => 'New login to your account',
    'login_alert_tagline' => 'Account Security',
    'login_alert_greeting' => 'Hello :name',
    'login_alert_intro' => 'We noticed a new login to your account.',
    'login_alert_email' => 'Email',
    'login_alert_time' => 'Time',
    'login_alert_ip' => 'IP Address',
    'login_alert_device' => 'Device',
    'login_alert_browser' => 'Browser',
    'login_alert_provider' => 'Signed in with',
    'login_alert_ok_note' => 'If this was you, no action is needed.',
    'login_alert_not_you_note' => 'If this was not you, please reset your password immediately.',
    'login_alert_reset_button' => 'Reset Password',

    // Orders
    'order_confirmation_subject' => 'Your Dar El Jamila order :number is confirmed',
    'invoice_ready_subject' => 'Your invoice for order :number is ready',
    'order_confirmation_tagline' => 'Order Confirmation',
    'order_confirmation_greeting' => 'Thank you for your order, :name!',
    'order_confirmation_intro' => 'Your order :number has been placed and your invoice #:invoice is attached to this email.',
    'order_confirmation_intro_no_invoice' => 'Your order :number has been placed. We will email your invoice separately shortly.',
    'order_item' => 'Item',
    'order_variant' => 'Size',
    'order_qty' => 'Qty',
    'order_price' => 'Price',
    'order_total' => 'Total',
    'order_subtotal' => 'Subtotal',
    'order_shipping_fee' => 'Shipping',
    'order_discount' => 'Discount',
    'order_grand_total' => 'Grand Total',
    'order_payment_method' => 'Payment Method',
    'order_payment_method_cod' => 'Cash on Delivery',
    'order_shipping_address' => 'Shipping Address',
    'order_details_title' => 'Order Details',
    'order_view_button' => 'View Order',
    'order_download_invoice_button' => 'Download Invoice',

    'order_status_subject' => 'Your order :number has been updated',
    'order_status_tagline' => 'Order Update',
    'order_status_greeting' => 'Hello :name',
    'order_status_intro' => 'Your order :number status is now:',
    'order_status_thanks' => 'Thank you for shopping with Dar El Jamila!',

    // Carts
    'cart_reminder_subject' => 'Your Dar El Jamila cart is waiting for you',
    'cart_reminder_tagline' => 'Your Cart',
    'cart_reminder_greeting' => 'Hello :name',
    'cart_reminder_intro' => 'Your Dar El Jamila cart is still waiting for you. Complete your order before your selected items sell out.',

    // Reviews
    'review_approved_subject' => 'Your review has been approved',
    'review_approved_tagline' => 'Review Approved',
    'review_approved_greeting' => 'Hello :name',
    'review_approved_intro' => 'Great news! Your review for :product has been approved and is now visible on our site.',
    'review_rejected_subject' => 'Update on your submitted review',
    'review_rejected_tagline' => 'Review Update',
    'review_rejected_greeting' => 'Hello :name',
    'review_rejected_intro' => 'Your review for :product was not approved.',
    'review_rejected_reason' => 'Reason: :reason',
    'review_view_button' => 'View Product',

    // Blog comments
    'blog_comment_approved_subject' => 'Your comment has been approved',
    'blog_comment_approved_tagline' => 'Comment Approved',
    'blog_comment_approved_greeting' => 'Hello :name',
    'blog_comment_approved_intro' => 'Great news! Your comment on ":post" has been approved and is now visible.',
    'blog_comment_rejected_subject' => 'Update on your submitted comment',
    'blog_comment_rejected_tagline' => 'Comment Update',
    'blog_comment_rejected_greeting' => 'Hello :name',
    'blog_comment_rejected_intro' => 'Your comment on ":post" was not approved.',
    'blog_comment_rejected_reason' => 'Reason: :reason',
    'blog_comment_view_button' => 'View Post',

    // Admin notifications
    'admin_tagline' => 'Admin Notification',
    'admin_user_welcome_subject' => 'Your Dar El Jamila staff account has been created',
    'admin_user_welcome_greeting' => 'Welcome, :name',
    'admin_user_welcome_intro' => 'A :role account has been created for you on the Dar El Jamila admin dashboard.',
    'admin_user_welcome_credentials_title' => 'Your Login Details',
    'admin_user_welcome_password' => 'Temporary Password',
    'admin_user_welcome_note' => 'For your security, please log in and change this password as soon as possible.',
    'admin_user_welcome_button' => 'Log In Now',
    'admin_new_order_subject' => 'New order received: :number',
    'admin_new_order_intro' => 'A new order has been placed.',
    'admin_new_order_customer' => 'Customer',
    'admin_new_order_total' => 'Total',
    'admin_new_order_button' => 'View Order',

    'admin_new_review_subject' => 'New product review submitted',
    'admin_new_review_intro' => 'A new review has been submitted for :product and is awaiting moderation.',
    'admin_new_review_rating' => 'Rating',
    'admin_new_review_button' => 'Review It',

    'admin_new_blog_comment_subject' => 'New blog comment submitted',
    'admin_new_blog_comment_intro' => 'A new comment has been submitted on ":post" and is awaiting moderation.',
    'admin_new_blog_comment_button' => 'Review It',

    'admin_low_stock_subject' => 'Low stock alert: :product',
    'admin_low_stock_intro' => ':product (size :size) is running low on stock.',
    'admin_low_stock_remaining' => 'Remaining stock',
    'admin_low_stock_button' => 'View Product',

    'admin_out_of_stock_subject' => 'Out of stock alert: :product',
    'admin_out_of_stock_intro' => ':product (size :size) is now out of stock.',
    'admin_out_of_stock_button' => 'View Product',

    'admin_new_customer_subject' => 'New customer registered: :name',
    'admin_new_customer_intro' => ':name has just created an account.',
    'admin_new_customer_email' => 'Email',
    'admin_new_customer_phone' => 'Phone',
    'admin_new_customer_button' => 'View Customer',
    'admin_customer_details_title' => 'Customer Details',

    'admin_new_contact_message_subject' => 'New contact form message received',
    'admin_new_contact_message_intro' => 'A new message has been submitted through the contact form.',
    'admin_new_contact_message_name' => 'Name',
    'admin_new_contact_message_email' => 'Email',
    'admin_new_contact_message_subject_label' => 'Subject',
    'admin_new_contact_message_button' => 'View Messages',
    'admin_message_details_title' => 'Message Details',
    'admin_message_body_title' => 'Message',

    // Payment (template only — not wired to any trigger yet)
    'payment_success_subject' => 'Payment received for order :number',
    'payment_success_tagline' => 'Payment Confirmed',
    'payment_success_greeting' => 'Hello :name',
    'payment_success_intro' => 'We have received your payment for order :number.',
    'payment_success_amount' => 'Amount Paid',
    'payment_success_button' => 'View Order',

    'payment_failed_subject' => 'Payment failed for order :number',
    'payment_failed_tagline' => 'Payment Failed',
    'payment_failed_greeting' => 'Hello :name',
    'payment_failed_intro' => 'Unfortunately, your payment for order :number could not be completed.',
    'payment_failed_note' => 'Please try again or use a different payment method.',
    'payment_failed_button' => 'Retry Payment',

    // Wishlist reminder (template only — not wired to any trigger yet)
    'wishlist_reminder_subject' => 'Items in your wishlist are waiting for you',
    'wishlist_reminder_tagline' => 'Your Wishlist',
    'wishlist_reminder_greeting' => 'Hello :name',
    'wishlist_reminder_intro' => 'You still have items saved in your wishlist. Take another look before they sell out.',
    'wishlist_reminder_button' => 'View Wishlist',

    // Back in stock (template only — not wired to any trigger yet)
    'back_in_stock_subject' => ':product is back in stock',
    'back_in_stock_tagline' => 'Back In Stock',
    'back_in_stock_greeting' => 'Hello :name',
    'back_in_stock_intro' => 'Good news! :product is back in stock and ready to order.',
    'back_in_stock_button' => 'Shop Now',

    // Newsletter welcome (template only — not wired to any trigger yet)
    'newsletter_welcome_subject' => 'Welcome to the Dar El Jamila newsletter',
    'newsletter_welcome_tagline' => 'Newsletter',
    'newsletter_welcome_greeting' => 'Hello',
    'newsletter_welcome_intro' => 'Thank you for subscribing! You will now be the first to hear about new collections, exclusive offers, and styling tips from Dar El Jamila.',
    'newsletter_welcome_button' => 'Explore the Shop',
];
