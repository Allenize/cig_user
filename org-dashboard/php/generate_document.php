<?php
/**
 * generate_document.php
 * Unified document generator — supports both DOCX and PDF output.
 *
 * Public API (used by upload_document.php):
 *   generateDocument($template, $data, $title, $format, $collaboratedLogo, $orgName, $orgTagline)
 *     $format : 'docx' | 'pdf'
 *     returns : absolute path to the generated temp file, or false on failure
 */

/* ══════════════════════════════════════════════════════════════════════════════
 * ENTRY POINT
 * ══════════════════════════════════════════════════════════════════════════════ */

function generateDocument($template, $data, $title, $format = 'docx', $collaboratedLogo = null, $organizationName = null, $organizationTagline = null) {
    $format = strtolower($format);

    // Always generate DOCX first
    $docxPath = generateDocx($template, $data, $title, $collaboratedLogo, $organizationName, $organizationTagline);
    if (!$docxPath) return false;

    if ($format !== 'pdf') return $docxPath;

    // Convert DOCX → PDF via LibreOffice (same engine used by the preview modal)
    $tmpDir  = sys_get_temp_dir();
    $soffice = findLibreOffice();
    if (!$soffice) {
        error_log('generateDocument: LibreOffice not found, returning DOCX instead');
        return $docxPath; // fall back to DOCX
    }

    $cmd  = sprintf('"%s" --headless --convert-to pdf --outdir "%s" "%s" 2>&1', $soffice, $tmpDir, $docxPath);
    exec($cmd, $out, $code);
    @unlink($docxPath);

    $pdfPath = $tmpDir . '/' . pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';
    return ($code === 0 && file_exists($pdfPath)) ? $pdfPath : false;
}

function findLibreOffice() {
    $paths = [
        'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
        'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
        '/usr/bin/soffice',
        '/usr/lib/libreoffice/program/soffice',
        '/opt/libreoffice/program/soffice',
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) return $p;
    }
    $found = trim((string)@shell_exec(PHP_OS_FAMILY === 'Windows' ? 'where soffice.exe 2>nul' : 'which soffice 2>/dev/null'));
    return $found ? strtok($found, "\r\n") : null;
}

/* ══════════════════════════════════════════════════════════════════════════════
 * DOCX GENERATION
 * ══════════════════════════════════════════════════════════════════════════════ */

function generateDocx($template, $data, $title, $collaboratedLogo = null, $organizationName = null, $organizationTagline = null) {
    $tempDir  = sys_get_temp_dir();
    $docxPath = $tempDir . '/' . uniqid('doc_') . '.docx';

    if (!class_exists('ZipArchive')) {
        error_log('generateDocx: ZipArchive not available');
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($docxPath, ZipArchive::CREATE) !== true) {
        error_log('generateDocx: could not create zip at ' . $docxPath);
        return false;
    }

    $xmlContent = createDocumentXml($title, $template, $data, $collaboratedLogo, $organizationName, $organizationTagline);

    $zip->addFromString('[Content_Types].xml',          getContentTypes());
    $zip->addFromString('_rels/.rels',                  getRelationships());
    $zip->addFromString('word/_rels/document.xml.rels', getDocumentRelationships($collaboratedLogo));
    $zip->addFromString('word/document.xml',            $xmlContent);

    // Main (PLSP) logo
    $logoPath = __DIR__ . '/../../plsplogo.png';
    if (file_exists($logoPath)) {
        $imageData = file_get_contents($logoPath);
        if ($imageData) {
            $zip->addFromString('word/media/image1.png', $imageData);
        }
    }

    // Collaborated logo
    if ($collaboratedLogo) {
        $collabPath = __DIR__ . '/../../Assets/' . basename($collaboratedLogo);
        if (file_exists($collabPath)) {
            $imageData = file_get_contents($collabPath);
            if ($imageData) {
                $ext = pathinfo($collabPath, PATHINFO_EXTENSION);
                $zip->addFromString('word/media/image2.' . $ext, $imageData);
            }
        }
    }

    $zip->close();
    return file_exists($docxPath) ? $docxPath : false;
}

// ── XML helpers ──────────────────────────────────────────────────────────────

