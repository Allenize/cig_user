<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit();
}

require_once dirname(dirname(__DIR__)) . '/db_connection.php';
$userId = $_SESSION['user_id'];

$fields = ['org_name', 'org_code', 'contact_person', 'phone', 'description'];
$labels = [
    'org_name'       => 'Organization Name',
    'org_code'       => 'Organization Code',
    'contact_person' => 'Contact Person',
    'phone'          => 'Contact Number',
    'description'    => 'Organization Tagline / Mission',
];

$data        = [];
$missing     = [];
$missingKeys = [];

foreach ($fields as $field) {
    $val = trim($_POST[$field] ?? '');
    if (empty($val)) {
        $missing[]     = $labels[$field] . ' is required.';
        $missingKeys[] = $field;
    }
    $data[$field] = $val;
}

if (!empty($missing)) {
    echo json_encode([
        'success'      => false,
        'message'      => 'Please fill in all required credentials before proceeding:',
        'missing'      => $missing,
        'missing_keys' => $missingKeys,
    ]);
    exit();
}

// Save credentials and mark as verified
$stmt = mysqli_prepare($conn,
    "UPDATE users SET
        org_name = ?,
        org_code = ?,
        contact_person = ?,
        phone = ?,
        description = ?,
        credentials_verified = 1
     WHERE user_id = ?"
);
mysqli_stmt_bind_param($stmt, 'sssssi',
    $data['org_name'],
    $data['org_code'],
    $data['contact_person'],
    $data['phone'],
    $data['description'],
    $userId
);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($ok) {
    // Update session
    $_SESSION['org_name']             = $data['org_name'];
    $_SESSION['org_code']             = $data['org_code'];
    $_SESSION['credentials_verified'] = true;

    echo json_encode(['success' => true, 'message' => 'Credentials verified successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}
?>