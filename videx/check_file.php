<?php
$file = '/var/www/videx.sms-app.co/js/sms_grupos.js';
if (file_exists($file)) {
    echo "File exists: " . filesize($file) . " bytes\n";
    echo "First 200 chars:\n";
    echo substr(file_get_contents($file), 0, 200) . "\n\n";
    echo "Last 200 chars:\n";
    echo substr(file_get_contents($file), -200) . "\n";
} else {
    echo "File does not exist\n";
}
?>
