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
$docxPath = generateDocx($template, $template_data, $title, $collaborated_logo);

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

function generateDocx($template, $data, $title, $collaborated_logo = null) {
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
    $xmlContent = createDocumentXml($title, $template, $data, $collaborated_logo);
    
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

function createDocumentXml($title, $template, $data, $collaborated_logo = null) {
    $docTitle = htmlspecialchars($title);
    $templateName = htmlspecialchars($template['name']);
    
    $content = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $content .= '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" ';
    $content .= 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
    $content .= '<w:body>';
    
    // Add header with logos - table layout for left and right logo
    $content .= '<w:tbl>';
    $content .= '<w:tblPr>';
    $content .= '<w:tblW w:w="5000" w:type="auto"/>';
    $content .= '<w:tblBorders><w:top w:val="none" w:sz="0" w:space="0" w:color="auto"/><w:left w:val="none" w:sz="0" w:space="0" w:color="auto"/><w:bottom w:val="none" w:sz="0" w:space="0" w:color="auto"/><w:right w:val="none" w:sz="0" w:space="0" w:color="auto"/><w:insideH w:val="none" w:sz="0" w:space="0" w:color="auto"/><w:insideV w:val="none" w:sz="0" w:space="0" w:color="auto"/></w:tblBorders>';
    $content .= '<w:tblCellMar><w:top w:w="0" w:type="dxa"/><w:left w:w="0" w:type="dxa"/><w:bottom w:w="0" w:type="dxa"/><w:right w:w="0" w:type="dxa"/></w:tblCellMar>';
    $content .= '</w:tblPr>';
    
    $content .= '<w:tr>';
    
    // Left cell - Organization logo
    $content .= '<w:tc>';
    $content .= '<w:tcPr><w:tcW w:w="2500" w:type="auto"/><w:vAlign w:val="center"/></w:tcPr>';
    $content .= '<w:p>';
    $content .= '<w:pPr><w:jc w:val="left"/></w:pPr>';
    $content .= '<w:r>';
    $content .= '<w:drawing>';
    $content .= '<wp:anchor distT="0" distB="0" distL="114300" distR="114300" simplePos="0" relativeHeight="251658240" behindDoc="0" locked="0" layoutInCell="1" allowOverlap="1" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">';
    $content .= '<wp:simplePos x="0" y="0"/>';
    $content .= '<wp:positionH relativeFrom="column"><wp:align>left</wp:align></wp:positionH>';
    $content .= '<wp:positionV relativeFrom="paragraph"><wp:posOffset>0</wp:posOffset></wp:positionV>';
    $content .= '<wp:extent cx="914400" cy="914400"/>';
    $content .= '<wp:effectExtent l="0" t="0" r="0" b="0"/>';
    $content .= '<wp:wrapNone/>';
    $content .= '<wp:docPr id="1" name="Logo"/>';
    $content .= '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">';
    $content .= '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">';
    $content .= '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">';
    $content .= '<pic:nvPicPr><pic:cNvPr id="0" name="logo.png"/><pic:cNvPicPr/></pic:nvPicPr>';
    $content .= '<pic:blipFill><a:blip r:embed="rId4" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>';
    $content .= '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="914400" cy="914400"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>';
    $content .= '</pic:pic>';
    $content .= '</a:graphicData>';
    $content .= '</a:graphic>';
    $content .= '</wp:anchor>';
    $content .= '</w:drawing>';
    $content .= '</w:r>';
    $content .= '</w:p>';
    $content .= '</w:tc>';
    
    // Middle cell - Organization name
    $content .= '<w:tc>';
    $content .= '<w:tcPr><w:tcW w:w="2000" w:type="auto"/><w:vAlign w:val="center"/></w:tcPr>';
    $content .= '<w:p>';
    $content .= '<w:pPr><w:jc w:val="center"/></w:pPr>';
    $content .= '<w:r><w:rPr><w:b/><w:sz w:val="28"/></w:rPr><w:t>PLSP Organization</w:t></w:r>';
    $content .= '</w:p>';
    $content .= '</w:tc>';
    
    // Right cell - Collaborated logo (if provided)
    $content .= '<w:tc>';
    $content .= '<w:tcPr><w:tcW w:w="2500" w:type="auto"/><w:vAlign w:val="center"/></w:tcPr>';
    $content .= '<w:p>';
    $content .= '<w:pPr><w:jc w:val="right"/></w:pPr>';
    if ($collaborated_logo) {
        $content .= '<w:r>';
        $content .= '<w:drawing>';
        $content .= '<wp:anchor distT="0" distB="0" distL="114300" distR="114300" simplePos="0" relativeHeight="251658240" behindDoc="0" locked="0" layoutInCell="1" allowOverlap="1" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">';
        $content .= '<wp:simplePos x="0" y="0"/>';
        $content .= '<wp:positionH relativeFrom="column"><wp:align>right</wp:align></wp:positionH>';
        $content .= '<wp:positionV relativeFrom="paragraph"><wp:posOffset>0</wp:posOffset></wp:positionV>';
        $content .= '<wp:extent cx="914400" cy="914400"/>';
        $content .= '<wp:effectExtent l="0" t="0" r="0" b="0"/>';
        $content .= '<wp:wrapNone/>';
        $content .= '<wp:docPr id="2" name="CollaboratedLogo"/>';
        $content .= '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">';
        $content .= '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">';
        $content .= '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">';
        $content .= '<pic:nvPicPr><pic:cNvPr id="1" name="collaborated_logo"/><pic:cNvPicPr/></pic:nvPicPr>';
        $content .= '<pic:blipFill><a:blip r:embed="rId5" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>';
        $content .= '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="914400" cy="914400"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>';
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
