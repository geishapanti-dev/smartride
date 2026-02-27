<?php
session_start();
include "includes/db.php";

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']); // Updated to username
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $guardian_number = mysqli_real_escape_string($conn, $_POST['guardian_number']);

    // Check if username/email already exists
    $check = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    if (mysqli_num_rows($check) > 0) {
        $message = "Email is already registered.";
    } else {
        $query = "INSERT INTO users (name, username, password, role, guardian_number)
                  VALUES ('$name', '$username', '$password', 'passenger', '$guardian_number')";
        
        if(mysqli_query($conn, $query)) {
            $message = "Success! <a href='login.php' style='color: #4f46e5; font-weight: bold;'>Click here to login</a>";
        } else {
            $message = "Error during registration. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Transport System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .auth-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">

    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="flex justify-center">
            <div class="auth-gradient p-3 rounded-2xl shadow-lg">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Create your account
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Join our fleet and ride with confidence.
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="bg-white py-8 px-6 shadow-xl rounded-[2rem] border border-gray-100 sm:px-10">
            
            <?php if ($message): ?>
                <div class="mb-4 p-4 rounded-xl text-sm <?php echo strpos($message, 'Success') !== false ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form class="space-y-5" method="POST">
                <div>
                    <label class="block text-sm font-medium text-gray-700 ml-1">Full Name</label>
                    <div class="mt-1">
                        <input name="name" type="text" required placeholder="Juan Dela Cruz"
                               class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 ml-1">Email Address</label>
                    <div class="mt-1">
                        <input name="username" type="email" required placeholder="you@example.com"
                               class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 ml-1">Password</label>
                    <div class="mt-1">
                        <input name="password" type="password" required placeholder="••••••••"
                               class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 ml-1">Guardian Phone Number</label>
                    <div class="mt-1">
                        <input name="guardian_number" type="text" required placeholder="09123456789"
                               class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-4 px-4 border border-transparent rounded-2xl shadow-lg text-sm font-bold text-white auth-gradient hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all active:scale-[0.98]">
                        REGISTER
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="font-semibold text-indigo-600 hover:text-indigo-500 transition">
                        Login here
                    </a>
                </p>
            </div>
        </div>

        <div class="mt-8 text-center text-xs text-gray-400">
            &copy; 2026 QR Transport System. All rights reserved.
        </div>
    </div>

</body>
</html>