function createDocumentXml($title, $template, $data, $collaboratedLogo = null, $organizationName = null, $organizationTagline = null) {
    $docTitle     = htmlspecialchars($title);
    $templateName = htmlspecialchars($template['name']);
    $orgName      = htmlspecialchars($organizationName ?: 'PLSP Economics Society - EcoS');
    $orgTagline   = htmlspecialchars($organizationTagline ?: 'Empowered and committed organization of service.');

    $c  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $c .= '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" ';
    $c .= 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
    $c .= '<w:body>';

    // Header table
    $c .= '<w:tbl><w:tblPr>';
    $c .= '<w:tblW w:w="9200" w:type="dxa"/>';
    $c .= '<w:tblBorders>'
        . '<w:top w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
        . '<w:left w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
        . '<w:bottom w:val="single" w:sz="12" w:space="5" w:color="2F5233"/>'
        . '<w:right w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
        . '<w:insideH w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
        . '<w:insideV w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
        . '</w:tblBorders>';
    $c .= '<w:tblCellMar><w:top w:w="100" w:type="dxa"/><w:left w:w="100" w:type="dxa"/><w:bottom w:w="100" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tblCellMar>';
    $c .= '</w:tblPr><w:tr>';

    // Left cell - PLSP logo
    $c .= '<w:tc><w:tcPr><w:tcW w:w="2800" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr>';
    $c .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>';
    $c .= docxInlineImage('rId4', 1, 'Logo', '700000', '700000');
    $c .= '</w:p></w:tc>';

    // Middle cell - Org info
    $c .= '<w:tc><w:tcPr><w:tcW w:w="3600" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr>';
    $c .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t>PAMANTASAN NG LUNGSOD NG SAN PABLO</w:t></w:r></w:p>';
    $c .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="28"/></w:rPr><w:t>' . $orgName . '</w:t></w:r></w:p>';
    $c .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:i/><w:sz w:val="20"/></w:rPr><w:t>"' . $orgTagline . '"</w:t></w:r></w:p>';
    $c .= '</w:tc>';

    // Right cell - collaborated logo or blank
    $c .= '<w:tc><w:tcPr><w:tcW w:w="2800" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr>';
    $c .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>';
    if ($collaboratedLogo) {
        $c .= docxInlineImage('rId5', 2, 'CollaboratedLogo', '700000', '700000');
    }
    $c .= '</w:p></w:tc>';

    $c .= '</w:tr></w:tbl>';

    // Title block
    $c .= '<w:p/>';
    $c .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="28"/></w:rPr><w:t>' . $docTitle . '</w:t></w:r></w:p>';
    $c .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:i/><w:sz w:val="22"/></w:rPr><w:t>(' . $templateName . ')</w:t></w:r></w:p>';
    $c .= '<w:p><w:r><w:t>Generated on: ' . date('F d, Y') . '</w:t></w:r></w:p>';
    $c .= '<w:p/>';

    // Body content
    if (isset($template['name']) && $template['name'] === 'Project Proposal') {
        $c .= buildProjectProposalContent($data);
    } else {
        foreach ($template['fields'] as $fieldId => $fieldLabel) {
            $fieldValue = isset($data[$fieldId]) ? htmlspecialchars($data[$fieldId]) : '';
            $c .= '<w:p><w:r><w:rPr><w:b/><w:color w:val="2F5233"/></w:rPr><w:t>' . htmlspecialchars($fieldLabel) . ':</w:t></w:r></w:p>';
            foreach (explode("\n", $fieldValue) as $line) {
                $c .= '<w:p><w:pPr><w:ind w:left="720"/></w:pPr><w:r><w:t>' . ($line ?: ' ') . '</w:t></w:r></w:p>';
            }
            $c .= '<w:p/>';
        }
    }

    // Footer
    $c .= '<w:p/>';
    $c .= '<w:p><w:pPr><w:pBdr><w:top w:val="single" w:sz="24" w:space="1" w:color="000000"/></w:pBdr></w:pPr></w:p>';
    $c .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:i/><w:sz w:val="22"/></w:rPr><w:t>"Primed to Lead and Serve for Progress"</w:t></w:r></w:p>';
    $c .= '<w:sectPr><w:pgMar w:top="1080" w:right="1080" w:bottom="1080" w:left="1080" w:header="720" w:footer="720" w:gutter="0"/></w:sectPr>';
    $c .= '</w:body></w:document>';

    return $c;
}

function docxInlineImage($rId, $docPrId, $name, $cx, $cy) {
    $r  = '<w:r><w:drawing>';
    $r .= '<wp:anchor distT="0" distB="0" distL="114300" distR="114300" simplePos="0" relativeHeight="251658240" behindDoc="0" locked="0" layoutInCell="1" allowOverlap="1" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">';
    $r .= '<wp:simplePos x="0" y="0"/>';
    $r .= '<wp:positionH relativeFrom="column"><wp:align>center</wp:align></wp:positionH>';
    $r .= '<wp:positionV relativeFrom="paragraph"><wp:posOffset>0</wp:posOffset></wp:positionV>';
    $r .= '<wp:extent cx="' . $cx . '" cy="' . $cy . '"/>';
    $r .= '<wp:effectExtent l="0" t="0" r="0" b="0"/>';
    $r .= '<wp:wrapNone/>';
    $r .= '<wp:docPr id="' . $docPrId . '" name="' . $name . '"/>';
    $r .= '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">';
    $r .= '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">';
    $r .= '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">';
    $r .= '<pic:nvPicPr><pic:cNvPr id="0" name="' . $name . '"/><pic:cNvPicPr/></pic:nvPicPr>';
    $r .= '<pic:blipFill><a:blip r:embed="' . $rId . '" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>';
    $r .= '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>';
    $r .= '</pic:pic></a:graphicData></a:graphic></wp:anchor></w:drawing></w:r>';
    return $r;
}

