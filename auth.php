<?php
// auth.php
session_start();

$config = require __DIR__ . '/config.php';
$valid_pass = $config['admin_password'] ?? 'admin';

if (isset($_POST['password'])) {
    if ($_POST['password'] === $valid_pass) {
        $_SESSION['logged_in'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Incorrect password";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Login Required</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background: #f8f9fa;
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .login-card {
                width: 100%;
                max-width: 400px;
                padding: 2rem;
                border-radius: 1rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                background: white;
            }
        </style>
    </head>

    <body>
        <div class="login-card text-center">
            <div class="mb-4 d-flex justify-content-center">
                <a class="d-flex align-items-center gap-2 text-decoration-none text-dark" href="#">
                    <img src="https://as.aih.edu.au/logo-green.svg" alt="AIHE Logo" style="height: 50px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        class="text-success">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                    </svg>
                    <span class="fs-4 fw-semibold tracking-tight">Enrolment Insights</span>
                </a>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger py-2 small">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Enter Admin Password" required
                        autofocus>
                </div>
                <button type="submit" class="btn btn-success w-100 fw-medium">Access Dashboard</button>
            </form>
            <div class="mt-4 text-muted small opacity-75">Protected Area</div>
        </div>
    </body>

    </html>
    <?php
    exit;
}
?>