<?php
session_start();
include "includes/db.php";

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// --- DATA FETCHING ---
$active_trips = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM trips WHERE status='Active'"))['total'] ?? 0;
$total_drivers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM drivers"))['total'] ?? 0;
$total_passengers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='passenger'"))['total'] ?? 0;

// SMS Count Logic
$sms_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM sms_logs WHERE DATE(sent_at) = CURDATE()");
$sms_count = ($sms_res) ? mysqli_fetch_assoc($sms_res)['total'] : 0; 

$trips_query = mysqli_query($conn, "SELECT * FROM trips ORDER BY id DESC LIMIT 3");

// UPDATED: Fetch all users using the new column names (body_number for drivers, username for passengers)
$users_query = mysqli_query($conn, "
    SELECT name, 'driver' AS role, body_number AS identifier FROM drivers
    UNION ALL
    SELECT name, role, username AS identifier FROM users WHERE role='passenger'
    ORDER BY role ASC, name ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Transport System</title>
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
            min-height: 100vh;
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

        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }

        .stat-card { 
            background: var(--card-bg); 
            color: var(--text-dark); 
            padding: 20px; 
            border-radius: 12px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }

        .stat-card h4 { margin: 0; color: #666; font-size: 0.85rem; text-transform: uppercase; }
        .stat-card .value { font-size: 1.8rem; font-weight: bold; color: var(--accent-blue); }

        .monitor-card, .table-container { 
            background: var(--card-bg); 
            color: var(--text-dark); 
            padding: 25px; 
            border-radius: 12px; 
            margin-bottom: 30px; 
        }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; color: #888; font-size: 0.8rem; text-transform: uppercase; padding: 12px; border-bottom: 2px solid #eee; }
        td { padding: 15px 12px; border-bottom: 1px solid #eee; font-size: 0.95rem; }
        .badge { background: var(--success-green); color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; }

        .sms-alert { 
            background: #27ae60; 
            color: white; 
            padding: 15px 25px; 
            border-radius: 8px; 
            margin-top: 20px; 
            display: inline-flex; 
            align-items: center; 
            gap: 10px; 
        }

        .system-health { 
            position: fixed; 
            bottom: 20px; 
            right: 20px; 
            background: #2c3e50; 
            padding: 12px 20px; 
            border-radius: 8px; 
            font-size: 0.85rem; 
            border: 1px solid #444; 
            color: white;
            z-index: 1000;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-bus"></i> Ser Dashboard
    </div>
    <div class="nav-group">
        <a href="admin_panel.php" class="nav-item active"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="add_driver.php" class="nav-item"><i class="fas fa-user-plus"></i> Add Driver</a>
        <a href="admin_trips.php" class="nav-item"><i class="fas fa-history"></i> Trip History</a>
        <a href="logout.php" class="nav-item" style="margin-top: 20px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="main-content">

    <div class="stats-grid">
        <div class="stat-card">
            <div><h4>Active Trips</h4><div class="value"><?php echo $active_trips; ?></div></div>
            <i class="fas fa-route" style="font-size: 2rem; color: #eee;"></i>
        </div>
        <div class="stat-card">
            <div><h4>Drivers</h4><div class="value"><?php echo $total_drivers; ?></div></div>
            <i class="fas fa-id-card" style="font-size: 2rem; color: #eee;"></i>
        </div>
        <div class="stat-card">
            <div><h4>Passengers</h4><div class="value"><?php echo $total_passengers; ?></div></div>
            <i class="fas fa-users" style="font-size: 2rem; color: #eee;"></i>
        </div>
        <div class="stat-card">
            <div><h4>SMS Today</h4><div class="value"><?php echo $sms_count; ?></div></div>
            <i class="fas fa-envelope" style="font-size: 2rem; color: #eee;"></i>
        </div>
    </div>

    <h3 style="margin-bottom: 20px;">Live User Monitor</h3>
    
    <div class="monitor-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <div>
                <h2 style="margin:0 0 15px 0;">All Users</h2>
                <p style="color: #888; font-size: 0.85rem;">List of Drivers and Passengers</p>
            </div>
            <div style="text-align: right;">
                <p style="color: #888; margin-bottom: 5px; font-size: 0.8rem;">System Status:</p>
                <i class="fas fa-users" style="color: var(--accent-blue); font-size: 2rem;"></i>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>ID / Email</th> </tr>
            </thead>
            <tbody>
                <?php while($user = mysqli_fetch_assoc($users_query)) { 
                    $role_lower = strtolower($user['role']);
                    $role_color = ($role_lower == 'driver') ? '#3498db' : '#2ecc71';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td>
                        <span style="background: <?php echo $role_color; ?>; color:white; padding:4px 10px; border-radius:12px; font-size:0.8rem;">
                            <?php echo ucfirst($role_lower); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($user['identifier']); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="table-container">
        <h3>Recent Trip History</h3>
        <table>
            <thead>
                <tr>
                    <th>Origin</th>
                    <th>Destination</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($trip = mysqli_fetch_assoc($trips_query)) { ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($trip['origin']); ?></strong></td>
                    <td><?php echo htmlspecialchars($trip['destination']); ?></td>
                    <td><?php echo date('H:i', strtotime($trip['created_at'])); ?></td>
                    <td><span class="badge">Arrived</span></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="sms-alert">
        <i class="fas fa-check"></i> SMS alert system is active and monitoring
    </div>

</div>

<div class="system-health">
    System Health: <span style="color: var(--success-green);"><i class="fas fa-check-circle"></i> Twilio API Online</span>
</div>

</body>
</html>