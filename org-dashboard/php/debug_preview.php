<?php
/**
 * debug_preview.php
 */
$conn = mysqli_connect("localhost", "root", "", "cig_system");

echo "<h2>All submissions</h2><pre>";
$r = mysqli_query($conn, "SELECT submission_id, file_name, submitted_by, user_id, LENGTH(file_content) as sz FROM submissions ORDER BY submission_id DESC");
while ($row = mysqli_fetch_assoc($r)) print_r($row);
echo "</pre>";

// Pick smallest DOCX to test HEX fetch
echo "<h2>HEX test on smallest DOCX</h2><pre>";
$r2 = mysqli_query($conn, "SELECT submission_id, file_name, LENGTH(file_content) as sz FROM submissions WHERE file_name LIKE '%.docx' ORDER BY LENGTH(file_content) ASC LIMIT 1");
$small = mysqli_fetch_assoc($r2);
echo "Testing: "; print_r($small);

if ($small) {
    $id = (int)$small['submission_id'];
    $r3 = mysqli_query($conn, "SELECT HEX(SUBSTRING(file_content,1,4)) as magic FROM submissions WHERE submission_id=$id");
    $m  = mysqli_fetch_assoc($r3);
    echo "Magic bytes hex: " . $m['magic'] . "\n";
    echo ($m['magic'] === '504B0304') ? "✅ Valid ZIP\n" : "❌ Bad magic: " . $m['magic'] . "\n";
}
echo "</pre>";

echo "<h2>Session check</h2><pre>";
session_start();
echo "session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "</pre>";

// Fix: show correct preview URL for each submission
echo "<h2>Test links (click each)</h2>";
$r4 = mysqli_query($conn, "SELECT submission_id, file_name, submitted_by FROM submissions ORDER BY submission_id DESC");
while ($row = mysqli_fetch_assoc($r4)) {
    echo "<a href='file_preview.php?submission_id={$row['submission_id']}' target='_blank'>"
       . "#{$row['submission_id']} — {$row['file_name']} (owner: user {$row['submitted_by']})</a><br>";
}
$conn->close();