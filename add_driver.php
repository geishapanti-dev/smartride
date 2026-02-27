<?php
session_start();
include "includes/db.php";

/* 🔒 Admin protection */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$msg = "";
$error = "";
$qrImage = "";

/* Ensure uploads folder exists */
if (!is_dir("uploads")) {
    mkdir("uploads", 0777, true);
}

/* ➕ Add Driver */
if (isset($_POST['add_driver'])) {

    $name        = mysqli_real_escape_string($conn, $_POST['name']);
    $body_number = mysqli_real_escape_string($conn, $_POST['body_number']);
    $vehicle     = mysqli_real_escape_string($conn, $_POST['vehicle']);
    $contact     = mysqli_real_escape_string($conn, $_POST['contact']);
    $address     = mysqli_real_escape_string($conn, $_POST['address']);
    $corporation = mysqli_real_escape_string($conn, $_POST['corporation']);

    $defaultPassword = '1234';
    $password = password_hash($defaultPassword, PASSWORD_DEFAULT);

    /* Check if Body Number already exists */
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$body_number'");
    if (mysqli_num_rows($check) > 0) {
        $error = "A driver with this Body Number already exists.";
    } else {

        /* =============================
           PROFILE PICTURE UPLOAD
        ============================== */
        $profilePicPath = "";

        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {

            $fileTmp  = $_FILES['profile_pic']['tmp_name'];
            $fileName = $_FILES['profile_pic']['name'];
            $fileSize = $_FILES['profile_pic']['size'];
            $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($fileExt, $allowed)) {
                $error = "Invalid image format. Only JPG, JPEG, PNG, GIF allowed.";
            } elseif ($fileSize > 2 * 1024 * 1024) { // 2MB limit
                $error = "Image size must not exceed 2MB.";
            } else {
                $newFileName = "driver_" . time() . "_" . rand(1000,9999) . "." . $fileExt;
                $uploadPath = "uploads/" . $newFileName;

                if (move_uploaded_file($fileTmp, $uploadPath)) {
                    $profilePicPath = $uploadPath;
                } else {
                    $error = "Failed to upload profile picture.";
                }
            }
        } else {
            $error = "Profile picture is required.";
        }

        /* If no upload error, continue inserting */
        if (!$error) {

            /* 1️⃣ Insert into USERS table */
            $userSql = "
                INSERT INTO users (name, username, password, role)
                VALUES ('$name', '$body_number', '$password', 'driver')
            ";

            if (mysqli_query($conn, $userSql)) {

                $user_id = mysqli_insert_id($conn);

                /* 2️⃣ Generate QR code */
                $qrCodeString = "DRIVER_" . $user_id;

                /* 3️⃣ Insert into DRIVERS table */
                $driverSql = "
                    INSERT INTO drivers 
                    (id, name, body_number, vehicle, contact, address, corporation, qr_code, profile_pic)
                    VALUES 
                    ('$user_id', '$name', '$body_number', '$vehicle', '$contact', '$address', '$corporation', '$qrCodeString', '$profilePicPath')
                ";

                if (mysqli_query($conn, $driverSql)) {

                    $qrImage = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrCodeString);

                    $msg = "Driver added! <br>
                            Login ID (Body No): <strong>$body_number</strong> <br>
                            Password: <strong>1234</strong>";

                } else {
                    $error = "Error adding to drivers table: " . mysqli_error($conn);
                }

            } else {
                $error = "System error adding user.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Driver | Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-bg: #222d3a;
            --main-bg: #1a1c24;
            --card-bg: #f8f9fa;
            --text-dark: #333;
            --accent-blue: #3498db;
        }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--main-bg); color: white; display: flex; }
        .sidebar { width: 240px; background: var(--sidebar-bg); height: 100vh; padding: 20px 0; position: fixed; }
        .sidebar-brand { padding: 0 25px 30px; font-size: 1.2rem; font-weight: bold; display: flex; gap: 10px; align-items: center; }
        .nav-item { display: flex; gap: 15px; padding: 12px 25px; color: #a0a5b1; text-decoration: none; }
        .nav-item:hover, .nav-item.active { background: #34495e; color: white; }
        .main-content { margin-left: 240px; padding: 30px; width: calc(100% - 240px); display: flex; justify-content: center; }
        .form-card { background: var(--card-bg); color: var(--text-dark); padding: 40px; border-radius: 12px; max-width: 500px; width: 100%; }
        .input-group { margin-bottom: 15px; }
        .input-group label { font-weight: 600; display: block; margin-bottom: 5px; }
        .input-group input, .input-group select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ddd; box-sizing: border-box; }
        .submit-btn { background: var(--accent-blue); color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        img { margin-top: 10px; border: 1px solid #ddd; padding: 5px; background: #fff; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand"><i class="fas fa-bus"></i> Ser Dashboard</div>
    <a href="admin_panel.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="add_driver.php" class="nav-item active"><i class="fas fa-user-plus"></i> Add Driver</a>
    <a href="admin_trips.php" class="nav-item"><i class="fas fa-history"></i> Trip History</a>
    <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-content">
    <div class="form-card">
        <h2>Register New Driver</h2>

        <?php if ($msg): ?>
            <div class="alert alert-success">
                <?php echo $msg; ?><br><br>
                <strong>Driver QR Code:</strong><br>
                <img src="<?php echo $qrImage; ?>" alt="Driver QR">
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <label>Full Name</label>
                <input type="text" name="name" required>
            </div>

            <div class="input-group">
                <label>Body Number (Login ID)</label>
                <input type="text" name="body_number" required>
            </div>

            <div class="input-group">
                <label>Corporation / Operator</label>
                <input type="text" name="corporation" required>
            </div>

            <div class="input-group">
                <label>Vehicle Type</label>
                <select name="vehicle" required>
                    <option value="">Select Vehicle</option>
                    <option>Bus</option>
                    <option>Van</option>
                    <option>Coaster</option>
                    <option>Jeep</option>
                    <option>Tricycle</option>
                </select>
            </div>

            <div class="input-group">
                <label>Contact Number</label>
                <input type="text" name="contact" required>
            </div>

            <div class="input-group">
                <label>Address</label>
                <input type="text" name="address" required>
            </div>

            <div class="input-group">
                <label>Profile Picture</label>
                <input type="file" name="profile_pic" accept="image/*" required>
            </div>

            <button type="submit" name="add_driver" class="submit-btn">
                Register Driver
            </button>
        </form>
    </div>
</div>

</body>
</html>
