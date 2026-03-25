<?php
require_once __DIR__ . '/auth_guard.php';
header('Content-Type: application/json');

$_baseDir   = dirname(dirname(__DIR__));
$_assetsDir = $_baseDir . '/Assets/';

$skip = ['Admission.png', 'CRCY.png', 'admission.png'];

$orgs = [];
if (is_dir($_assetsDir)) {
    $files = scandir($_assetsDir);
    sort($files);
    foreach ($files as $f) {
        if (in_array($f, $skip)) continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png'])) continue;
        $path = $_assetsDir . $f;
        if (!file_exists($path)) continue;
        $mime = ($ext === 'png') ? 'image/png' : 'image/jpeg';
        $b64  = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        $name = pathinfo($f, PATHINFO_FILENAME);
        $orgs[] = ['file' => $f, 'name' => $name, 'b64' => $b64];
    }
}
echo json_encode($orgs);