<?php
// docx_info.php - place in org-dashboard/php/
// Shows DB info about a submission without sending binary
session_start();
header('Content-Type: text/plain');

$conn = mysqli_connect("localhost", "root", "", "cig_system");
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

echo "session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "requested id: $id\n";

$r = mysqli_query($conn, "SELECT submission_id, file_name, submitted_by, user_id, LENGTH(file_content) as blob_sz FROM submissions WHERE submission_id=$id");
$row = mysqli_fetch_assoc($r);
if (!$row) { echo "ROW NOT FOUND\n"; exit; }

echo "submitted_by: " . $row['submitted_by'] . "\n";
echo "user_id col: "  . $row['user_id']      . "\n";
echo "file_name: "    . $row['file_name']     . "\n";
echo "blob_size: "    . $row['blob_sz']       . " bytes\n";
echo "session match: " . ((int)$row['submitted_by'] === (int)($_SESSION['user_id']??-1) ? 'YES ✅' : 'NO ❌') . "\n";

// Check if mysqli returns blob
$r2 = mysqli_query($conn, "SELECT SUBSTRING(file_content,1,4) as magic FROM submissions WHERE submission_id=$id");
$r2row = mysqli_fetch_assoc($r2);
$magic = bin2hex($r2row['magic'] ?? '');
echo "magic bytes: $magic\n";
echo ($magic === '504b0304' ? "✅ Valid ZIP\n" : "❌ NOT valid ZIP - corrupted on read\n");












