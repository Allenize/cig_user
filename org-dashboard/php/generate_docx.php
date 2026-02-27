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
$docxPath = generateDocx($template, $template_data, $title);

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

function generateDocx($template, $data, $title) {
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
    $xmlContent = createDocumentXml($title, $template, $data);
    
    // Add necessary files to ZIP
    $zip->addFromString('[Content_Types].xml', getContentTypes());
    $zip->addFromString('_rels/.rels', getRelationships());
    $zip->addFromString('word/_rels/document.xml.rels', getDocumentRelationships());
    $zip->addFromString('word/document.xml', $xmlContent);
    
    $zip->close();
    
    return $docxPath;
}

function createDocumentXml($title, $template, $data) {
    $docTitle = htmlspecialchars($title);
    $templateName = htmlspecialchars($template['name']);
    
    $content = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $content .= '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" ';
    $content .= 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
    $content .= '<w:body>';
    
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
    
    // Add template fields and data
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
    
    // Add footer
    $content .= '<w:p>';
    $content .= '<w:pPr><w:pBdr>';
    $content .= '<w:top w:val="single" w:sz="12" w:space="1" w:color="AUTO"/>';
    $content .= '</w:pBdr></w:pPr>';
    $content .= '<w:r><w:rPr><w:i/><w:sz w:val="18"/><w:color w:val="999999"/></w:rPr><w:t>Document prepared by PLSP Organization</w:t></w:r>';
    $content .= '</w:p>';
    
    $content .= '</w:body></w:document>';
    
    return $content;
}

function getContentTypes() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
        '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
        '<Default Extension="xml" ContentType="application/xml"/>' .
        '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>' .
        '</Types>';
}

function getRelationships() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>' .
        '</Relationships>';
}

function getDocumentRelationships() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>';
}
?>
