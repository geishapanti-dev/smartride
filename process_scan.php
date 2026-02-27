<?php
session_start();
date_default_timezone_set('Asia/Manila'); 

include "includes/db.php";
include "includes/sms_sender.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['qr_code']) || !isset($data['origin']) || !isset($data['destination'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit();
}

$qr_code = mysqli_real_escape_string($conn, $data['qr_code']);
$origin = mysqli_real_escape_string($conn, $data['origin']);
$destination = mysqli_real_escape_string($conn, $data['destination']);

// Find the driver (using 'contact' based on your screenshot)
$driver_query = "SELECT id, name, vehicle, body_number, contact FROM drivers WHERE qr_code = '$qr_code'";
$driver_result = mysqli_query($conn, $driver_query);

if (mysqli_num_rows($driver_result) > 0) {
    $driver = mysqli_fetch_assoc($driver_result);
    $driver_id = $driver['id'];
    $driver_name = $driver['name'];
    $driver_phone = $driver['contact']; 
    $vehicle_info = $driver['vehicle'] . " (" . $driver['body_number'] . ")";

    $active_check = mysqli_query($conn, "SELECT id FROM trips WHERE user_id='$user_id' AND status!='completed'");
    if (mysqli_num_rows($active_check) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'You already have an active trip!']);
        exit();
    }

    $insert_query = "INSERT INTO trips (user_id, driver_id, origin, destination, status, created_at) 
                     VALUES ('$user_id', '$driver_id', '$origin', '$destination', 'in_transit', NOW())";
    
    if (mysqli_query($conn, $insert_query)) {
        $userQ = mysqli_query($conn, "SELECT name, guardian_number FROM users WHERE id='$user_id'");
        $userData = mysqli_fetch_assoc($userQ);
        
        $guardian_num = $userData['guardian_number'];
        $student_name = $userData['name'];

        $message = "TRIP STARTED: $student_name is now on board.\n" .
                   "Driver: $driver_name ($driver_phone)\n" . 
                   "Vehicle: $vehicle_info\n" .
                   "Route: $origin to $destination\n" .
                   "Time: " . date('h:i A'); // Now shows PH Time

        if (!empty($guardian_num)) {
            sendSMS($guardian_num, $message);
        }

        echo json_encode(['status' => 'success', 'redirect' => 'trip_status.php']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Driver QR Code']);
}
?>