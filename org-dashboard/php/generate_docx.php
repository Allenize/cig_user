<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../php/templates.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$template_id = $_POST['template_id'] ?? null;
$template_data = $_POST['template_data'] ?? [];
$title = $_POST['title'] ?? 'Document';

// Get the actual field names from POST data
$template_id = $_POST['template_id'] ?? null;
$title = $_POST['title'] ?? 'Document';
$collaborated_logo = $_POST['collaborated_logo'] ?? null;
$organization_name = $_POST['organization_name'] ?? 'PLSP Economics Society – EcoS';
$organization_tagline = $_POST['organization_tagline'] ?? 'Empowered and committed organization of service.';

if (!$template_id) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['success' => false, 'message' => 'Template ID required']));
}

$template = getTemplate($template_id);
if (!$template) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['success' => false, 'message' => 'Invalid template']));
}

// Collect template data from POST
$template_data = [];
foreach ($template['fields'] as $fieldId => $fieldLabel) {
    $template_data[$fieldId] = $_POST[$fieldId] ?? '';
}

// Generate DOCX content
$docxPath = generateDocx($template, $template_data, $title, $collaborated_logo, $organization_name, $organization_tagline);

if ($docxPath && file_exists($docxPath)) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-z0-9]/i', '_', $title) . '.docx"');
    header('Content-Length: ' . filesize($docxPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    readfile($docxPath);
    
    // Clean up temp file
    @unlink($docxPath);
    exit;
} else {
    http_response_code(500);
    header('Content-Type: application/json');
    exit(json_encode(['success' => false, 'message' => 'Failed to generate document']));
}

function generateDocx($template, $data, $title, $collaborated_logo = null, $organization_name = null, $organization_tagline = null) {
    $tempDir = sys_get_temp_dir();
    $fileName = uniqid('doc_') . '.docx';
    $docxPath = $tempDir . '/' . $fileName;
    
    // Create the DOCX package structure manually
    if (!class_exists('ZipArchive')) {
        return false;
    }
    
    $zip = new ZipArchive();
    if ($zip->open($docxPath, ZipArchive::CREATE) !== true) {
        return false;
    }
    
    // Create document.xml content
    $xmlContent = createDocumentXml($title, $template, $data, $collaborated_logo, $organization_name, $organization_tagline);
    
    // Add necessary files to ZIP
    $zip->addFromString('[Content_Types].xml', getContentTypes());
    $zip->addFromString('_rels/.rels', getRelationships());
    $zip->addFromString('word/_rels/document.xml.rels', getDocumentRelationships($collaborated_logo));
    $zip->addFromString('word/document.xml', $xmlContent);
    
    // Add logo image if it exists
    $logoPath = __DIR__ . '/../../plsplogo.png';
    if (file_exists($logoPath)) {
        $imageData = file_get_contents($logoPath);
        if ($imageData) {
            $zip->addFromString('word/media/image1.png', $imageData);
        }
    }
    
    // Add collaborated logo if provided
    $imageId = 2;
    if ($collaborated_logo) {
        $collaboratedLogoPath = __DIR__ . '/../../' . basename($collaborated_logo);
        if (file_exists($collaboratedLogoPath)) {
            $imageData = file_get_contents($collaboratedLogoPath);
            if ($imageData) {
                $ext = pathinfo($collaboratedLogoPath, PATHINFO_EXTENSION);
                $zip->addFromString('word/media/image2.' . $ext, $imageData);
                $imageId = 2;
            }
        }
    }
    
    $zip->close();
    
    return $docxPath;
}

function buildProjectProposalContent($data) {
    $content = '';
    
    // Add proposal date
    if (!empty($data['proposal_date'])) {
        $content .= '<w:p>';
        $content .= '<w:pPr><w:jc w:val="right"/></w:pPr>';
        $content .= '<w:r><w:t>' . htmlspecialchars($data['proposal_date']) . '</w:t></w:r>';
        $content .= '</w:p>';
        $content .= '<w:p/>';
    }
    
    // Add recipient info
    if (!empty($data['recipient_1'])) {
        $content .= '<w:p>';
        $content .= '<w:r><w:t>' . htmlspecialchars($data['recipient_1']) . '</w:t></w:r>';
        $content .= '</w:p>';
    }
    if (!empty($data['recipient_2'])) {
        $content .= '<w:p>';
        $content .= '<w:r><w:t>' . htmlspecialchars($data['recipient_2']) . '</w:t></w:r>';
        $content .= '</w:p>';
    }
    $content .= '<w:p/>';
    
    // Add dear opening
    if (!empty($data['dear_opening'])) {
        $content .= '<w:p>';
        $content .= '<w:r><w:t>' . htmlspecialchars($data['dear_opening']) . '</w:t></w:r>';
        $content .= '</w:p>';
    }
    
    // Add opening statement
    if (!empty($data['opening_statement'])) {
        $content .= '<w:p>';
        $content .= '<w:r><w:t>' . htmlspecialchars($data['opening_statement']) . '</w:t></w:r>';
        $content .= '</w:p>';
        $content .= '<w:p/>';
    }
    
    // I. IDENTIFYING INFORMATION TABLE
    $content .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="24"/></w:rPr><w:t>I. Identifying Information</w:t></w:r></w:p>';
    
    $content .= '<w:tbl>';
    $content .= '<w:tblPr>';
    $content .= '<w:tblW w:w="9000" w:type="dxa"/>';
    $content .= '<w:tblBorders><w:top w:val="single" w:sz="12" w:space="1" w:color="000000"/>';
    $content .= '<w:left w:val="single" w:sz="12" w:space="1" w:color="000000"/>';
    $content .= '<w:bottom w:val="single" w:sz="12" w:space="1" w:color="000000"/>';
    $content .= '<w:right w:val="single" w:sz="12" w:space="1" w:color="000000"/>';
    $content .= '<w:insideH w:val="single" w:sz="12" w:space="1" w:color="000000"/>';
    $content .= '<w:insideV w:val="single" w:sz="12" w:space="1" w:color="000000"/></w:tblBorders>';
    $content .= '</w:tblPr>';
    
    // Table row: Organization
    $content .= '<w:tr><w:trPr><w:trHeight w:val="400" w:type="auto"/></w:trPr>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="3000" w:type="dxa"/><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Organization</w:t></w:r></w:p></w:tc>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>' . htmlspecialchars($data['organization'] ?? '') . '</w:t></w:r></w:p></w:tc>';
    $content .= '</w:tr>';
    
    // Table row: Project Title
    $content .= '<w:tr><w:trPr><w:trHeight w:val="400" w:type="auto"/></w:trPr>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="3000" w:type="dxa"/><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Project Title</w:t></w:r></w:p></w:tc>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>' . htmlspecialchars($data['project_title'] ?? '') . '</w:t></w:r></w:p></w:tc>';
    $content .= '</w:tr>';
    
    // Table row: Type of Project
    $content .= '<w:tr><w:trPr><w:trHeight w:val="400" w:type="auto"/></w:trPr>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="3000" w:type="dxa"/><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Type of Project</w:t></w:r></w:p></w:tc>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>' . htmlspecialchars($data['project_type'] ?? '') . '</w:t></w:r></w:p></w:tc>';
    $content .= '</w:tr>';
    
    // Table row: Project Involvement
    $content .= '<w:tr><w:trPr><w:trHeight w:val="400" w:type="auto"/></w:trPr>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="3000" w:type="dxa"/><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Project Involvement</w:t></w:r></w:p></w:tc>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>' . htmlspecialchars($data['project_involvement'] ?? '') . '</w:t></w:r></w:p></w:tc>';
    $content .= '</w:tr>';
    
    // Table row: Project Location
    $content .= '<w:tr><w:trPr><w:trHeight w:val="400" w:type="auto"/></w:trPr>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="3000" w:type="dxa"/><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Project Location</w:t></w:r></w:p></w:tc>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>' . htmlspecialchars($data['project_location'] ?? '') . '</w:t></w:r></w:p></w:tc>';
    $content .= '</w:tr>';
    
    // Table row: Proposed Start Date
    $content .= '<w:tr><w:trPr><w:trHeight w:val="400" w:type="auto"/></w:trPr>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="3000" w:type="dxa"/><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Proposed Start Date</w:t></w:r></w:p></w:tc>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>' . htmlspecialchars($data['proposed_start_date'] ?? '') . '</w:t></w:r></w:p></w:tc>';
    $content .= '</w:tr>';
    
    // Table row: Proposed Completion Date
    $content .= '<w:tr><w:trPr><w:trHeight w:val="400" w:type="auto"/></w:trPr>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="3000" w:type="dxa"/><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Proposed Completion Date</w:t></w:r></w:p></w:tc>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>' . htmlspecialchars($data['proposed_end_date'] ?? '') . '</w:t></w:r></w:p></w:tc>';
    $content .= '</w:tr>';
    
    // Table row: Number of Participants
    $content .= '<w:tr><w:trPr><w:trHeight w:val="400" w:type="auto"/></w:trPr>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="3000" w:type="dxa"/><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Number of Participants</w:t></w:r></w:p></w:tc>';
    $content .= '<w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>' . htmlspecialchars($data['number_participants'] ?? '') . '</w:t></w:r></w:p></w:tc>';
    $content .= '</w:tr>';
    
    $content .= '</w:tbl>';
    $content .= '<w:p/>';
    
    // II. PROJECT DESCRIPTION SECTION
    $content .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="24"/></w:rPr><w:t>II. Project Description</w:t></w:r></w:p>';
    
    // A. SUMMARY OF THE PROJECT
    $content .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t>A. SUMMARY OF THE PROJECT</w:t></w:r></w:p>';
    if (!empty($data['project_summary'])) {
        $content .= '<w:p>';
        $content .= '<w:pPr><w:ind w:left="720"/></w:pPr>';
        $content .= '<w:r><w:t>' . htmlspecialchars($data['project_summary']) . '</w:t></w:r>';
        $content .= '</w:p>';
    }
    $content .= '<w:p/>';
    
    // B. PROJECT GOAL AND OBJECTIVES
    $content .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t>B. PROJECT GOAL AND OBJECTIVES</w:t></w:r></w:p>';
    
    $content .= '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Goal:</w:t></w:r></w:p>';
    if (!empty($data['project_goal'])) {
        $content .= '<w:p>';
        $content .= '<w:pPr><w:ind w:left="720"/></w:pPr>';
        $content .= '<w:r><w:t>' . htmlspecialchars($data['project_goal']) . '</w:t></w:r>';
        $content .= '</w:p>';
    }
    
    $content .= '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Objectives:</w:t></w:r></w:p>';
    if (!empty($data['project_objectives'])) {
        $objectives = explode("\n", $data['project_objectives']);
        $objNum = 1;
        foreach ($objectives as $obj) {
            $obj = trim($obj);
            if (!empty($obj)) {
                $content .= '<w:p>';
                $content .= '<w:pPr><w:ind w:left="720"/></w:pPr>';
                $content .= '<w:r><w:t>' . $objNum . '. ' . htmlspecialchars($obj) . '</w:t></w:r>';
                $content .= '</w:p>';
                $objNum++;
            }
        }
    }
    $content .= '<w:p/>';
    
    // C. EXPECTED OUTPUTS
    $content .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t>C. EXPECTED OUTPUTS</w:t></w:r></w:p>';
    if (!empty($data['expected_outputs'])) {
        $outputs = explode("\n", $data['expected_outputs']);
        foreach ($outputs as $output) {
            $output = trim($output);
            if (!empty($output)) {
                $content .= '<w:p>';
                $content .= '<w:pPr><w:ind w:left="720"/></w:pPr>';
                $content .= '<w:r><w:t>• ' . htmlspecialchars($output) . '</w:t></w:r>';
                $content .= '</w:p>';
            }
        }
    }
    $content .= '<w:p/>';
    
    // III. BUDGET SECTION
    $content .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="24"/></w:rPr><w:t>III. Budget</w:t></w:r></w:p>';
    
    $content .= '<w:tbl>';
    $content .= '<w:tblPr>';
    $content .= '<w:tblW w:w="9000" w:type="dxa"/>';
    $content .= '<w:tblBorders><w:top w:val="single" w:sz="12" w:space="1" w:color="000000"/>';
    $content .= '<w:left w:val="single" w:sz="12" w:space="1" w:color="000000"/>';
    $content .= '<w:bottom w:val="single" w:sz="12" w:space="1" w:color="000000"/>';
    $content .= '<w:right w:val="single" w:sz="12" w:space="1" w:color="000000"/>';
    $content .= '<w:insideH w:val="single" w:sz="12" w:space="1" w:color="000000"/>';
    $content .= '<w:insideV w:val="single" w:sz="12" w:space="1" w:color="000000"/></w:tblBorders>';
    $content .= '</w:tblPr>';
    
    // Budget rows
    $budgetRows = [
        ['Source of Fund', $data['budget_source'] ?? ''],
        ['Partner/Donation/Subsidy', $data['budget_partner'] ?? ''],
        ['Total Project Cost', $data['budget_total'] ?? '']
    ];
    
    foreach ($budgetRows as $row) {
        $content .= '<w:tr><w:trPr><w:trHeight w:val="400" w:type="auto"/></w:trPr>';
        $content .= '<w:tc><w:tcPr><w:tcW w:w="3000" w:type="dxa"/><w:shd w:fill="D3D3D3"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>' . $row[0] . '</w:t></w:r></w:p></w:tc>';
        $content .= '<w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>' . htmlspecialchars($row[1]) . '</w:t></w:r></w:p></w:tc>';
        $content .= '</w:tr>';
    }
    
    $content .= '</w:tbl>';
    $content .= '<w:p/>';
    
    // IV. MONITORING AND EVALUATION
    $content .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="24"/></w:rPr><w:t>IV. Monitoring and Evaluation</w:t></w:r></w:p>';
    
    $content .= '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Monitoring:</w:t></w:r></w:p>';
    if (!empty($data['monitoring_details'])) {
        $monItems = explode("\n", $data['monitoring_details']);
        foreach ($monItems as $item) {
            $item = trim($item);
            if (!empty($item)) {
                $content .= '<w:p>';
                $content .= '<w:pPr><w:ind w:left="720"/></w:pPr>';
                $content .= '<w:r><w:t>• ' . htmlspecialchars($item) . '</w:t></w:r>';
                $content .= '</w:p>';
            }
        }
    }
    $content .= '<w:p/>';
    
    $content .= '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Evaluation Strategy:</w:t></w:r></w:p>';
    if (!empty($data['evaluation_details'])) {
        $evalItems = explode("\n", $data['evaluation_details']);
        foreach ($evalItems as $item) {
            $item = trim($item);
            if (!empty($item)) {
                $content .= '<w:p>';
                $content .= '<w:pPr><w:ind w:left="720"/></w:pPr>';
                $content .= '<w:r><w:t>• ' . htmlspecialchars($item) . '</w:t></w:r>';
                $content .= '</w:p>';
            }
        }
    }
    $content .= '<w:p/>';
    
    // V. SECURITY PLAN
    $content .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="24"/></w:rPr><w:t>V. Security Plan</w:t></w:r></w:p>';
    if (!empty($data['security_plan'])) {
        $secItems = explode("\n", $data['security_plan']);
        foreach ($secItems as $item) {
            $item = trim($item);
            if (!empty($item)) {
                $content .= '<w:p>';
                $content .= '<w:pPr><w:ind w:left="720"/></w:pPr>';
                $content .= '<w:r><w:t>• ' . htmlspecialchars($item) . '</w:t></w:r>';
                $content .= '</w:p>';
            }
        }
    }
    $content .= '<w:p/>';
    $content .= '<w:p/>';
    
    // Closing
    if (!empty($data['closing_statement'])) {
        $content .= '<w:p>';
        $content .= '<w:r><w:t>' . htmlspecialchars($data['closing_statement']) . '</w:t></w:r>';
        $content .= '</w:p>';
        $content .= '<w:p/>';
    }
    
    // Signature section
    if (!empty($data['sender_name'])) {
        $content .= '<w:p>';
        $content .= '<w:r><w:t>Sincerely,</w:t></w:r>';
        $content .= '</w:p>';
        $content .= '<w:p><w:p/></w:p>';
        $content .= '<w:p><w:p/></w:p>';
        $content .= '<w:p>';
        $content .= '<w:r><w:rPr><w:b/></w:rPr><w:t>' . htmlspecialchars($data['sender_name']) . '</w:t></w:r>';
        $content .= '</w:p>';
    }
    
    // Noted by section
    if (!empty($data['noted_by'])) {
        $content .= '<w:p/>';
        $content .= '<w:p>';
        $content .= '<w:r><w:rPr><w:b/></w:rPr><w:t>Noted by:</w:t></w:r>';
        $content .= '</w:p>';
        
        $notedByList = explode(",", $data['noted_by']);
        foreach ($notedByList as $person) {
            $person = trim($person);
            if (!empty($person)) {
                $content .= '<w:p>';
                $content .= '<w:r><w:t>' . htmlspecialchars($person) . '</w:t></w:r>';
                $content .= '</w:p>';
            }
        }
    }
    
    // Endorsed by section
    if (!empty($data['endorsed_by'])) {
        $content .= '<w:p/>';
        $content .= '<w:p>';
        $content .= '<w:r><w:rPr><w:b/></w:rPr><w:t>Endorsed by:</w:t></w:r>';
        $content .= '</w:p>';
        $content .= '<w:p>';
        $content .= '<w:r><w:t>' . htmlspecialchars($data['endorsed_by']) . '</w:t></w:r>';
        $content .= '</w:p>';
    }
    
    return $content;
}

function createDocumentXml($title, $template, $data, $collaborated_logo = null, $organization_name = null, $organization_tagline = null) {
    $docTitle = htmlspecialchars($title);
    $templateName = htmlspecialchars($template['name']);
    
    // Set defaults for organization header
    if (!$organization_name) {
        $organization_name = 'PLSP Economics Society – EcoS';
    }
    if (!$organization_tagline) {
        $organization_tagline = 'Empowered and committed organization of service.';
    }
    
    $orgName = htmlspecialchars($organization_name);
    $orgTagline = htmlspecialchars($organization_tagline);
    
    $content = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $content .= '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" ';
    $content .= 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
    $content .= '<w:body>';
    
    // Add header with logos - table layout for left and right logo
    $content .= '<w:tbl>';
    $content .= '<w:tblPr>';
    $content .= '<w:tblW w:w="9000" w:type="dxa"/>';
    $content .= '<w:tblBorders><w:top w:val="none" w:sz="0" w:space="0" w:color="auto"/><w:left w:val="none" w:sz="0" w:space="0" w:color="auto"/><w:bottom w:val="none" w:sz="0" w:space="0" w:color="auto"/><w:right w:val="none" w:sz="0" w:space="0" w:color="auto"/><w:insideH w:val="none" w:sz="0" w:space="0" w:color="auto"/><w:insideV w:val="none" w:sz="0" w:space="0" w:color="auto"/></w:tblBorders>';
    $content .= '<w:tblCellMar><w:top w:w="0" w:type="dxa"/><w:left w:w="0" w:type="dxa"/><w:bottom w:w="0" w:type="dxa"/><w:right w:w="0" w:type="dxa"/></w:tblCellMar>';
    $content .= '</w:tblPr>';
    
    $content .= '<w:tr>';
    
    // Left cell - Organization logo (1/3 width)
    $content .= '<w:tc>';
    $content .= '<w:tcPr><w:tcW w:w="3000" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr>';
    $content .= '<w:p>';
    $content .= '<w:pPr><w:jc w:val="center"/></w:pPr>';
    $content .= '<w:r>';
    $content .= '<w:drawing>';
    $content .= '<wp:anchor distT="0" distB="0" distL="114300" distR="114300" simplePos="0" relativeHeight="251658240" behindDoc="0" locked="0" layoutInCell="1" allowOverlap="1" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">';
    $content .= '<wp:simplePos x="0" y="0"/>';
    $content .= '<wp:positionH relativeFrom="column"><wp:align>center</wp:align></wp:positionH>';
    $content .= '<wp:positionV relativeFrom="paragraph"><wp:posOffset>0</wp:posOffset></wp:positionV>';
    $content .= '<wp:extent cx="700000" cy="700000"/>';
    $content .= '<wp:effectExtent l="0" t="0" r="0" b="0"/>';
    $content .= '<wp:wrapNone/>';
    $content .= '<wp:docPr id="1" name="Logo"/>';
    $content .= '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">';
    $content .= '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">';
    $content .= '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">';
    $content .= '<pic:nvPicPr><pic:cNvPr id="0" name="logo.png"/><pic:cNvPicPr/></pic:nvPicPr>';
    $content .= '<pic:blipFill><a:blip r:embed="rId4" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>';
    $content .= '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="700000" cy="700000"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>';
    $content .= '</pic:pic>';
    $content .= '</a:graphicData>';
    $content .= '</a:graphic>';
    $content .= '</wp:anchor>';
    $content .= '</w:drawing>';
    $content .= '</w:r>';
    $content .= '</w:p>';
    $content .= '</w:tc>';
    
    // Middle cell - Organization name (1/3 width)
    $content .= '<w:tc>';
    $content .= '<w:tcPr><w:tcW w:w="3000" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr>';
    
    // Line 1: Pamantasan ng Lungsod ng San Pablo
    $content .= '<w:p>';
    $content .= '<w:pPr><w:jc w:val="center"/></w:pPr>';
    $content .= '<w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t>PAMANTASAN NG LUNGSOD NG SAN PABLO</w:t></w:r>';
    $content .= '</w:p>';
    
    // Line 2: Organization Name (editable)
    $content .= '<w:p>';
    $content .= '<w:pPr><w:jc w:val="center"/></w:pPr>';
    $content .= '<w:r><w:rPr><w:b/><w:sz w:val="28"/></w:rPr><w:t>' . $orgName . '</w:t></w:r>';
    $content .= '</w:p>';
    
    // Line 3: Tagline (editable)
    $content .= '<w:p>';
    $content .= '<w:pPr><w:jc w:val="center"/></w:pPr>';
    $content .= '<w:r><w:rPr><w:i/><w:sz w:val="20"/></w:rPr><w:t>"' . $orgTagline . '"</w:t></w:r>';
    $content .= '</w:p>';
    
    $content .= '</w:tc>';
    
    // Right cell - Collaborated logo (1/3 width)
    $content .= '<w:tc>';
    $content .= '<w:tcPr><w:tcW w:w="3000" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr>';
    $content .= '<w:p>';
    $content .= '<w:pPr><w:jc w:val="center"/></w:pPr>';
    if ($collaborated_logo) {
        $content .= '<w:r>';
        $content .= '<w:drawing>';
        $content .= '<wp:anchor distT="0" distB="0" distL="114300" distR="114300" simplePos="0" relativeHeight="251658240" behindDoc="0" locked="0" layoutInCell="1" allowOverlap="1" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">';
        $content .= '<wp:simplePos x="0" y="0"/>';
        $content .= '<wp:positionH relativeFrom="column"><wp:align>center</wp:align></wp:positionH>';
        $content .= '<wp:positionV relativeFrom="paragraph"><wp:posOffset>0</wp:posOffset></wp:positionV>';
        $content .= '<wp:extent cx="700000" cy="700000"/>';
        $content .= '<wp:effectExtent l="0" t="0" r="0" b="0"/>';
        $content .= '<wp:wrapNone/>';
        $content .= '<wp:docPr id="2" name="CollaboratedLogo"/>';
        $content .= '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">';
        $content .= '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">';
        $content .= '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">';
        $content .= '<pic:nvPicPr><pic:cNvPr id="1" name="collaborated_logo"/><pic:cNvPicPr/></pic:nvPicPr>';
        $content .= '<pic:blipFill><a:blip r:embed="rId5" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>';
        $content .= '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="700000" cy="700000"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>';
        $content .= '</pic:pic>';
        $content .= '</a:graphicData>';
        $content .= '</a:graphic>';
        $content .= '</wp:anchor>';
        $content .= '</w:drawing>';
        $content .= '</w:r>';
    }
    $content .= '</w:p>';
    $content .= '</w:tc>';
    
    $content .= '</w:tr>';
    $content .= '</w:tbl>';
    
    // Add horizontal line
    $content .= '<w:p>';
    $content .= '<w:pPr><w:pBdr>';
    $content .= '<w:bottom w:val="single" w:sz="24" w:space="1" w:color="2F5233"/>';
    $content .= '</w:pBdr></w:pPr>';
    $content .= '</w:p>';
    
    // Add spacing
    $content .= '<w:p/>';
    
    // Add title
    $content .= '<w:p>';
    $content .= '<w:pPr>';
    $content .= '<w:pStyle w:val="Heading1"/>';
    $content .= '<w:jc w:val="center"/>';
    $content .= '</w:pPr>';
    $content .= '<w:r><w:rPr><w:b/><w:sz w:val="28"/></w:rPr><w:t>' . $docTitle . '</w:t></w:r>';
    $content .= '</w:p>';
    
    // Add template name
    $content .= '<w:p>';
    $content .= '<w:pPr><w:jc w:val="center"/></w:pPr>';
    $content .= '<w:r><w:rPr><w:i/><w:sz w:val="22"/></w:rPr><w:t>(' . $templateName . ')</w:t></w:r>';
    $content .= '</w:p>';
    
    // Add generation date
    $content .= '<w:p>';
    $content .= '<w:r><w:t>Generated on: ' . date('F d, Y') . '</w:t></w:r>';
    $content .= '</w:p>';
    
    $content .= '<w:p/>';
    
    // Check if this is a project proposal template
    if (isset($template['name']) && $template['name'] === 'Project Proposal') {
        $content .= buildProjectProposalContent($data);
    } else {
        // Add template fields and data (generic approach for other templates)
        foreach ($template['fields'] as $fieldId => $fieldLabel) {
            $fieldValue = isset($data[$fieldId]) ? htmlspecialchars($data[$fieldId]) : '';
            
            // Field label
            $content .= '<w:p>';
            $content .= '<w:r><w:rPr><w:b/><w:color w:val="2F5233"/></w:rPr><w:t>' . htmlspecialchars($fieldLabel) . ':</w:t></w:r>';
            $content .= '</w:p>';
            
            // Field value (handle multi-line content)
            $lines = explode("\n", $fieldValue);
            foreach ($lines as $line) {
                $content .= '<w:p>';
                $content .= '<w:pPr><w:ind w:left="720"/></w:pPr>';
                $content .= '<w:r><w:t>' . ($line ?: ' ') . '</w:t></w:r>';
                $content .= '</w:p>';
            }
            
            // Spacing between fields
            $content .= '<w:p/>';
        }
    }
    
    // Add footer
    $content .= '<w:p/>';
    $content .= '<w:p>';
    $content .= '<w:pPr><w:pBdr>';
    $content .= '<w:top w:val="single" w:sz="24" w:space="1" w:color="000000"/>';
    $content .= '</w:pBdr></w:pPr>';
    $content .= '</w:p>';
    
    $content .= '<w:p>';
    $content .= '<w:pPr><w:jc w:val="center"/></w:pPr>';
    $content .= '<w:r><w:rPr><w:i/><w:sz w:val="22"/></w:rPr><w:t>"Primed to Lead and Serve for Progress"</w:t></w:r>';
    $content .= '</w:p>';
    
    // Add section properties with page margins (1080 twips = 1.5 inches)
    $content .= '<w:sectPr>';
    $content .= '<w:pgMar w:top="1080" w:right="1080" w:bottom="1080" w:left="1080" w:header="720" w:footer="720" w:gutter="0"/>';
    $content .= '</w:sectPr>';
    
    $content .= '</w:body></w:document>';
    
    return $content;
}

function getContentTypes() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
        '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
        '<Default Extension="xml" ContentType="application/xml"/>' .
        '<Default Extension="png" ContentType="image/png"/>' .
        '<Default Extension="jpg" ContentType="image/jpeg"/>' .
        '<Default Extension="jpeg" ContentType="image/jpeg"/>' .
        '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>' .
        '</Types>';
}

function getRelationships() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>' .
        '</Relationships>';
}

function getDocumentRelationships($collaborated_logo = null) {
    $relationships = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/image1.png"/>';
    
    if ($collaborated_logo) {
        $ext = pathinfo($collaborated_logo, PATHINFO_EXTENSION);
        $relationships .= '<Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/image2.' . $ext . '"/>';
    }
    
    $relationships .= '</Relationships>';
    
    return $relationships;
}
?>
