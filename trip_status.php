<?php
session_start();
include "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT trips.*, 
                 drivers.name, 
                 drivers.vehicle, 
                 drivers.contact, 
                 drivers.body_number, 
                 drivers.profile_pic,
                 drivers.address,
                 drivers.corporation
          FROM trips 
          JOIN drivers ON trips.driver_id = drivers.id 
          WHERE trips.user_id='$user_id' AND trips.status != 'completed' 
          LIMIT 1";

$result = mysqli_query($conn, $query);
$trip = mysqli_fetch_assoc($result);

if (!$trip) {
    header("Location: dashboard.php");
    exit();
}

// --- 🛠️ FIX: CORRECT IMAGE PATH LOGIC ---
$profile_img = 'uploads/default.png'; 

if (!empty($trip['profile_pic'])) {
    // Check direct path first (e.g. 'uploads/driver_7.jpg')
    if (file_exists($trip['profile_pic'])) {
        $profile_img = $trip['profile_pic'];
    }
    // Check relative to uploads folder just in case
    elseif (file_exists('uploads/' . $trip['profile_pic'])) {
        $profile_img = 'uploads/' . $trip['profile_pic'];
    }
}
// ------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip In Progress</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen p-4 flex flex-col items-center justify-start sm:justify-center">

    <div class="mb-4 animate-pulse mt-4">
        <span class="bg-green-100 text-green-700 px-6 py-2 rounded-full font-bold text-xs uppercase tracking-widest border border-green-200 shadow-sm">
            🟢 In Transit
        </span>
    </div>

    <div class="w-full max-w-md bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-gray-100">
        <div class="bg-indigo-600 h-24"></div>
        
        <div class="px-6 pb-8">
            <div class="flex flex-col items-center -mt-12">
                <div class="w-24 h-24 bg-white rounded-full shadow-xl border-4 border-white overflow-hidden">
                    <img src="<?php echo htmlspecialchars($profile_img); ?>" 
                         alt="Driver Profile" 
                         class="w-full h-full object-cover">
                </div>
                
                <div class="mt-4 text-center">
                    <h2 class="text-2xl font-bold text-gray-800 leading-tight"><?php echo htmlspecialchars($trip['name']); ?></h2>
                    <p class="text-indigo-600 font-semibold text-sm uppercase tracking-wide"><?php echo htmlspecialchars($trip['corporation']); ?></p>
                    
                    <div class="flex justify-center gap-3 mt-4">
                        <a href="tel:<?php echo htmlspecialchars($trip['contact']); ?>" 
                           class="flex items-center gap-2 px-5 py-2 bg-green-500 text-white rounded-full hover:bg-green-600 transition shadow-md text-sm font-bold">
                           📞 Call Driver
                        </a>
                        <div class="flex items-center gap-2 px-5 py-2 bg-gray-100 text-gray-700 rounded-full border border-gray-200 text-sm font-bold">
                           🆔 <?php echo htmlspecialchars($trip['body_number']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 space-y-3">
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                    <p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Driver's Home Base</p>
                    <p class="text-sm text-gray-700 font-medium">📍 <?php echo htmlspecialchars($trip['address']); ?></p>
                </div>

                <div class="flex justify-between items-center p-4 bg-indigo-50/50 rounded-2xl border border-indigo-100/50">
                    <div class="text-left">
                        <p class="text-[10px] text-indigo-400 font-bold uppercase">Pickup</p>
                        <p class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($trip['origin']); ?></p>
                    </div>
                    <div class="text-indigo-300 px-2 font-bold">➜</div>
                    <div class="text-right">
                        <p class="text-[10px] text-indigo-400 font-bold uppercase">Dropoff</p>
                        <p class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($trip['destination']); ?></p>
                    </div>
                </div>
                
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                    <p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Vehicle Information</p>
                    <p class="text-sm text-gray-700 font-bold">🚐 <?php echo htmlspecialchars($trip['vehicle']); ?></p>
                </div>
            </div>

            <form action="end_trip.php" method="POST" class="mt-8">
                <button type="submit" onclick="return confirm('Arrived safely?')" 
                        class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-2xl shadow-lg shadow-red-100 transition-all active:scale-[0.98]">
                    🛑 End Current Trip
                </button>
            </form>
            <p class="text-center text-[10px] text-gray-400 mt-4 uppercase font-bold tracking-widest">Safety First &bull; Tap to Arrive</p>
        </div>
    </div>

</body>
</html>