<?php
// FINAL CSS FIX - Reads the local file and outputs corrected CSS with white background and black text

$cssContent = file_get_contents(__DIR__ . '/campanha.css');

// Now apply critical fixes to ensure white background and black text
$cssContent = preg_replace(
    '/\.modal-body\s*\{[^}]*\}/s',
    '.modal-body {
    flex: 1;
    padding: 32px 28px;
    color: #000000;
    overflow-y: auto;
    max-height: 65vh;
    background: #ffffff;
}',
    $cssContent
);

// Ensure modal-content is white
$cssContent = preg_replace(
    '/\.modal-content\s*\{[^}]*background:[^;]*;/s',
    '.modal-content {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    background: #ffffff;',
    $cssContent
);

// Ensure all modal labels are black
$cssContent = str_replace(
    '.modal-body .form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--videx-text);',
    '.modal-body .form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #000000;',
    $cssContent
);

// Ensure all modal inputs are black text
$cssContent = str_replace(
    'color: var(--videx-text);',
    'color: #000000;',
    $cssContent
);

// Write the corrected CSS
$result = file_put_contents(__DIR__ . '/campanha.css', $cssContent);

if ($result !== false) {
    echo "✓ SUCCESS - campanha.css has been REWRITTEN with:\n";
    echo "  - White background (#ffffff)\n";
    echo "  - Black text (#000000) for all labels and inputs\n";
    echo "  - File size: " . number_format($result) . " bytes\n\n";
    echo "Rewritten at: " . date('Y-m-d H:i:s') . "\n";
    echo "Now go to: https://videx.sms-app.co/index.php?doc=campanha&opc=2\n";
    echo "Clear your browser cache (Ctrl+Shift+Delete) and refresh\n";
} else {
    echo "✗ FAILED - Could not write to campanha.css\n";
    echo "File location: " . __DIR__ . '/campanha.css\n';
}
?>
