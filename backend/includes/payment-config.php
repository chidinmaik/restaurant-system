<?php
// Payment Gateway Configuration

// Flutterwave Configuration
// Test API Keys (Sandbox)
define('FLUTTERWAVE_TEST_PUBLIC_KEY', 'FLWPUBK_TEST-c153e9c97003c9a11627adcdc1e46805-X');
define('FLUTTERWAVE_TEST_SECRET_KEY', 'FLWSECK_TEST-9eb4faf11ab305e90366db8f5b0af83d-X');

// Live API Keys (Production)
define('FLUTTERWAVE_LIVE_PUBLIC_KEY', 'FLWPUBK-xxxxxxxx-X');
define('FLUTTERWAVE_LIVE_SECRET_KEY', 'FLWSECK-xxxxxxxx-X');

// Set to true to use live keys, false to use test keys
define('USE_LIVE_PAYMENT', false);

// Site URL (used for callback URLs)
// Make sure this matches your actual site URL
define('SITE_URL', 'http://localhost/rest'); // Change this to your actual site URL

// Payment Statuses
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_PAID', 'paid');
define('PAYMENT_STATUS_FAILED', 'failed');
define('PAYMENT_STATUS_REFUNDED', 'refunded');

// Booking Statuses
define('BOOKING_STATUS_PENDING', 'pending');
define('BOOKING_STATUS_CONFIRMED', 'confirmed');
define('BOOKING_STATUS_CANCELLED', 'cancelled');
define('BOOKING_STATUS_COMPLETED', 'completed');