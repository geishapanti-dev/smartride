<?php
session_start();
include "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// 1. Get active trip (Joined with drivers to get driver info)
$currentTripQuery = mysqli_query($conn,
    "SELECT trips.*, drivers.name as driver_name, drivers.vehicle 
     FROM trips 
     LEFT JOIN drivers ON trips.driver_id = drivers.id
     WHERE trips.user_id='$user_id' 
     AND trips.status != 'completed'
     ORDER BY trips.id DESC
     LIMIT 1"
);
$currentTrip = mysqli_fetch_assoc($currentTripQuery);

// 2. Get trip history (Joined with drivers to get driver info and vehicle)
$historyQuery = mysqli_query($conn,
    "SELECT trips.*, drivers.name as driver_name, drivers.vehicle 
     FROM trips 
     LEFT JOIN drivers ON trips.driver_id = drivers.id
     WHERE trips.user_id='$user_id' 
     ORDER BY trips.id DESC 
     LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-gradient-custom { background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .snap-x-container { scroll-snap-type: x mandatory; }
        .snap-card { scroll-snap-align: center; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen pb-10">

    <div class="flex gap-2 mb-3 overflow-x-auto p-4">
        <button type="button" onclick="setLoc('Home')" class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-xs font-bold border border-indigo-100">🏠 Home</button>
        <button type="button" onclick="setLoc('School')" class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-xs font-bold border border-indigo-100">🎓 School</button>
        <button type="button" onclick="setLoc('Mall')" class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-xs font-bold border border-indigo-100">🛍️ Mall</button>
    </div>

    <div class="bg-gradient-custom text-white pb-24 pt-8 px-6 rounded-b-[2.5rem] shadow-xl">
        <div class="max-w-md mx-auto flex justify-between items-center">
            <div>
                <p class="text-indigo-100 text-xs uppercase tracking-widest font-semibold">Welcome back</p>
                <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($user_name); ?></h2>
            </div>
            <a href="logout.php" class="bg-white/20 hover:bg-white/30 p-2 px-4 rounded-2xl text-sm backdrop-blur-md transition-all font-medium border border-white/10">Logout</a>
        </div>
    </div>

    <div class="max-w-md mx-auto px-4 -mt-16">
        <div class="bg-white rounded-[2rem] shadow-2xl shadow-indigo-100 p-6 mb-8 border border-gray-100">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-widest">Active Trip</h3>
                <?php if ($currentTrip): ?>
                    <span class="flex items-center gap-2 text-xs font-bold text-green-500 uppercase">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        In Progress
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!$currentTrip): ?>
                <form id="tripForm" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-gray-400 uppercase ml-1">Current Location</label>
                        <input type="text" id="origin" placeholder="Where are you?" class="w-full bg-gray-50 border-2 border-gray-100 rounded-2xl py-3.5 px-4 focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-gray-400 uppercase ml-1">Destination</label>
                        <input type="text" id="destination" placeholder="Where to?" class="w-full bg-gray-50 border-2 border-gray-100 rounded-2xl py-3.5 px-4 focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none">
                    </div>
                    <button type="button" id="scanBtn" disabled class="w-full bg-indigo-600 disabled:bg-gray-200 disabled:text-gray-400 text-white font-bold py-4 rounded-2xl shadow-lg shadow-indigo-100 transition-all active:scale-95 mt-2">Scan Driver QR</button>
                </form>
            <?php else: ?>
                <div class="relative pl-6 space-y-6">
                    <div class="absolute left-[7px] top-2 bottom-2 w-0.5 border-l-2 border-dashed border-gray-200"></div>
                    <div class="relative">
                        <div class="absolute -left-[23px] top-1.5 w-3 h-3 rounded-full bg-indigo-500 ring-4 ring-indigo-50"></div>
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Driver: <?php echo htmlspecialchars($currentTrip['driver_name']); ?></p>
                        <p class="font-bold text-gray-800 leading-tight"><?php echo htmlspecialchars($currentTrip['origin']); ?></p>
                    </div>
                    <div class="relative">
                        <div class="absolute -left-[23px] top-1.5 w-3 h-3 rounded-full bg-red-500 ring-4 ring-red-50"></div>
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Destination</p>
                        <p class="font-bold text-gray-800 leading-tight"><?php echo htmlspecialchars($currentTrip['destination']); ?></p>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-50 flex items-center justify-between">
                    <div class="bg-indigo-50 px-4 py-2 rounded-full">
                        <span class="text-indigo-600 font-bold text-xs uppercase tracking-tighter"><?php echo $currentTrip['status']; ?></span>
                    </div>
                    <a href="trip_status.php" class="bg-indigo-600 text-white px-6 py-3 rounded-2xl font-bold text-sm shadow-lg shadow-indigo-100 active:scale-95 transition-transform">
                        View Trip →
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="mb-4 flex items-center justify-between px-2">
            <h3 class="font-bold text-gray-800 text-sm uppercase tracking-widest">Recent Activity</h3>
        </div>

        <div class="flex overflow-x-auto gap-4 pb-6 hide-scrollbar snap-x-container px-2">
            <?php if (mysqli_num_rows($historyQuery) == 0): ?>
                <div class="w-full bg-white rounded-3xl p-8 text-center border border-gray-100 shadow-sm">
                    <p class="text-gray-400 text-sm font-medium">No trip history yet.</p>
                </div>
            <?php else: ?>
                <?php while ($row = mysqli_fetch_assoc($historyQuery)): ?>
                    <div class="min-w-[240px] max-w-[240px] bg-white p-5 rounded-[2rem] shadow-md border border-gray-50 snap-card">
                        <div class="flex justify-between items-start mb-2">
                            <div class="p-2 bg-gray-50 rounded-xl text-lg">🚕</div>
                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest pt-2">
                                <?php echo date('M d, g:i A', strtotime($row['created_at'])); ?>
                            </span>
                        </div>
                        <div class="space-y-1 mb-4">
                            <p class="text-[10px] text-indigo-500 font-bold uppercase">Driver: <?php echo htmlspecialchars($row['driver_name'] ?? 'Unknown'); ?></p>
                            <p class="text-sm font-bold text-gray-800 truncate"><?php echo htmlspecialchars($row['destination']); ?></p>
                        </div>
                        <div class="flex items-center justify-between text-[10px] font-bold text-gray-400 pt-3 border-t border-gray-50">
                            <span><?php echo ($row['status'] == 'completed') ? 'SUCCESS' : 'ENDED'; ?></span>
                            <a href="trip_details.php?id=<?php echo $row['id']; ?>" class="text-indigo-500 hover:underline">DETAILS →</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/passenger.js"></script>
</body>
</html>