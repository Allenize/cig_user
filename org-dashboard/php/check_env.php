<?php
header('Content-Type: text/plain');
echo "GD: "         . (extension_loaded('gd')      ? 'YES' : 'NO') . "\n";
echo "Imagick: "    . (class_exists('Imagick')      ? 'YES' : 'NO') . "\n";
echo "ZipArchive: " . (class_exists('ZipArchive')   ? 'YES' : 'NO') . "\n";
echo "COM: "        . (class_exists('COM')          ? 'YES' : 'NO') . "\n";

if (extension_loaded('gd')) {
    $info = gd_info();
    echo "GD Version: "   . $info['GD Version']   . "\n";
    echo "PNG Support: "  . ($info['PNG Support']  ? 'YES' : 'NO') . "\n";
    echo "JPEG Support: " . ($info['JPEG Support'] ? 'YES' : 'NO') . "\n";
}

$conn = mysqli_connect("localhost", "root", "", "cig_system");
$r    = mysqli_query($conn, "SELECT submission_id, file_name FROM submissions WHERE file_name LIKE '%.docx' ORDER BY submission_id DESC LIMIT 1");
$row  = mysqli_fetch_assoc($r);
echo "\nLatest DOCX id=" . $row['submission_id'] . " file=" . $row['file_name'] . "\n";

$r2   = mysqli_query($conn, "SELECT file_content FROM submissions WHERE submission_id=" . (int)$row['submission_id']);
$row2 = mysqli_fetch_assoc($r2);
$tmp  = tempnam(sys_get_temp_dir(), 'docx_');
file_put_contents($tmp, $row2['file_content']);

$zip = new ZipArchive();
$zip->open($tmp);
echo "\nMedia files:\n";
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (strpos($name, 'word/media/') === 0) {
        $data  = $zip->getFromIndex($i);
        $ext   = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $magic = bin2hex(substr($data, 0, 4));
        echo "  $name ($ext) size=" . strlen($data) . " magic=$magic\n";
    }
}
$zip->close();
unlink($tmp);
mysqli_close($conn);    