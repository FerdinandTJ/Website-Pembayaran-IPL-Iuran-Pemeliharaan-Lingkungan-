<?php

// Debug info untuk melihat status file dan storage
echo "<h2>Debug Info - Storage dan File Upload</h2>";

echo "<h3>1. Storage Path Info:</h3>";
echo "<strong>Storage Path:</strong> " . storage_path() . "<br>";
echo "<strong>Public Storage Path:</strong> " . storage_path('app/public') . "<br>";
echo "<strong>Proofs Directory:</strong> " . storage_path('app/public/proofs') . "<br>";

echo "<h3>2. Public Storage Link:</h3>";
$publicStorageLink = public_path('storage');
echo "<strong>Public Storage Link:</strong> " . $publicStorageLink . "<br>";
echo "<strong>Link Exists:</strong> " . (is_link($publicStorageLink) ? 'Yes' : 'No') . "<br>";

echo "<h3>3. Proofs Directory Content:</h3>";
$proofsDir = storage_path('app/public/proofs');
if (is_dir($proofsDir)) {
    $files = scandir($proofsDir);
    if (count($files) > 2) { // . dan .. selalu ada
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "- " . $file . " (Size: " . filesize($proofsDir . '/' . $file) . " bytes)<br>";
            }
        }
    } else {
        echo "No files found in proofs directory<br>";
    }
} else {
    echo "Proofs directory does not exist<br>";
}

echo "<h3>4. Database Info:</h3>";
try {
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    $invoices = \App\Models\Invoice::where('is_paid', true)->where('payment_proof', '!=', null)->get();
    echo "<strong>Invoices with payment proof:</strong> " . $invoices->count() . "<br>";
    
    foreach ($invoices as $invoice) {
        $filePath = storage_path('app/public/' . $invoice->payment_proof);
        echo "- Invoice #{$invoice->id}: {$invoice->payment_proof} " . 
             "(Exists: " . (file_exists($filePath) ? 'Yes' : 'No') . ")<br>";
    }
} catch (Exception $e) {
    echo "Error connecting to database: " . $e->getMessage() . "<br>";
}

echo "<h3>5. URL Test:</h3>";
echo "<strong>Asset URL for test:</strong> " . asset('storage/proofs/test.jpg') . "<br>";

?>