function getContentTypes() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml"  ContentType="application/xml"/>'
        . '<Default Extension="png"  ContentType="image/png"/>'
        . '<Default Extension="jpg"  ContentType="image/jpeg"/>'
        . '<Default Extension="jpeg" ContentType="image/jpeg"/>'
        . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
        . '</Types>';
}

function getRelationships() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
        . '</Relationships>';
}

function getDocumentRelationships($collaboratedLogo = null) {
    $r  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/image1.png"/>';
    if ($collaboratedLogo) {
        $ext = pathinfo($collaboratedLogo, PATHINFO_EXTENSION);
        $r .= '<Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/image2.' . $ext . '"/>';
    }
    $r .= '</Relationships>';
    return $r;
}

/* ══════════════════════════════════════════════════════════════════════════════
 * PROJECT PROPOSAL DOCX CONTENT
 * ══════════════════════════════════════════════════════════════════════════════ */

function buildProjectProposalContent($data) {
    $c = '';

    if (!empty($data['proposal_date'])) {
        $c .= '<w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:t>' . htmlspecialchars($data['proposal_date']) . '</w:t></w:r></w:p><w:p/>';
    }
    foreach (['recipient_1', 'recipient_2'] as $key) {
        if (!empty($data[$key])) {
            $c .= '<w:p><w:r><w:t>' . htmlspecialchars($data[$key]) . '</w:t></w:r></w:p>';
        }
    }
    $c .= '<w:p/>';
    if (!empty($data['dear_opening'])) {
        $c .= '<w:p><w:r><w:t>' . htmlspecialchars($data['dear_opening']) . '</w:t></w:r></w:p>';
    }
    if (!empty($data['opening_statement'])) {
        $c .= '<w:p><w:r><w:t>' . htmlspecialchars($data['opening_statement']) . '</w:t></w:r></w:p><w:p/>';
    }

    // I. Identifying Information
    $c .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="24"/></w:rPr><w:t>I. Identifying Information</w:t></w:r></w:p>';
    $c .= docxTable([
        ['Organization',             $data['organization']         ?? ''],
        ['Project Title',            $data['project_title']        ?? ''],
        ['Type of Project',          $data['project_type']         ?? ''],
        ['Project Involvement',      $data['project_involvement']  ?? ''],
        ['Project Location',         $data['project_location']     ?? ''],
        ['Proposed Start Date',      $data['proposed_start_date']  ?? ''],
        ['Proposed Completion Date', $data['proposed_end_date']    ?? ''],
        ['Number of Participants',   $data['number_participants']  ?? ''],
    ]);
    $c .= '<w:p/>';

    // II. Project Description
    $c .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="24"/></w:rPr><w:t>II. Project Description</w:t></w:r></w:p>';
    $c .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t>A. SUMMARY OF THE PROJECT</w:t></w:r></w:p>';
    $c .= docxIndentedText($data['project_summary'] ?? '');
    $c .= '<w:p/>';

    $c .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t>B. PROJECT GOAL AND OBJECTIVES</w:t></w:r></w:p>';
    $c .= '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Goal:</w:t></w:r></w:p>';
    $c .= docxIndentedText($data['project_goal'] ?? '');
    $c .= '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Objectives:</w:t></w:r></w:p>';
    if (!empty($data['project_objectives'])) {
        $n = 1;
        foreach (explode("\n", $data['project_objectives']) as $obj) {
            $obj = trim($obj);
            if ($obj !== '') {
                $c .= '<w:p><w:pPr><w:ind w:left="720"/></w:pPr><w:r><w:t>' . $n++ . '. ' . htmlspecialchars($obj) . '</w:t></w:r></w:p>';
            }
        }
    }
    $c .= '<w:p/>';

    $c .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t>C. EXPECTED OUTPUTS</w:t></w:r></w:p>';
    $c .= docxBulletList($data['expected_outputs'] ?? '');
    $c .= '<w:p/>';

    // III. Budget
    $c .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="24"/></w:rPr><w:t>III. Budget</w:t></w:r></w:p>';
    $c .= docxTable([
        ['Source of Fund',           $data['budget_source']  ?? ''],
        ['Partner/Donation/Subsidy', $data['budget_partner'] ?? ''],
        ['Total Project Cost',       $data['budget_total']   ?? ''],
    ]);
    $c .= '<w:p/>';

    // IV. Monitoring and Evaluation
    $c .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="24"/></w:rPr><w:t>IV. Monitoring and Evaluation</w:t></w:r></w:p>';
    $c .= '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Monitoring:</w:t></w:r></w:p>';
    $c .= docxBulletList($data['monitoring_details'] ?? '');
    $c .= '<w:p/>';
    $c .= '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Evaluation Strategy:</w:t></w:r></w:p>';
    $c .= docxBulletList($data['evaluation_details'] ?? '');
    $c .= '<w:p/>';

    // V. Security Plan
    $c .= '<w:p><w:r><w:rPr><w:b/><w:sz w:val="24"/></w:rPr><w:t>V. Security Plan</w:t></w:r></w:p>';
    $c .= docxBulletList($data['security_plan'] ?? '');
    $c .= '<w:p/><w:p/>';

    // Signatures
    if (!empty($data['closing_statement'])) {
        $c .= '<w:p><w:r><w:t>' . htmlspecialchars($data['closing_statement']) . '</w:t></w:r></w:p><w:p/>';
    }
    if (!empty($data['sender_name'])) {
        $c .= '<w:p><w:r><w:t>Sincerely,</w:t></w:r></w:p><w:p/><w:p/>';
        $c .= '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>' . htmlspecialchars($data['sender_name']) . '</w:t></w:r></w:p>';
    }
    if (!empty($data['noted_by'])) {
        $c .= '<w:p/><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Noted by:</w:t></w:r></w:p>';
        foreach (explode(',', $data['noted_by']) as $person) {
            $person = trim($person);
            if ($person !== '') {
                $c .= '<w:p><w:r><w:t>' . htmlspecialchars($person) . '</w:t></w:r></w:p>';
            }
        }
    }
    if (!empty($data['endorsed_by'])) {
        $c .= '<w:p/><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Endorsed by:</w:t></w:r></w:p>';
        $c .= '<w:p><w:r><w:t>' . htmlspecialchars($data['endorsed_by']) . '</w:t></w:r></w:p>';
    }

    return $c;
}

