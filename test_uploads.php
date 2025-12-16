<?php
// test_upload.php
$upload_dir = "uploads/destinations/";

echo "Testing upload directory: $upload_dir<br>";
echo "Directory exists: " . (file_exists($upload_dir) ? 'YES' : 'NO') . "<br>";
echo "Directory is writable: " . (is_writable($upload_dir) ? 'YES' : 'NO') . "<br>";

// Try to create a test file
$test_file = $upload_dir . "test.txt";
if (file_put_contents($test_file, "test content")) {
    echo "✅ SUCCESS: Can write to directory!<br>";
    echo "Test file created: $test_file<br>";
    
    // Clean up
    unlink($test_file);
    echo "Test file cleaned up.<br>";
} else {
    echo "❌ FAILED: Cannot write to directory.<br>";
    echo "Error: " . error_get_last()['message'] . "<br>";
}

// Also test with move_uploaded_file simulation
echo "<hr><h3>Testing move_uploaded_file simulation:</h3>";
$temp_file = tempnam(sys_get_temp_dir(), 'test');
file_put_contents($temp_file, "fake upload content");
$target_file = $upload_dir . "upload_test.txt";

if (move_uploaded_file($temp_file, $target_file)) {
    echo "✅ move_uploaded_file would work!<br>";
    unlink($target_file);
} else {
    // Since it's not a real uploaded file, rename instead
    if (rename($temp_file, $target_file)) {
        echo "✅ rename() works (similar to move_uploaded_file)<br>";
        unlink($target_file);
    } else {
        echo "❌ Cannot move files to directory<br>";
    }
}

echo '<hr><a href="admin/add_destination.php">Try Add Destination Page</a>';
?>