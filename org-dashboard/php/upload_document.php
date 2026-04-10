<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
ob_start();

session_start();

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    http_response_code(401);
    header('Content-Type: application/json');
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Include database connection
require_once __DIR__ . '/db_connection.php';
if (!$conn) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    exit(json_encode(['success' => false, 'message' => 'Database connection failed']));
}
// Files are stored on disk, no BLOB size tuning needed

// Define template data function early
function getTemplateData($templateId) {
    $templates = [
        'meeting_minutes' => [
            'name' => 'Meeting Minutes',
            'fields' => [
                'meeting_date' => 'Meeting Date',
                'meeting_time' => 'Meeting Time',
                'location' => 'Location',
                'attendees' => 'Attendees (comma-separated)',
                'agenda' => 'Agenda',
                'discussion' => 'Discussion Summary',
                'action_items' => 'Action Items',
                'next_meeting' => 'Next Meeting Date'
            ]
        ],
        'event_proposal' => [
            'name' => 'Event Proposal',
            'fields' => [
                'event_name' => 'Event Name',
                'event_date' => 'Proposed Date',
                'event_time' => 'Event Time',
                'location' => 'Location/Venue',
                'objective' => 'Event Objective',
                'target_audience' => 'Target Audience',
                'expected_attendance' => 'Expected Number of Attendees',
                'budget' => 'Estimated Budget',
                'description' => 'Event Description',
                'requirements' => 'Special Requirements'
            ]
        ],
        'financial_report' => [
            'name' => 'Financial Report',
            'fields' => [
                'report_period' => 'Reporting Period',
                'opening_balance' => 'Opening Balance',
                'total_income' => 'Total Income',
                'total_expenses' => 'Total Expenses',
                'expense_breakdown' => 'Expense Breakdown',
                'closing_balance' => 'Closing Balance',
                'remarks' => 'Remarks/Notes'
            ]
        ],
        'incident_report' => [
            'name' => 'Incident Report',
            'fields' => [
                'incident_date' => 'Incident Date',
                'incident_time' => 'Incident Time',
                'location' => 'Location',
                'incident_description' => 'Incident Description',
                'individuals_involved' => 'Individuals Involved',
                'witnesses' => 'Witnesses',
                'action_taken' => 'Action Taken',
                'recommendations' => 'Recommendations'
            ]
        ],
        'membership_form' => [
            'name' => 'Membership Form',
            'fields' => [
                'full_name' => 'Full Name',
                'email' => 'Email Address',
                'phone' => 'Phone Number',
                'course_year' => 'Course and Year',
                'date_joined' => 'Date Joined',
                'membership_role' => 'Membership Role',
                'skills' => 'Skills/Expertise',
                'availability' => 'Availability for Activities'
            ]
        ],
        'project_proposal' => [
            'name' => 'Project Proposal',
            'fields' => [
                'proposal_date' => 'Date',
                'recipient_1' => 'First Recipient Name & Title',
                'recipient_2' => 'Second Recipient Name & Title',
                'dear_opening' => 'Dear [Recipient - Full Name with Title]',
                'opening_statement' => 'Opening Statement',
                'organization' => 'Organization',
                'project_title' => 'Project Title',
                'project_type' => 'Type of Project (Curricular / Non-Curricular / Off-Campus)',
                'project_involvement' => 'Project Involvement (Host / Collaboration / Participant)',
                'project_location' => 'Project Location',
                'proposed_start_date' => 'Proposed Start Date & Time',
                'proposed_end_date' => 'Proposed Completion Date',
                'number_participants' => 'Number of Participants',
                'project_summary' => 'A. SUMMARY OF THE PROJECT',
                'project_goal' => 'Goal',
                'project_objectives' => 'Objectives (numbered, one per line)',
                'expected_outputs' => 'C. EXPECTED OUTPUTS (bulleted)',
                'budget_source' => 'Source of Fund',
                'budget_partner' => 'Partner/Donation/Subsidy',
                'budget_total' => 'Total Project Cost',
                'monitoring_details' => 'Monitoring (bulleted)',
                'evaluation_details' => 'Evaluation Strategy (bulleted)',
                'security_plan' => 'V. SECURITY PLAN (bulleted)',
                'closing_statement'   => 'Closing Statement',
                'sender_name'         => 'Sender Name & Title',
                'adviser_name'        => 'Noted by – Adviser (Name, Title, Org)',
                'co_adviser_name'     => 'Noted by – Co-Adviser (Name, Title, Org)',
                'additional_signer_1' => 'Additional Noted by #1',
                'additional_signer_2' => 'Additional Noted by #2',
                'endorsed_by'         => 'Endorsed by (name and title)'
            ]
        ]
    ];
    
    return $templates[$templateId] ?? null;
}

