<?php
session_start();
include "includes/db.php";

/* Only allow drivers */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: login.php");
    exit();
}

$driver_id = $_SESSION['user_id'];

/* Fetch driver info */
$driverQuery = mysqli_query($conn, "SELECT * FROM drivers WHERE id='$driver_id'");
$driver = mysqli_fetch_assoc($driverQuery);

/* If no profile picture, use default */
if (empty($driver['profile_pic'])) {
    $driver['profile_pic'] = "uploads/default.png";
}

/* Fetch trips */
$tripsQuery = mysqli_query($conn, "
    SELECT * FROM trips 
    WHERE driver_id='$driver_id' 
    ORDER BY created_at DESC
    LIMIT 15
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-gradient-custom { background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>

<body class="bg-gray-50 min-h-screen pb-10">

    <!-- HEADER -->
    <div class="bg-gradient-custom text-white pb-24 pt-8 px-6 rounded-b-[2.5rem] shadow-xl">
        <div class="max-w-md mx-auto flex justify-between items-center">

            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full overflow-hidden border-2 border-white shadow-lg">
                    <img src="<?php echo htmlspecialchars($driver['profile_pic']); ?>" 
                         class="w-full h-full object-cover">
                </div>
                <div>
                    <p class="text-indigo-100 text-xs uppercase tracking-widest font-semibold">Driver Portal</p>
                    <h2 class="text-2xl font-bold">
                        <?php echo htmlspecialchars($driver['name']); ?>
                    </h2>
                </div>
            </div>

            <a href="logout.php" 
               class="bg-white/20 hover:bg-white/30 p-2 px-4 rounded-2xl text-sm backdrop-blur-md transition-all font-medium border border-white/10">
                Logout
            </a>
        </div>
    </div>

    <div class="max-w-md mx-auto px-4 -mt-16">

        <!-- QR CARD -->
        <div class="bg-white rounded-[2rem] shadow-2xl shadow-indigo-100 p-8 mb-8 border border-gray-100 text-center">
            <h3 class="font-bold text-gray-800 text-sm uppercase tracking-widest mb-6">
                Your Passenger QR
            </h3>

            <?php if (!empty($driver['qr_code'])): ?>
                <?php 
                $startQrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" 
                              . urlencode($driver['qr_code']); 
                ?>
                
                <div class="bg-gray-50 p-6 rounded-[2rem] inline-block mb-6 border border-gray-100">
                    <img src="<?php echo $startQrUrl; ?>" 
                         class="w-48 h-48 mx-auto rounded-lg">
                </div>

                <div class="space-y-4">
                    <p class="text-gray-500 text-sm px-4">
                        Passengers scan this QR to start their trip and view your profile.
                    </p>

                    <a href="<?php echo $startQrUrl; ?>" 
                       download="MY_DRIVER_QR.png" 
                       class="flex items-center justify-center gap-2 w-full bg-indigo-600 text-white font-bold py-4 rounded-2xl shadow-lg shadow-indigo-100 transition-all active:scale-95">
                        <i class="fas fa-download"></i> Save QR Code
                    </a>
                </div>
            <?php else: ?>
                <p class="text-red-500 font-bold">
                    No QR Code assigned to you yet.
                </p>
            <?php endif; ?>
        </div>

        <!-- DRIVER INFO -->
        <div class="bg-white rounded-3xl p-6 mb-6 border border-gray-100 shadow-sm flex items-center justify-around">
            <div class="text-center">
                <p class="text-[10px] text-gray-400 font-bold uppercase">Vehicle</p>
                <p class="font-bold text-gray-800">
                    <?php echo htmlspecialchars($driver['vehicle']); ?>
                </p>
            </div>

            <div class="h-8 w-px bg-gray-100"></div>

            <div class="text-center">
                <p class="text-[10px] text-gray-400 font-bold uppercase">Contact</p>
                <p class="font-bold text-gray-800">
                    <?php echo htmlspecialchars($driver['contact']); ?>
                </p>
            </div>
        </div>

        <!-- UPDATE PROFILE BUTTON -->
        <div class="text-center mb-8">
            <a href="update_profile_pic.php"
               class="inline-block bg-indigo-600 text-white text-sm font-bold px-6 py-3 rounded-2xl shadow-lg shadow-indigo-100 transition-all active:scale-95">
               Update Profile Picture & Change Password
            </a>
        </div>

        <!-- RECENT PASSENGERS -->
        <div class="mb-4 flex items-center justify-between px-2">
            <h3 class="font-bold text-gray-800 text-sm uppercase tracking-widest">
                Recent Passengers
            </h3>
        </div>

        <div class="space-y-4 px-2">
            <?php if ($tripsQuery && mysqli_num_rows($tripsQuery) > 0): ?>
                <?php while($trip = mysqli_fetch_assoc($tripsQuery)): ?>
                    <div class="bg-white p-5 rounded-3xl shadow-sm border border-gray-50 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600">
                                <i class="fas fa-user text-lg"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-tighter">
                                    <?php echo date('M d, h:i A', strtotime($trip['created_at'])); ?>
                                </p>
                                <p class="font-bold text-gray-800 text-sm">
                                    <?php echo htmlspecialchars($trip['origin']); ?> →
                                    <?php echo htmlspecialchars($trip['destination']); ?>
                                </p>
                            </div>
                        </div>

                        <div class="text-right">
                            <?php if($trip['status'] === 'completed'): ?>
                                <span class="text-[10px] font-bold text-green-500 bg-green-50 px-3 py-1 rounded-full uppercase">
                                    Done
                                </span>
                            <?php else: ?>
                                <span class="text-[10px] font-bold text-indigo-500 bg-indigo-50 px-3 py-1 rounded-full uppercase animate-pulse">
                                    Active
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="bg-white rounded-3xl p-10 text-center border border-gray-100 shadow-sm">
                    <p class="text-gray-400 text-sm font-medium">
                        No trips recorded yet.
                    </p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
