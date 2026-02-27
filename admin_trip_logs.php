<?php
session_start();
include "includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$trip_id = $_GET['trip_id'];

// Fetch trip info
$tripQuery = mysqli_query($conn, "
    SELECT t.id as trip_id, t.status, t.created_at, u.name as user_name
    FROM trips t
    JOIN users u ON t.user_id = u.id
    WHERE t.id='$trip_id'
");
$trip = mysqli_fetch_assoc($tripQuery);

// Fetch logs
$logs = mysqli_query($conn, "
    SELECT * FROM trip_logs
    WHERE trip_id='$trip_id'
    ORDER BY scan_time ASC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Trip Logs</title>
    
</head>
<body>

<h2>Trip Logs for Trip ID: <?php echo $trip['trip_id']; ?></h2>
<p>Passenger: <?php echo $trip['user_name']; ?></p>
<p>Status: <?php echo $trip['status']; ?></p>
<p>Created At: <?php echo $trip['created_at']; ?></p>

<a href="admin_trips.php">Back to All Trips</a>
<br><br>

<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>Log ID</th>
        <th>Scan Type</th>
        <th>Scan Time</th>
    </tr>

    <?php while ($log = mysqli_fetch_assoc($logs)) { ?>
    <tr>
        <td><?php echo $log['id']; ?></td>
        <td><?php echo $log['scan_type']; ?></td>
        <td><?php echo $log['scan_time']; ?></td>
    </tr>
    <?php } ?>
</table>

</body>
</html>
