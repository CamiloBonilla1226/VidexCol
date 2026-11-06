<?php
// Check CSS on server via current document root
$cssPath = __DIR__ . '/campanha.css';
if (file_exists($cssPath)) {
    $content = file_get_contents($cssPath);

    // Extract modal-body section
    preg_match('/\.modal-body\s*\{([^}]+)\}/', $content, $match);

    if (!empty($match)) {
        echo "=== LOCAL .modal-body CSS ===\n";
        echo ".modal-body {" . $match[1] . "}\n\n";

        // Check for #ffffff
        if (strpos($match[1], '#ffffff') !== false || strpos($match[1], 'white') !== false) {
            echo "✓ WHITE BACKGROUND FOUND\n";
        } else {
            echo "✗ WHITE BACKGROUND NOT FOUND\n";
        }

        // Check for #000000 text
        if (strpos($match[1], '#000000') !== false || strpos($content, 'color: #000000') !== false) {
            echo "✓ BLACK TEXT FOUND\n";
        } else {
            echo "✗ BLACK TEXT NOT FOUND\n";
        }
    } else {
        echo "✗ .modal-body NOT FOUND IN CSS\n";
    }
} else {
    echo "✗ campanha.css not found at: " . $cssPath . "\n";
}
?>
