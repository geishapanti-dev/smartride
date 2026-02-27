<?php
session_start();
include "includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$error = "";

/* Ensure uploads folder exists */
if (!is_dir("uploads")) {
    mkdir("uploads", 0777, true);
}

if (isset($_POST['update_pic'])) {

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

                $update = mysqli_query($conn, 
                    "UPDATE drivers SET profile_pic='$uploadPath' WHERE id='$user_id'"
                );

                if ($update) {
                    $msg = "Profile picture updated successfully!";
                } else {
                    $error = "Database update failed.";
                }

            } else {
                $error = "Upload failed.";
            }
        }
    } else {
        $error = "Please select an image.";
    }
}

/* Get current picture */
$get = mysqli_query($conn, "SELECT profile_pic FROM drivers WHERE id='$user_id'");
$current = mysqli_fetch_assoc($get);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Profile Picture</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="bg-white p-8 rounded-3xl shadow-xl w-full max-w-sm text-center">

    <h2 class="text-xl font-bold mb-6">Update Profile Picture</h2>

    <div class="w-24 h-24 mx-auto rounded-full overflow-hidden border-4 border-gray-200 mb-4">
        <img src="<?php echo htmlspecialchars($current['profile_pic']); ?>" 
             class="w-full h-full object-cover">
    </div>

    <?php if($msg): ?>
        <div class="bg-green-100 text-green-700 p-2 rounded mb-3"><?php echo $msg; ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="bg-red-100 text-red-700 p-2 rounded mb-3"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="profile_pic" required class="mb-4">
        <button type="submit" name="update_pic"
            class="bg-indigo-600 text-white px-6 py-2 rounded-xl hover:bg-indigo-700">
            Update
        </button>
    </form>

</div>

</body>
</html>
