<?php
session_start();
include "includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? null;
$message = '';

// Fetch current data
$driver = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM drivers WHERE id = '$id'"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $body_no = mysqli_real_escape_string($conn, $_POST['body_number']);
    $vehicle = mysqli_real_escape_string($conn, $_POST['vehicle']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $corp = mysqli_real_escape_string($conn, $_POST['corporation']);
    
    $profile_pic = $driver['profile_pic']; // Keep old pic by default

    // Handle File Upload
    if (!empty($_FILES['profile_pic']['name'])) {
        $target_dir = "uploads/profile_pics/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $file_name = "driver_" . $id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
            $profile_pic = $file_name;
        }
    }

    // Update Drivers Table
    $update_driver = "UPDATE drivers SET 
        name='$name', body_number='$body_no', vehicle='$vehicle', 
        contact='$contact', address='$address', corporation='$corp', 
        profile_pic='$profile_pic' 
        WHERE id='$id'";

    // Update Users Table (Sync username with body number)
    $update_user = "UPDATE users SET name='$name', username='$body_no' WHERE id=(SELECT id FROM users WHERE username='{$driver['body_number']}' LIMIT 1)";

    if (mysqli_query($conn, $update_driver) && mysqli_query($conn, $update_user)) {
        header("Location: admin_panel.php?msg=updated");
        exit();
    } else {
        $message = "Error updating record: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Driver</title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <style>
        .edit-container { max-width: 500px; margin: 50px auto; background: white; padding: 30px; border-radius: 12px; color: #333; }
        .preview-img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; display: block; margin: 10px 0; border: 2px solid #3498db; }
        input, select { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #3498db; color: white; border: none; padding: 12px; width: 100%; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body style="background: #1a1c24;">
    <div class="edit-container">
        <h2>Edit Driver Details</h2>
        <?php if($message) echo "<p style='color:red;'>$message</p>"; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <label>Current Profile Picture:</label>
            <img src="uploads/profile_pics/<?php echo $driver['profile_pic'] ?: 'default.png'; ?>" class="preview-img">
            <input type="file" name="profile_pic" accept="image/*">

            <input type="text" name="name" value="<?php echo $driver['name']; ?>" placeholder="Full Name" required>
            <input type="text" name="body_number" value="<?php echo $driver['body_number']; ?>" placeholder="Body Number" required>
            <input type="text" name="vehicle" value="<?php echo $driver['vehicle']; ?>" placeholder="Vehicle Type">
            <input type="text" name="contact" value="<?php echo $driver['contact']; ?>" placeholder="Contact Number">
            <input type="text" name="address" value="<?php echo $driver['address']; ?>" placeholder="Address">
            <input type="text" name="corporation" value="<?php echo $driver['corporation']; ?>" placeholder="Corporation">
            
            <button type="submit">UPDATE DRIVER</button>
            <a href="admin_panel.php" style="display:block; text-align:center; margin-top:15px; color:#777; text-decoration:none;">Cancel</a>
        </form>
    </div>
</body>
</html>