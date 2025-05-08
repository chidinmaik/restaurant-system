<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'includes/config.php';
include 'includes/payment-config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Flutterwave API Keys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Flutterwave API Key Test</h3>
                    </div>
                    <div class="card-body">
                        <h4>Current Configuration</h4>
                        <table class="table">
                            <tr>
                                <td>Environment:</td>
                                <td><?php echo USE_LIVE_PAYMENT ? 'LIVE' : 'TEST'; ?></td>
                            </tr>
                            <tr>
                                <td>Public Key:</td>
                                <td>
                                    <?php 
                                    $publicKey = USE_LIVE_PAYMENT ? FLUTTERWAVE_LIVE_PUBLIC_KEY : FLUTTERWAVE_TEST_PUBLIC_KEY;
                                    echo !empty($publicKey) ? substr($publicKey, 0, 10) . '...' : '<span class="text-danger">Not set</span>'; 
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Secret Key:</td>
                                <td>
                                    <?php 
                                    $secretKey = USE_LIVE_PAYMENT ? FLUTTERWAVE_LIVE_SECRET_KEY : FLUTTERWAVE_TEST_SECRET_KEY;
                                    echo !empty($secretKey) ? substr($secretKey, 0, 4) . '...' . substr($secretKey, -4) : '<span class="text-danger">Not set</span>'; 
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Site URL:</td>
                                <td>
                                    <?php echo SITE_URL; ?>
                                    <?php if (strpos(SITE_URL, 'localhost') !== false): ?>
                                        <br><span class="text-warning">Note: Using localhost may cause callback issues</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                        
                        <h4 class="mt-4">Test Payment</h4>
                        <p>Click the button below to test a small payment with Flutterwave:</p>
                        
                        <form action="payment/test-payment.php" method="post">
                            <div class="mb-3">
                                <label for="test_email" class="form-label">Email for Test</label>
                                <input type="email" class="form-control" id="test_email" name="test_email" value="test@example.com" required>
                            </div>
                            <div class="mb-3">
                                <label for="test_amount" class="form-label">Test Amount</label>
                                <input type="number" class="form-control" id="test_amount" name="test_amount" value="1.00" min="1" step="0.01" required>
                                <small class="text-muted">Use a small amount for testing</small>
                            </div>
                            <button type="submit" class="btn btn-primary">Test Flutterwave Payment</button>
                        </form>
                        
                        <div class="alert alert-info mt-4">
                            <h5>Troubleshooting Tips:</h5>
                            <ol>
                                <li>Make sure your API keys are correctly entered in <code>includes/payment-config.php</code></li>
                                <li>Check that <code>SITE_URL</code> is set to your actual website URL</li>
                                <li>For testing, use Flutterwave test cards (e.g., 5531 8866 5214 2950, CVV: 564, Expiry: 09/32, PIN: 3310, OTP: 12345)</li>
                                <li>Check your server's error log for detailed error messages</li>
                                <li>Make sure cURL is enabled on your server</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>