<?php
session_start();
include "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user already has an active trip
$check = mysqli_query($conn,
    "SELECT id FROM trips WHERE user_id='$user_id' AND status!='completed'"
);

if (mysqli_num_rows($check) > 0) {
    header("Location: dashboard.php");
    exit();
}

// Create new trip
mysqli_query($conn,
    "INSERT INTO trips (user_id, status) VALUES ('$user_id', 'in_transit')"
);

header("Location: dashboard.php");
exit();