function docxTable(array $rows) {
    $c  = '<w:tbl><w:tblPr>';
    $c .= '<w:tblW w:w="9000" w:type="dxa"/>';
    $c .= '<w:tblBorders>'
        . '<w:top w:val="single" w:sz="12" w:space="1" w:color="000000"/>'
        . '<w:left w:val="single" w:sz="12" w:space="1" w:color="000000"/>'
        . '<w:bottom w:val="single" w:sz="12" w:space="1" w:color="000000"/>'
        . '<w:right w:val="single" w:sz="12" w:space="1" w:color="000000"/>'
        . '<w:insideH w:val="single" w:sz="12" w:space="1" w:color="000000"/>'
        . '<w:insideV w:val="single" w:sz="12" w:space="1" w:color="000000"/>'
        . '</w:tblBorders></w:tblPr>';
    foreach ($rows as [$label, $value]) {
        $c .= '<w:tr><w:trPr><w:trHeight w:val="400" w:type="auto"/></w:trPr>';
        $c .= '<w:tc><w:tcPr><w:tcW w:w="3000" w:type="dxa"/><w:shd w:fill="D3D3D3"/></w:tcPr>'
            . '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>' . htmlspecialchars($label) . '</w:t></w:r></w:p></w:tc>';
        $c .= '<w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/></w:tcPr>'
            . '<w:p><w:r><w:t>' . htmlspecialchars($value) . '</w:t></w:r></w:p></w:tc>';
        $c .= '</w:tr>';
    }
    $c .= '</w:tbl>';
    return $c;
}

function docxIndentedText($text) {
    $c = '';
    foreach (explode("\n", $text) as $line) {
        $c .= '<w:p><w:pPr><w:ind w:left="720"/></w:pPr><w:r><w:t>' . htmlspecialchars(trim($line) ?: ' ') . '</w:t></w:r></w:p>';
    }
    return $c;
}

function docxBulletList($text) {
    $c = '';
    foreach (explode("\n", $text) as $item) {
        $item = trim($item);
        if ($item !== '') {
            $c .= '<w:p><w:pPr><w:ind w:left="720"/></w:pPr><w:r><w:t>- ' . htmlspecialchars($item) . '</w:t></w:r></w:p>';
        }
    }
    return $c;
}