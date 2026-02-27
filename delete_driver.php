<?php
session_start();
include "includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Get driver id from URL
$id = $_GET['id'];

// Delete driver
mysqli_query($conn, "DELETE FROM drivers WHERE id='$id'");

header("Location: admin_panel.php");
exit();
