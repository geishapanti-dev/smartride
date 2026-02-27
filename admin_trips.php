<?php
session_start();
include "includes/db.php";

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// --- HANDLE DELETE TRIP ---
$msg = '';
if (isset($_POST['delete_trip'])) {
    $trip_id = intval($_POST['trip_id']);

    // Delete related trip logs first to avoid foreign key errors
    mysqli_query($conn, "DELETE FROM trip_logs WHERE trip_id = $trip_id");

    // Then delete the trip
    if (mysqli_query($conn, "DELETE FROM trips WHERE id = $trip_id")) {
        $msg = "Trip deleted successfully!";
    } else {
        $msg = "Error deleting trip!";
    }
}

// Fetch all trips with Passenger names
$tripsQuery = mysqli_query($conn, "
    SELECT t.*, u.name as passenger_name 
    FROM trips t 
    LEFT JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Trips | Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    :root {
        --sidebar-width: 240px;
        --sidebar-bg: #222d3a;
        --main-bg: #1a1c24;
        --card-bg: #f8f9fa;
        --text-dark: #333;
        --accent-blue: #3498db;
        --success-green: #2ecc71;
    }

    * { box-sizing: border-box; }

    body { 
        font-family: 'Segoe UI', sans-serif; 
        background: var(--main-bg); 
        color: white; 
        margin: 0; 
        display: flex; 
        overflow-x: hidden; 
    }

    .sidebar { 
        width: var(--sidebar-width); 
        background: var(--sidebar-bg); 
        height: 100vh; 
        padding: 20px 0; 
        position: fixed; 
        left: 0;
        top: 0;
        border-right: 1px solid #333; 
        z-index: 100;
    }

    .sidebar-brand { 
        padding: 0 25px 30px; 
        display: flex; 
        align-items: center; 
        gap: 12px; 
        font-size: 1.1rem; 
        font-weight: bold; 
        color: white; 
    }

    .nav-group { display: flex; flex-direction: column; }

    .nav-item { 
        padding: 12px 25px; 
        display: flex; 
        align-items: center; 
        gap: 15px; 
        color: #a0a5b1; 
        text-decoration: none; 
        transition: 0.3s; 
        font-size: 0.95rem; 
    }

    .nav-item:hover, .nav-item.active { 
        background: #34495e; 
        color: white; 
    }

    .nav-item.active { 
        border-left: 4px solid var(--accent-blue); 
    }

    .main-content { 
        margin-left: var(--sidebar-width); 
        padding: 40px; 
        width: calc(100% - var(--sidebar-width)); 
        min-height: 100vh;
    }

    .table-card { 
        background: var(--card-bg); 
        color: var(--text-dark); 
        border-radius: 12px; 
        padding: 25px; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.3); 
    }

    .table-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 20px; 
    }

    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; color: #888; font-weight: 600; padding: 15px; border-bottom: 2px solid #eee; text-transform: uppercase; font-size: 0.8rem; }
    td { padding: 15px; border-bottom: 1px solid #eee; font-size: 0.9rem; }

    .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; color: white; }
    .status-arrived { background: var(--success-green); }
    .status-active { background: #e67e22; }
    .status-pending { background: #95a5a6; }

    .btn-view, .btn-delete { 
        text-decoration: none; 
        font-weight: 600; 
        font-size: 0.85rem; 
        padding: 5px 10px; 
        border-radius: 5px; 
        color: white; 
        cursor: pointer; 
    }

    .btn-view { background: var(--accent-blue); }
    .btn-view:hover { opacity: 0.9; }

    .btn-delete { background: #e74c3c; margin-left: 5px; border: none; }
    .btn-delete:hover { opacity: 0.8; }

    .system-health-tag { color: var(--success-green); font-size: 0.85rem; font-weight: 500; }

    .msg-alert { background: var(--success-green); color: white; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; display: inline-block; }
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-bus"></i> Ser Dashboard
    </div>
    <div class="nav-group">
        <a href="admin_panel.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="add_driver.php" class="nav-item"><i class="fas fa-user-plus"></i> Add Driver</a>
        <a href="admin_trips.php" class="nav-item active"><i class="fas fa-history"></i> Trip History</a>
        <a href="logout.php" class="nav-item" style="margin-top: 20px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <?php if($msg): ?>
        <div class="msg-alert"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="table-card">
        <div class="table-header">
            <h2 style="margin:0; font-size: 1.4rem;">Detailed Trip History</h2>
            <div class="system-health-tag">
                <i class="fas fa-check-circle"></i> Twilio SMS API Connected
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Trip ID</th>
                    <th>Passenger</th>
                    <th>Route (O → D)</th>
                    <th>Start Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($trip = mysqli_fetch_assoc($tripsQuery)) { 
                    $statusClass = 'status-pending';
                    if($trip['status'] == 'Arrived') $statusClass = 'status-arrived';
                    if($trip['status'] == 'Active') $statusClass = 'status-active';
                ?>
                <tr>
                    <td>#<?php echo $trip['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($trip['passenger_name']); ?></strong></td>
                    <td>
                        <?php echo htmlspecialchars($trip['origin'] ?? 'N/A'); ?> 
                        <i class="fas fa-arrow-right" style="font-size: 0.7rem; color: #ccc; margin: 0 5px;"></i> 
                        <?php echo htmlspecialchars($trip['destination'] ?? 'N/A'); ?>
                    </td>
                    <td><?php echo date('M d, H:i', strtotime($trip['created_at'])); ?></td>
                    <td>
                        <span class="badge <?php echo $statusClass; ?>">
                            <?php echo $trip['status']; ?>
                        </span>
                    </td>
                    <td>
                        <a href="trip_logs.php?trip_id=<?php echo $trip['id']; ?>" class="btn-view">
                            <i class="fas fa-eye"></i> View Logs
                        </a>
                        <!-- DELETE BUTTON -->
                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this trip?');">
                            <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                            <button type="submit" name="delete_trip" class="btn-delete">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
