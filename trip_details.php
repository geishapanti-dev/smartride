<?php
session_start();
include "includes/db.php";

if (!isset($_GET['id'])) { header("Location: dashboard.php"); exit(); }

$trip_id = $_GET['id'];

$query = mysqli_query($conn, "SELECT trips.*, 
                                     drivers.name as d_name, 
                                     drivers.vehicle, 
                                     drivers.contact, 
                                     drivers.address, 
                                     drivers.body_number, 
                                     drivers.corporation, 
                                     drivers.profile_pic 
                              FROM trips 
                              JOIN drivers ON trips.driver_id = drivers.id 
                              WHERE trips.id = '$trip_id'");
$trip = mysqli_fetch_assoc($query);

$profile_img = 'uploads/default.png'; 

if (!empty($trip['profile_pic'])) {
    if (file_exists($trip['profile_pic'])) {
        $profile_img = $trip['profile_pic'];
    }
    elseif (file_exists('uploads/' . $trip['profile_pic'])) {
        $profile_img = 'uploads/' . $trip['profile_pic'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-md mx-auto bg-white rounded-[2.5rem] shadow-xl overflow-hidden border border-gray-100">
        <div class="bg-indigo-600 p-8 text-white text-center">
            <div class="relative inline-block">
                <img src="<?php echo htmlspecialchars($profile_img); ?>" 
                     class="w-24 h-24 rounded-full mx-auto mb-4 border-4 border-white/20 object-cover shadow-lg bg-white" 
                     alt="Driver">
            </div>
            <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($trip['d_name']); ?></h2>
            <p class="text-indigo-200 text-sm font-medium"><?php echo htmlspecialchars($trip['corporation']); ?></p>
        </div>
        
        <div class="p-8 space-y-6">
            <div class="flex justify-between p-4 bg-gray-50 rounded-2xl relative">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase">From</p>
                    <p class="text-gray-800 font-bold"><?php echo htmlspecialchars($trip['origin']); ?></p>
                </div>
                <div class="self-center text-gray-300">➜</div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-gray-400 uppercase">To</p>
                    <p class="text-gray-800 font-bold"><?php echo htmlspecialchars($trip['destination']); ?></p>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100 space-y-4">
                <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Driver Information</p>
                
                <div class="flex items-start gap-3 text-gray-700">
                    <span class="text-lg">📍</span>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase">Address</p>
                        <p class="text-sm font-medium"><?php echo htmlspecialchars($trip['address'] ?: 'Not Provided'); ?></p>
                    </div>
                </div>

                <div class="flex items-start gap-3 text-gray-700">
                    <span class="text-lg">🚐</span>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase">Vehicle & Body No.</p>
                        <p class="text-sm font-medium"><?php echo htmlspecialchars($trip['vehicle']); ?> (#<?php echo htmlspecialchars($trip['body_number']); ?>)</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 text-gray-700">
                    <span class="text-lg">📞</span>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase">Contact</p>
                        <a href="tel:<?php echo $trip['contact']; ?>" class="text-sm font-medium text-indigo-600 hover:underline">
                            <?php echo htmlspecialchars($trip['contact']); ?>
                        </a>
                    </div>
                </div>
            </div>

            <a href="dashboard.php" class="block w-full text-center bg-gray-100 py-4 rounded-2xl font-bold text-gray-500 mt-4 hover:bg-gray-200 transition-colors">
                Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>