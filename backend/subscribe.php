<?php
include 'includes/config.php';

// Initialize $message to avoid undefined variable warning
$message = "Please submit the newsletter form to subscribe.";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $conn = getDbConnection();
        $stmt = $conn->prepare("INSERT INTO newsletter_subscribers (email, subscribed_at) VALUES (?, NOW())");
        $stmt->bind_param("s", $email);
        
        if ($stmt->execute()) {
            $message = "Thank you for subscribing!";
        } else {
            $message = "Error subscribing. Please try again.";
        }
        $stmt->close();
        $conn->close();
    } else {
        $message = "Invalid email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/rooms-list.css">
</head>
<body class="bg-dark text-light">
    <div class="container mt-5">
        <h2 style="font-family: 'Playfair Display', serif;"><?php echo htmlspecialchars($message); ?></h2>
        <a href="index.php" class="btn btn-primary mt-3" style="font-family: 'Roboto', sans-serif;">Back to Home</a>
    </div>
</body>
</html>