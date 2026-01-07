<?php

$testSite = "/home/master/applications/vvkvmagmmv/public_html";
$wpConfigPath = $testSite . "/wp-config.php";

// Backup wp-config.php
if (!copy($wpConfigPath, $wpConfigPath . ".bak")) {
    echo "Error creating backup!\n";
    exit(1);
}

$newSalts = file_get_contents('https://api.wordpress.org/secret-key/1.1/salt/');
if ($newSalts === false) {
    echo "Error fetching salts!\n";
    exit(1);
}

$newCacheKeySalt = bin2hex(random_bytes(6));

$wpConfigLines = file($wpConfigPath, FILE_IGNORE_NEW_LINES);

// 1. Remove require('wp-salt.php'); if it exists
$requireIndex = array_search("require('wp-salt.php');", $wpConfigLines);
if ($requireIndex !== false) {
    unset($wpConfigLines[$requireIndex]);
    $wpConfigLines = array_values($wpConfigLines); // Re-index the array
}

// 2. Handle existing defined salts (same as before)
$saltStartIndex = array_search("#@+", $wpConfigLines);
if ($saltStartIndex !== false) {
    $saltEndIndex = $saltStartIndex;
    while (!in_array($wpConfigLines[$saltEndIndex], ["", " */"], true)) {
        $saltEndIndex++;
         if(!isset($wpConfigLines[$saltEndIndex])){
            echo "Could not find end of salt section\n";
            exit(1);
        }
    }


    array_splice($wpConfigLines, $saltStartIndex + 1, $saltEndIndex - $saltStartIndex - 1);  // Remove old salts

    $newSaltLines = explode("\n", $newSalts);
    array_push($newSaltLines, "define('WP_CACHE_KEY_SALT', '$newCacheKeySalt');");
    array_splice($wpConfigLines, $saltStartIndex + 1, 0, $newSaltLines); //Insert at correct location

} else {
    // If the #@+ comment isn't found, add new salts at the end of the config file
    echo "Salt section not found. Adding salts to the end of wp-config.php.\n";
    $wpConfigLines[] = $newSalts;
    $wpConfigLines[] = "define('WP_CACHE_KEY_SALT', '$newCacheKeySalt');";
}


if (file_put_contents($wpConfigPath, implode("\n", $wpConfigLines)) === false) {
    echo "Error writing to wp-config.php!\n";
    exit(1);
}

// ... (Redis token update code if needed) ...
// ... (cache flush) ...


echo "wp-config.php updated successfully!\n";

?>
