<?php
session_start();
date_default_timezone_set('Asia/Manila'); 

include "includes/db.php";
include "includes/sms_sender.php"; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$check_query = "SELECT trips.id, trips.destination, 
                       users.name AS student_name, users.guardian_number,
                       drivers.name AS driver_name, drivers.contact AS driver_phone
                FROM trips 
                JOIN users ON trips.user_id = users.id
                JOIN drivers ON trips.driver_id = drivers.id
                WHERE trips.user_id='$user_id' AND trips.status != 'completed'
                LIMIT 1";

$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) > 0) {
    $trip = mysqli_fetch_assoc($result);
    $trip_id = $trip['id'];

    $update_query = "UPDATE trips SET status='completed' WHERE id='$trip_id'";
    
    if (mysqli_query($conn, $update_query)) {
        $guardian_num = $trip['guardian_number'];
        $student_name = $trip['student_name'];
        $destination  = $trip['destination'];
        $driver_name  = $trip['driver_name'];
        $driver_phone = $trip['driver_phone']; 
        $arrival_time = date('h:i A'); // Now shows PH Time

        $message = "SAFE ARRIVAL: $student_name has safely arrived at $destination.\n" .
                   "Driver: $driver_name ($driver_phone)\n" . 
                   "Time: $arrival_time\n" .
                   "Trip Status: COMPLETED";

        if (!empty($guardian_num)) {
            sendSMS($guardian_num, $message);
        }

        header("Location: dashboard.php?status=trip_ended");
    } else {
        echo "Error ending trip: " . mysqli_error($conn);
    }
} else {
    header("Location: dashboard.php");
}
exit();
?>