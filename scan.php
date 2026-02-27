<?php
session_start();
include "includes/db.php";

// 🛡️ Security: Only passengers should access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header("Location: login.php");
    exit();
}

// 📍 Get Trip Details passed from the Dashboard
$origin = isset($_GET['origin']) ? htmlspecialchars($_GET['origin']) : 'Unknown';
$destination = isset($_GET['destination']) ? htmlspecialchars($_GET['destination']) : 'Unknown';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Driver QR | SafeRide</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        /* Custom styling for the camera container */
        #reader {
            border: none !important;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        #reader__dashboard_section_csr button {
            background-color: #4f46e5 !important;
            color: white !important;
            padding: 10px 20px !important;
            border-radius: 9999px !important;
            border: none !important;
            font-weight: 600 !important;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col items-center p-6">

    <div class="w-full max-w-md text-center mt-4">
        <h1 class="text-2xl font-bold text-gray-800">Scan Driver QR</h1>
        <p class="text-gray-500 text-sm mt-1">Point your camera at the driver's QR code to start the trip.</p>
    </div>

    <div class="w-full max-w-md mt-8">
        <div id="reader" class="bg-white"></div>
        
        <div class="mt-6 p-4 bg-indigo-50 rounded-2xl border border-indigo-100 flex items-center gap-3">
            <div class="bg-indigo-600 text-white w-8 h-8 rounded-full flex items-center justify-center shrink-0">
                <i class="fas fa-map-marker-alt text-xs"></i>
            </div>
            <div class="text-sm">
                <p class="text-indigo-400 font-bold uppercase text-[10px] tracking-widest">Selected Route</p>
                <p class="text-gray-700 font-semibold"><?php echo $origin; ?> ➜ <?php echo $destination; ?></p>
            </div>
        </div>

        <div id="status" class="mt-4 text-center text-indigo-600 font-medium text-sm animate-pulse">
            <i class="fas fa-camera mr-2"></i> Initializing camera...
        </div>
    </div>

    <div class="mt-8">
        <a href="dashboard.php" class="flex items-center gap-2 px-6 py-3 bg-white text-gray-600 font-bold rounded-2xl shadow-sm border border-gray-200 hover:bg-gray-50 transition">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <script>
        const statusDiv = document.getElementById("status");
        const tripOrigin = "<?php echo $origin; ?>";
        const tripDest = "<?php echo $destination; ?>";

        function onScanSuccess(qrMessage) {
            // Stop the scanner immediately upon detection
            html5QrCode.stop().then(() => {
                statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing trip details...';
            });

            // Data to send to process_scan.php
            const scanData = {
                qr_code: qrMessage,
                origin: tripOrigin,
                destination: tripDest
            };

            fetch("process_scan.php", {
                method: "POST",
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(scanData)
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === "success") {
                    statusDiv.textContent = "Trip started! SMS sent to guardian.";
                    window.location.href = data.redirect; // Redirects to trip_status.php
                } else {
                    alert("Error: " + data.message);
                    location.reload(); 
                }
            })
            .catch(err => {
                console.error(err);
                statusDiv.textContent = "Connection error. Please try again.";
            });
        }

        function onScanError(err) {
            // Keep empty to avoid console spamming
        }

        const html5QrCode = new Html5Qrcode("reader");

        html5QrCode.start(
            { facingMode: "environment" }, 
            { 
                fps: 10, 
                qrbox: { width: 250, height: 250 } 
            },
            onScanSuccess,
            onScanError
        ).then(() => {
            statusDiv.innerHTML = '<span class="text-green-600"><i class="fas fa-check-circle mr-2"></i> Ready to scan</span>';
        }).catch(err => {
            statusDiv.innerHTML = '<span class="text-red-500"><i class="fas fa-exclamation-triangle mr-2"></i> Camera access denied</span>';
            console.error("Camera error:", err);
        });
    </script>

</body>
</html>