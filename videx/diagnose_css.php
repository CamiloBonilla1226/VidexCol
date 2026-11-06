<?php
// This script will help diagnose and fix the CSS upload issue

// Read the local CSS file
$localCss = file_get_contents(__DIR__ . '/campanha.css');

// Check if it has the white background
if (strpos($localCss, 'background: #ffffff') !== false && strpos($localCss, 'color: #000000') !== false) {
    echo "✓ Local CSS has correct colors (white background, black text)\n\n";
} else {
    echo "✗ Local CSS is missing correct colors\n";
    exit;
}

// Now let's verify what's actually on the system
$modalBodyMatch = [];
preg_match('/\.modal-body\s*\{([^}]*background[^}]*)\}/', $localCss, $modalBodyMatch);

echo "Modal body section from local file:\n";
echo ".modal-body {" . (isset($modalBodyMatch[1]) ? $modalBodyMatch[1] : "NOT FOUND") . "}\n\n";

// Try to display the actual CSS file location on server
$possible_paths = [
    __DIR__ . '/campanha.css',
    '../videx.sms-app.co/campanha.css',
    '../../videx.sms-app.co/campanha.css',
    '/home/twsselvr/public_html/videx.sms-app.co/campanha.css'
];

echo "Checking file existence at various paths:\n";
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        echo "✓ Found at: $path\n";
        $serverContent = file_get_contents($path);

        // Check server version
        if (strpos($serverContent, 'background: #ffffff') !== false) {
            echo "  ✓ Has white background\n";
        } else {
            echo "  ✗ Missing white background\n";
        }

        if (strpos($serverContent, 'color: #000000') !== false) {
            echo "  ✓ Has black text\n";
        } else {
            echo "  ✗ Missing black text\n";
        }
    }
}

// Display file modification times
echo "\n\nFile modification times:\n";
echo "campanha.css: " . date('Y-m-d H:i:s', filemtime(__DIR__ . '/campanha.css')) . "\n";
?>