// Check if this is a template upload or regular file upload
$isTemplateUpload = isset($_POST['template_id']) && !empty($_POST['template_id']);

if ($isTemplateUpload) {
    // Handle template document generation and upload
    handleTemplateUpload($conn);
} else {
    // Handle regular file upload
    handleRegularUpload($conn);
}

function handleTemplateUpload($conn) {
    try {
        $templateId = isset($_POST['template_id']) ? trim($_POST['template_id']) : null;
        $title = isset($_POST['title']) ? trim($_POST['title']) : 'Document';
        $organizationName = isset($_POST['organization_name']) ? trim($_POST['organization_name']) : null;
        $organizationTagline = isset($_POST['organization_tagline']) ? trim($_POST['organization_tagline']) : null;
        $collaboratedLogo = isset($_POST['collaborated_logo_value']) ? trim($_POST['collaborated_logo_value']) : null;
        // JS sends 'collaborated_logo' (not 'collaborated_logo_value') — check both
        if (empty($collaboratedLogo)) {
            $collaboratedLogo = isset($_POST['collaborated_logo']) ? trim($_POST['collaborated_logo']) : null;
        }
        if (empty($collaboratedLogo)) $collaboratedLogo = null;

        // Tagline is optional — use empty string if blank
        if (empty(trim($organizationTagline ?? ''))) $organizationTagline = '';

        error_log("Template Upload Debug: templateId='$templateId', title='$title'");

        // Validate required fields
        if (empty($templateId) || empty($title)) {
            throw new Exception('Please select a template and enter a document title');
        }
        if (empty($organizationName)) {
            throw new Exception('Organization name is required');
        }

        // Resolve org logo path from DB
        $orgLogoPath = null;
        $userId = (int)$_SESSION['user_id'];
        $logoStmt = mysqli_prepare($conn, "SELECT logo_path FROM users WHERE user_id = ? LIMIT 1");
        mysqli_stmt_bind_param($logoStmt, 'i', $userId);
        mysqli_stmt_execute($logoStmt);
        $logoRow = mysqli_fetch_assoc(mysqli_stmt_get_result($logoStmt));
        mysqli_stmt_close($logoStmt);
        if (!empty($logoRow['logo_path'])) {
            // logo_path stored as e.g. "../uploads/logos/file.png" relative to php/
            // Try resolving from php/ first, then from cig_user/ base
            $_upBase = dirname(dirname(__DIR__)); // cig_user/
            $logoAbs = realpath(__DIR__ . '/' . $logoRow['logo_path']);
            if (!$logoAbs || !file_exists($logoAbs)) {
                $logoAbs = realpath($_upBase . '/' . ltrim($logoRow['logo_path'], './'));
            }
            if ($logoAbs && file_exists($logoAbs)) $orgLogoPath = $logoAbs;
        }
        
        // Get template data
        $template = getTemplateData($templateId);
        if (!$template) {
            error_log("Invalid template ID: '$templateId'");
            throw new Exception('Invalid template. Please select a valid template.');
        }
        
        // Include the unified document generation function
        include __DIR__ . '/generate_document.php';
        
        // Collect all template field data
        $data = [];
        foreach ($template['fields'] as $fieldId => $fieldLabel) {
            $data[$fieldId] = $_POST[$fieldId] ?? '';
        }

        // ── Lock recipient_1 & recipient_2 from site_settings (super admin only) ──
        // These fields are never editable by org users — always pulled from DB.
        if ($templateId === 'project_proposal') {
            $_ssLock = [];
            $_ssLockQ = mysqli_query($conn, "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('dean_name','dean_title','president_name','president_title')");
            while ($_ssLockRow = mysqli_fetch_assoc($_ssLockQ)) {
                $_ssLock[$_ssLockRow['setting_key']] = trim($_ssLockRow['setting_value']);
            }
            $data['recipient_1'] = $_ssLock['president_name'] ?? 'Name of University President';
            $data['recipient_2'] = $_ssLock['dean_name']      ?? 'Name of Dean';
            // Also store titles for DOCX rendering
            $data['recipient_1_title'] = $_ssLock['president_title'] ?? 'Interim University President';
            $data['recipient_2_title'] = $_ssLock['dean_title']      ?? 'Dean, Office of Student Affairs and Services';
        }
        // Collect dynamic additional signers (not in template definition)
        for ($si = 1; $si <= 5; $si++) {
            $key = 'additional_signer_' . $si;
            if (!empty($_POST[$key]) && !isset($data[$key])) {
                $data[$key] = trim($_POST[$key]);
                $template['fields'][$key] = 'Additional Noted by #' . $si;
            }
        }
        // Defensive: always pull adviser fields from POST even if not in $data yet
        foreach (['adviser_name', 'co_adviser_name'] as $_k) {
            if (!isset($data[$_k]) && !empty($_POST[$_k])) {
                $data[$_k] = trim($_POST[$_k]);
            }
        }

        // Determine output format: 'docx' (default) or 'pdf'
        $format = (isset($_POST['output_format']) && strtolower($_POST['output_format']) === 'pdf') ? 'pdf' : 'docx';

        // Generate the document (no control number yet — assigned only upon final approval)
        if (!function_exists('generateDocument')) {
            throw new Exception('generateDocument() not loaded — check generate_document.php path and syntax.');
        }
        $generatedPath = generateDocument($template, $data, $title, $format, $collaboratedLogo, $organizationName, $organizationTagline, $orgLogoPath, null);

        if (!$generatedPath || !file_exists($generatedPath)) {
            $zipOk = class_exists('ZipArchive') ? 'yes' : 'NO — enable zip extension';
            throw new Exception('Document generation failed. ZipArchive: ' . $zipOk . ' | Temp: ' . sys_get_temp_dir());
        }

        // Get user info
        $userId = $_SESSION['user_id'];
        $submittedBy = $_SESSION['user_id'];

        // org_id references users.user_id in this schema — use the submitting user's own id
        $orgId = $userId;

        // Document filename based on chosen format
        $fileName = uniqid('doc_') . '_' . preg_replace('/[^a-z0-9]/i', '_', $title) . '.' . $format;
        
        // Save file to disk
        $uploadDir = __DIR__ . '/../uploads/submissions/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filePath = $uploadDir . $fileName;
        if (!rename($generatedPath, $filePath)) {
            copy($generatedPath, $filePath);
            @unlink($generatedPath);
        }
        if (!file_exists($filePath)) {
            throw new Exception('Failed to save generated document to disk');
        }
        
        // Store relative path for DB
        $dbFilePath = '../uploads/submissions/' . $fileName;
        
        $description = "Template: " . $template['name'] . " | Organization: " . htmlspecialchars($organizationName);

        // Build JSON snapshot of all submitted field values for the preview modal
        $submissionData = json_encode([
            'template_id'          => $templateId,
            'template_name'        => $template['name'],
            'organization_name'    => $organizationName,
            'organization_tagline' => $organizationTagline,
            'collaborated_logo'    => $collaboratedLogo,
            'fields'               => $data,
            'field_labels'         => $template['fields'],
        ], JSON_UNESCAPED_UNICODE);

        // Control number is NOT assigned at upload time.
        // It will be generated only when the Super Admin sets status to 'approved'.

        // Insert into submissions table with file path and JSON snapshot
        $stmt = mysqli_prepare($conn, "INSERT INTO submissions (user_id, org_id, title, description, submission_data, status, file_name, file_path, submitted_by) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        
        // Bind parameters: i=user_id, i=org_id, s=title, s=description, s=submission_data, s=file_name, s=file_path, i=submitted_by
        $stmt->bind_param("iissssi", $userId, $orgId, $title, $description, $submissionData, $fileName, $dbFilePath, $submittedBy);
        
        if (!$stmt->execute()) {
            $error = $stmt->error;
            // Check for packet size error and provide helpful message
            if (stripos($error, 'packet') !== false || stripos($error, 'lost connection') !== false) {
                throw new Exception('File is too large or connection was lost. Try a smaller file (max 50MB). Error: ' . $error);
            }
            throw new Exception('Execute failed: ' . $error);
        }
        
        $submissionId = $stmt->insert_id;
        $stmt->close();
        
        // Get user name
        $userStmt = mysqli_prepare($conn, "SELECT full_name FROM users WHERE user_id = ?");
        if (!$userStmt) {
            throw new Exception('Select failed: ' . $conn->error);
        }
        
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $userData = $userResult->fetch_assoc();
        $userStmt->close();
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'submitted_by' => $userData['full_name'] ?? 'User',
            'filename' => $fileName,
            'submission_id' => $submissionId,
            'control_number' => null,
            'submission_data' => $submissionData ?? null
        ]);
        exit;
    } catch (Exception $e) {
        ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json');
        exit(json_encode(['success' => false, 'message' => $e->getMessage()]));
    }
}

