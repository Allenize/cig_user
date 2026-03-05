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
$conn = mysqli_connect("localhost", "root", "", "cig_system");
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
                'closing_statement' => 'Closing Statement',
                'sender_name' => 'Sender Name & Title',
                'noted_by' => 'Noted by (comma-separated names with titles)',
                'endorsed_by' => 'Endorsed by (name and title)'
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
        // Get and validate required POST data
        $templateId = isset($_POST['template_id']) ? trim($_POST['template_id']) : null;
        $title = isset($_POST['title']) ? trim($_POST['title']) : 'Document';
        $organizationName = isset($_POST['organization_name']) ? trim($_POST['organization_name']) : null;
        $organizationTagline = isset($_POST['organization_tagline']) ? trim($_POST['organization_tagline']) : null;
        $collaboratedLogo = isset($_POST['collaborated_logo']) ? trim($_POST['collaborated_logo']) : null;
        
        error_log("Template Upload Debug: templateId='$templateId', title='$title'");
        
        // Validate required fields
        if (empty($templateId) || empty($title)) {
            throw new Exception('Please select a template and enter a document title');
        }
        
        if (empty($organizationName)) {
            throw new Exception('Organization name is required');
        }
        
        if (empty($organizationTagline)) {
            throw new Exception('Organization tagline is required');
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

        // Determine output format: 'docx' (default) or 'pdf'
        $format = (isset($_POST['output_format']) && strtolower($_POST['output_format']) === 'pdf') ? 'pdf' : 'docx';

        // Generate the document
        if (!function_exists('generateDocument')) {
            throw new Exception('generateDocument() not loaded — check generate_document.php path and syntax.');
        }
        $generatedPath = generateDocument($template, $data, $title, $format, $collaboratedLogo, $organizationName, $organizationTagline);

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

        // Insert into submissions table with file path and JSON snapshot
        $stmt = $conn->prepare("INSERT INTO submissions (user_id, org_id, title, description, submission_data, status, file_name, file_path, submitted_by) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        
        // Bind parameters: int, int, string, string, string, string, string, int
        $stmt->bind_param("iisssssi", $userId, $orgId, $title, $description, $submissionData, $fileName, $dbFilePath, $submittedBy);
        
        if (!$stmt->execute()) {
            $error = $stmt->error;
            // Check for packet size error and provide helpful message
            if (stripos($error, 'packet') !== false || stripos($error, 'lost connection') !== false) {
                throw new Exception('File is too large or connection was lost. Try a smaller file (max 10MB). Error: ' . $error);
            }
            throw new Exception('Execute failed: ' . $error);
        }
        
        $submissionId = $stmt->insert_id;
        $stmt->close();
        
        // Get user name
        $userStmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
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
    
    if ($_FILES['file']['size'] > 10 * 1024 * 1024) { // 10MB limit
        ob_end_clean();
        http_response_code(400);
        header('Content-Type: application/json');
        exit(json_encode(['success' => false, 'message' => 'File size exceeds 10MB limit']));
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
        // Insert into submissions table with file path
        $stmt = $conn->prepare("INSERT INTO submissions (user_id, org_id, title, description, status, file_name, file_path, submitted_by) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        
        // Bind parameters: int, int, string, string, string, string, int
        $stmt->bind_param("iissssi", $userId, $orgId, $title, $fullDescription, $fileName, $dbFilePath, $submittedBy);
        
        if (!$stmt->execute()) {
            $error = $stmt->error;
            // Check for packet size error and provide helpful message
            if (stripos($error, 'packet') !== false || stripos($error, 'lost connection') !== false) {
                throw new Exception('File is too large or connection was lost. Try a smaller file (max 10MB). Error: ' . $error);
            }
            throw new Exception('Execute failed: ' . $error);
        }
        
        $submissionId = $stmt->insert_id;
        $stmt->close();
        
        // Get user name
        $userStmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
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
            'submission_id' => $submissionId
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