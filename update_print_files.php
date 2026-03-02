<?php
$files = [
    'ppe_table_print.php',
    'ppe_remittance_print.php',
    'ppe_check_issued_print.php',
    'ppe_print.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }

    $content = file_get_contents($file);

    // Update table font-size to 14px
    $content = preg_replace('/table\s*\{\s*[^}]*font-size:\s*\d+px;/', "table {\n            width: 100%;\n            border-collapse: collapse;\n            font-size: 14px;", $content);

    // Update th font-size to 14px
    $content = preg_replace('/th\s*\{\s*[^}]*font-size:\s*\d+px;/', "th {\n            background-color: #f0f0f0;\n            font-weight: bold;\n            font-size: 14px;", $content);

    // Update H1 font size
    $content = preg_replace('/h1 style="font-size: \d+px;/', 'h1 style="font-size: 20px;', $content);

    // Update H2 font size
    $content = preg_replace('/h2 style="font-size: \d+px;/', 'h2 style="font-size: 18px;', $content);

    // Update date/info div font size
    $content = preg_replace('/div style="font-size: \d+px; color: black;/', 'div style="font-size: 14px; color: black;', $content);

    // Update Prepared by section font sizes
    $content = preg_replace('/p style="font-size: \d+px; margin: 0;"/', 'p style="font-size: 14px; margin: 0;"', $content);
    $content = preg_replace('/p style="margin-top: 30px; font-size: \d+px; margin-bottom: 0; font-weight: bold;"/', 'p style="margin-top: 30px; font-size: 14px; margin-bottom: 0; font-weight: bold;"', $content);

    // Ensure page-break-after is auto
    $content = str_replace('page-break-after: always;', 'page-break-after: auto;', $content);

    // Add window.print if missing
    if (strpos($content, 'window.print()') === false) {
        $script = "    <script>\n        window.onload = function() {\n            window.print();\n        };\n    </script>\n</head>";
        $content = str_replace('</head>', $script, $content);
    }

    file_put_contents($file, $content);
    echo "Updated $file\n";
}
?>