function handleRegularUpload($conn) {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        ob_end_clean();
        http_response_code(400);
        header('Content-Type: application/json');
        exit(json_encode(['success' => false, 'message' => 'Please select a file to upload']));
    }
    
    $title = isset($_POST['title']) ? trim($_POST['title']) : 'Document';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $relatedEvent = isset($_POST['related_event']) ? trim($_POST['related_event']) : '';
    
    if (empty($title)) {
        ob_end_clean();
        http_response_code(400);
        header('Content-Type: application/json');
        exit(json_encode(['success' => false, 'message' => 'Document title is required']));
    }
    
    // Validate file
    $allowedExtensions = ['pdf', 'docx', 'xlsx'];
    $fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        ob_end_clean();
        http_response_code(400);
        header('Content-Type: application/json');
        exit(json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: PDF, DOCX, XLSX']));
    }
    
    if ($_FILES['file']['size'] > 50 * 1024 * 1024) { // 50MB limit
        ob_end_clean();
        http_response_code(400);
        header('Content-Type: application/json');
        exit(json_encode(['success' => false, 'message' => 'File size exceeds 50MB limit']));
    }
    
    // Get user info
    $userId = $_SESSION['user_id'];
    $submittedBy = $_SESSION['user_id'];
    // org_id references users.user_id in this schema — use the submitting user's own id
    $orgId = $userId;
    
    // Document filename
    $fileName = uniqid('doc_') . '_' . preg_replace('/[^a-z0-9]/i', '_', $title) . '.' . $fileExtension;
    
    // Save file to disk
    $uploadDir = __DIR__ . '/../uploads/submissions/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $diskPath = $uploadDir . $fileName;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $diskPath)) {
        ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json');
        exit(json_encode(['success' => false, 'message' => 'Failed to save uploaded file to disk']));
    }
    $dbFilePath = '../uploads/submissions/' . $fileName;
    
    // Build full description
    $fullDescription = $description;
    if ($relatedEvent) {
        $fullDescription .= ($description ? ' | ' : '') . 'Related Event: ' . htmlspecialchars($relatedEvent);
    }
    
    try {
        // Control number is NOT assigned at upload time.
        // It will be generated only when Super Admin sets status to 'approved'.

        // Insert into submissions table with file path
        $stmt = mysqli_prepare($conn, "INSERT INTO submissions (user_id, org_id, title, description, status, file_name, file_path, submitted_by) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        
        // Bind parameters: i=user_id, i=org_id, s=title, s=description, s=file_name, s=file_path, i=submitted_by
        $stmt->bind_param("iisssi", $userId, $orgId, $title, $fullDescription, $fileName, $dbFilePath, $submittedBy);
        
        if (!$stmt->execute()) {
            $error = $stmt->error;
            // Check for packet size error and provide helpful message
            if (stripos($error, 'packet') !== false || stripos($error, 'lost connection') !== false) {
                throw new Exception('File is too large or connection was lost. Try a smaller file (max 50MB). Error: ' . $error);
            }
            throw new Exception('Execute failed: ' . $error);
        }
        
        $submissionId = $stmt->insert_id;
        $stmt->close();
        
        // Get user name
        $userStmt = mysqli_prepare($conn, "SELECT full_name FROM users WHERE user_id = ?");
        if (!$userStmt) {
            throw new Exception('User select failed: ' . $conn->error);
        }
        
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $userData = $userResult->fetch_assoc();
        $userStmt->close();
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'submitted_by' => $userData['full_name'] ?? 'User',
            'filename' => $fileName,
            'submission_id' => $submissionId,
            'control_number' => null
        ]);
        exit;
    } catch (Exception $e) {
        ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json');
        exit(json_encode(['success' => false, 'message' => $e->getMessage()]));
    }
}

// Close database connection
mysqli_close($conn);
?>