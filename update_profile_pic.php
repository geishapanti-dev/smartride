<?php
session_start();
include "includes/db.php";

/* 🔒 Security Check */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

/* Ensure uploads folder exists */
if (!is_dir("uploads")) {
    mkdir("uploads", 0777, true);
}

/* --- HANDLE FORM SUBMISSION --- */
if (isset($_POST['update_profile'])) {

    // 1. Handle Password Change (Users Table)
    if (!empty($_POST['new_password'])) {
        $new_pass = mysqli_real_escape_string($conn, $_POST['new_password']);
        
        $hashed_password = password_hash($new_pass, PASSWORD_BCRYPT);

        $passUpdate = mysqli_query($conn, "UPDATE users SET password='$hashed_password' WHERE id='$user_id'");
        
        if ($passUpdate) {
            $success = "Password updated! ";
        } else {
            $error = "Failed to update password.";
        }
    }

    // 2. Handle Image Upload (Drivers Table)
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {

        $fileTmp  = $_FILES['profile_pic']['tmp_name'];
        $fileName = $_FILES['profile_pic']['name'];
        $fileSize = $_FILES['profile_pic']['size'];
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowed = ['jpg','jpeg','png','gif'];

        if (!in_array($fileExt, $allowed)) {
            $error = "Invalid image format.";
        } elseif ($fileSize > 2 * 1024 * 1024) {
            $error = "Image must not exceed 2MB.";
        } else {
            $newFileName = "driver_" . $user_id . "_" . time() . "." . $fileExt;
            $uploadPath = "uploads/" . $newFileName;

            if (move_uploaded_file($fileTmp, $uploadPath)) {

                $oldQuery = mysqli_query($conn, "SELECT profile_pic FROM drivers WHERE id='$user_id'");
                $oldData  = mysqli_fetch_assoc($oldQuery);

                if (!empty($oldData['profile_pic']) && file_exists($oldData['profile_pic'])) {
                    unlink($oldData['profile_pic']);
                }

                $picUpdate = mysqli_query($conn, "UPDATE drivers SET profile_pic='$uploadPath' WHERE id='$user_id'");

                if ($picUpdate) {
                    $success .= "Profile picture updated!";
                } else {
                    $error = "Database update for image failed.";
                }

            } else {
                $error = "Upload failed.";
            }
        }
    }

    if (empty($error) && !empty($success)) {
        header("Location: driver_dashboard.php?updated=1");
        exit();
    }
}

/* Get Current Picture for Display */
$get = mysqli_query($conn, "SELECT profile_pic FROM drivers WHERE id='$user_id'");
$current = mysqli_fetch_assoc($get);

$profileImage = !empty($current['profile_pic']) ? $current['profile_pic'] : "uploads/default.png";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

<div class="bg-white p-8 rounded-3xl shadow-xl w-full max-w-sm">

    <div class="text-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">Edit Profile</h2>
        <p class="text-gray-400 text-xs mt-1">Update your photo or password</p>
    </div>

    <div class="w-24 h-24 mx-auto rounded-full overflow-hidden border-4 border-indigo-50 mb-6 shadow-sm">
        <img src="<?php echo htmlspecialchars($profileImage); ?>" class="w-full h-full object-cover">
    </div>

    <?php if($error): ?>
        <div class="bg-red-50 text-red-600 p-3 rounded-xl text-sm mb-4 text-center border border-red-100 font-medium">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Change Photo</label>
            <input type="file" name="profile_pic" 
                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
        </div>

        <div class="h-px bg-gray-100 my-4"></div>

        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Change Password</label>
            <input type="password" name="new_password" placeholder="New password (leave empty to keep current)" 
                   class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3 outline-none transition-all focus:bg-white">
        </div>

        <div class="pt-4">
            <button type="submit" name="update_profile"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-indigo-200 active:scale-95">
                Save Changes
            </button>

            <a href="driver_dashboard.php" class="block text-center text-gray-400 text-sm mt-4 hover:text-gray-600">
                Cancel
            </a>
        </div>
    </form>

</div>

</body>
</html>