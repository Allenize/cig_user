<?php
// get_token.php — place in org-dashboard/php/
// Visit: http://localhost:3000/org-dashboard/php/get_token.php?id=13
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 13;
$secret = 'cig_preview_2026';
$token  = sha1($id . date('YmdH') . $secret);
header('Content-Type: text/plain');
echo $token;