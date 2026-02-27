<?php
session_start();
include "includes/db.php";

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // We call it 'username' now because it could be an Email OR a Body Number
    $input_user = mysqli_real_escape_string($conn, $_POST['username']); 
    $password = $_POST['password'];

    // 1️⃣ Single Query to users table (covers Admin, Driver, and Passenger)
    $query = "SELECT * FROM users WHERE username='$input_user'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        // 2️⃣ Verify Password
        if (password_verify($password, $user['password'])) {
            // Login Success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // 3️⃣ Redirect based on Role
            if ($user['role'] === 'admin') {
                header("Location: admin_panel.php");
            } elseif ($user['role'] === 'driver') {
                header("Location: driver_dashboard.php");
            } else {
                header("Location: dashboard.php"); // For passengers
            }
            exit;
        } else {
            $message = "Incorrect password.";
        }
    } else {
        $message = "User not found. Please check your Email or Body Number.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | QR Transport</title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Small inline style to ensure the text input looks good */
        input[type="text"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
<div class="auth-container">

    <div class="auth-bg">
        <h1>Manage your fleet<br>with precision.</h1>
    </div>

    <div class="auth-card">
        <h2>Welcome back!</h2>

        <?php if ($message): ?>
            <p class="error" style="color: red; background: #fee; padding: 10px; border-radius: 5px; text-align: center;">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <input type="text" name="username" placeholder="Email or Body Number (e.g. BUS-102)" required>
            </div>
            
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit" style="margin-top: 20px;">LOG IN</button>
        </form>

        <p class="switch">
            Don’t have an account? <a href="register.php">Sign Up</a>
        </p>
    </div>
</div>
</body>
</html>