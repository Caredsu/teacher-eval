<?php
echo "<h1>Server Diagnostics</h1>";

echo "<h3>PHP Version:</h3>";
echo phpversion() . "<br><br>";

echo "<h3>Loaded Extensions:</h3>";
$extensions = get_loaded_extensions();
echo "<pre>";
foreach ($extensions as $ext) {
    echo $ext . "\n";
}
echo "</pre>";

echo "<h3>MongoDB Extension Available?</h3>";
if (extension_loaded('mongodb')) {
    echo "<span style='color: green;'>✓ YES - MongoDB extension available</span>";
} else {
    echo "<span style='color: red;'>✗ NO - MongoDB extension NOT available (this is the problem)</span>";
}

echo "<br><br><h3>Alternative Solution:</h3>";
echo "Use REST API to connect instead of PHP driver";
?>
