<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isAuthenticated()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = getUser();
$role = (string)($user['role'] ?? '');
$allowedRoles = ['ga_president', 'ga_staff', 'security', 'department'];
if (!in_array($role, $allowedRoles, true)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$reportNo = trim((string)($_GET['id'] ?? ''));
if ($reportNo === '') {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Missing id']);
    exit;
}

$userBuilding = normalize_building($user['entity'] ?? $user['building'] ?? null);
$userDepartmentId = (int)($user['department_id'] ?? 0);

$whereExtra = '';
$params = [$reportNo];

if ($role === 'security') {
    if (!$userBuilding) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Account is missing an assigned building']);
        exit;
    }
    $whereExtra = ' AND r.building = ?';
    $params[] = $userBuilding;
} elseif ($role === 'department') {
    if ($userDepartmentId <= 0) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Account is missing an assigned department']);
        exit;
    }
    $whereExtra = ' AND r.responsible_department_id = ?';
    $params[] = $userDepartmentId;
}

$sql = "SELECT 
        r.id, r.report_no, r.subject, r.category, r.location, r.severity, r.building,
        r.status, r.submitted_at, r.details, r.actions_taken, r.remarks, r.security_remarks,
        d.name AS department_name, u_submit.name AS submitted_by_name,
        u_submit.signature_path AS submitted_by_signature,
        gasr.reviewed_at, u_staff.name AS ga_staff_reviewer,
        u_staff.signature_path AS ga_staff_signature,
        u_staff.job_level AS ga_staff_job_level,
        gapa.decided_at, u_pres.name AS ga_president_name,
        u_pres.signature_path AS ga_president_signature,
        u_pres.job_level AS ga_president_job_level,
        da.acted_at AS dept_acted_at, u_dept.name AS dept_acted_by,
        u_dept.signature_path AS dept_signature
     FROM reports r
     JOIN departments d ON d.id = r.responsible_department_id
LEFT JOIN users u_submit ON u_submit.employee_no = r.submitted_by
LEFT JOIN ga_staff_reviews gasr ON gasr.report_id = r.id
LEFT JOIN users u_staff ON u_staff.employee_no = gasr.reviewed_by
LEFT JOIN ga_president_approvals gapa ON gapa.report_id = r.id
LEFT JOIN users u_pres ON u_pres.employee_no = gapa.decided_by
LEFT JOIN department_actions da ON da.report_id = r.id
LEFT JOIN users u_dept ON u_dept.employee_no = da.acted_by
    WHERE r.report_no = ? " . $whereExtra . " LIMIT 1";

$report = db_fetch_one($sql, '', $params);
if (!$report) {
    http_response_code(404);
    echo json_encode(['error' => 'Report not found']);
    exit;
}

// --- PDF HELPER FUNCTIONS ---
function pdf_escape_text(string $s): string { return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s); }
function pdf_text($x, $y, $font, $size, $text): string { return "BT\n/$font $size Tf\n$x $y Td\n(" . pdf_escape_text($text) . ") Tj\nET\n"; }
function pdf_line($x1, $y1, $x2, $y2, $w = 1.0): string { return "$w w\n$x1 $y1 m\n$x2 $y2 l\nS\n"; }

function wrap_pdf_lines(string $text, int $maxLen): array {
    $text = str_replace(["\r\n", "\r"], "\n", trim($text));
    $out = [];
    foreach (explode("\n", $text) as $p) {
        $words = preg_split('/\s+/', trim($p), -1, PREG_SPLIT_NO_EMPTY);
        $line = '';
        foreach ($words as $w) {
            if (strlen($line) + 1 + strlen($w) <= $maxLen) { $line .= ($line === '' ? '' : ' ') . $w; }
            else { $out[] = $line; $line = $w; }
        }
        if ($line !== '') $out[] = $line;
    }
    return $out;
}

function build_pdf_image_objects_from_rgba($path): ?array {
    // GD is required for imagecreatefrompng/jpeg and pixel access
    if (!function_exists('imagecreatefrompng') || !function_exists('imagecreatefromjpeg') || !function_exists('imagecolorat') || !function_exists('imagecolorsforindex')) {
        return null;
    }
    if (!is_file($path)) return null;
    $info = @getimagesize($path);
    if (!$info || empty($info['mime'])) return null;
    $mime = $info['mime'];
    if ($mime === 'image/png') {
        $src = @imagecreatefrompng($path);
    } elseif ($mime === 'image/jpeg') {
        $src = @imagecreatefromjpeg($path);
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $src = @imagecreatefromwebp($path);
    } elseif ($mime === 'image/gif' && function_exists('imagecreatefromgif')) {
        $src = @imagecreatefromgif($path);
    } else {
        return null;
    }
    if (!$src) return null;
    $w = imagesx($src); $h = imagesy($src);
    $rgb = ''; $alpha = '';
    for ($py = 0; $py < $h; $py++) {
        for ($px = 0; $px < $w; $px++) {
            $c = imagecolorsforindex($src, imagecolorat($src, $px, $py));
            $rgb .= chr($c['red']) . chr($c['green']) . chr($c['blue']);
            $alpha .= chr((int)((127 - $c['alpha']) * 2));
        }
    }
    imagedestroy($src);
    $rgbS = gzcompress($rgb, 6); $maskS = gzcompress($alpha, 6);
    return [
        'maskObj' => "<< /Type /XObject /Subtype /Image /Width $w /Height $h /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode /Length " . strlen($maskS) . " >>\nstream\n" . $maskS . "\nendstream",
        'imgObj'  => "<< /Type /XObject /Subtype /Image /Width $w /Height $h /ColorSpace /DeviceRGB /BitsPerComponent 8 /SMask %SMASK% 0 R /Filter /FlateDecode /Length " . strlen($rgbS) . " >>\nstream\n" . $rgbS . "\nendstream",
        'w' => $w, 'h' => $h
    ];
}

$gdAvailableForPdfImages = function_exists('imagecreatefrompng') && function_exists('imagecreatefromjpeg') && function_exists('imagecolorat') && function_exists('imagecolorsforindex');

// --- DATA PREPARATION ---
$evidence    = db_fetch_all("SELECT id, file_path FROM report_attachments WHERE report_id = ?", '', [$report['id']]);
$gaManager   = !empty($report['ga_president_name']) ? strtoupper($report['ga_president_name'])  : 'NOT YET APPROVED';
$gaStaffName = !empty($report['ga_staff_reviewer']) ? strtoupper($report['ga_staff_reviewer'])  : 'NOT YET REVIEWED';
$subjectLine = strtoupper((string)($report['category'] ?? 'REPORT')) . " RE: " . strtoupper((string)($report['subject'] ?? ''));
$dateStr     = !empty($report['submitted_at']) ? date('d F Y', strtotime($report['submitted_at'])) : date('d F Y');

// Dynamic role labels: "GA " + job_level, only when person has approved/reviewed
$gaPresidentRoleLabel = '';
if (!empty($report['ga_president_name'])) {
    $jl = trim((string)($report['ga_president_job_level'] ?? ''));
    if ($jl !== '') $gaPresidentRoleLabel = 'GA ' . strtoupper($jl);
}
$gaStaffRoleLabel = '';
if (!empty($report['ga_staff_reviewer'])) {
    $jl = trim((string)($report['ga_staff_job_level'] ?? ''));
    if ($jl !== '') $gaStaffRoleLabel = 'GA ' . strtoupper($jl);
}

// --- PDF CONSTANTS ---
$pageW = 612; $pageH = 1008; $marginL = 50; $marginR = 50; $topY = $pageH - 50; $bottomY = 55;

$pages = []; $content = ''; $y = $topY; $evidenceImageObjects = [];

// Load GA president signature for the TO section header
$gaPresidentSigRef = null;
if ($gdAvailableForPdfImages && !empty($report['ga_president_signature'])) {
    $sp = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string)$report['ga_president_signature']);
    $sd = build_pdf_image_objects_from_rgba($sp);
    if ($sd) { $gaPresidentSigRef = 'ImSigPresHeader'; $evidenceImageObjects[$gaPresidentSigRef] = $sd; }
}

// Load GA staff signature for the THRU section header
$gaStaffSigRef = null;
if ($gdAvailableForPdfImages && !empty($report['ga_staff_signature'])) {
    $sp = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string)$report['ga_staff_signature']);
    $sd = build_pdf_image_objects_from_rgba($sp);
    if ($sd) { $gaStaffSigRef = 'ImSigStaffHeader'; $evidenceImageObjects[$gaStaffSigRef] = $sd; }
}

$isFirstPage = true;
$start_new_page = function () use (&$pages, &$content, &$y, $topY, $pageW, &$isFirstPage): void {
    if ($content !== '') { $pages[] = $content; }
    $content = '';
    $y = $topY;

    if (!$isFirstPage) {
        return; // No header on subsequent pages
    }
    $isFirstPage = false;

    $centerX = $pageW / 2;
    $content .= "0 0 0 rg\n";

    // Line 1: SISCO INVESTIGATION & SECURITY CORPORATION
    $line1 = 'SISCO INVESTIGATION & SECURITY CORPORATION';
    $content .= pdf_text($centerX - 170, $y, 'F2', 14, $line1);

    // Line 2: NIDEC Philippines Corporation - Security Detachment
    $y -= 16;
    $line2 = 'NIDEC Philippines Corporation - Security Detachment';
    $content .= pdf_text($centerX - 146, $y, 'F1', 12, $line2);

    // Line 3: Address
    $y -= 16;
    $line3 = '119 Technology Avenue Special Economic Zone Laguna Technopark, Binan Laguna';
    $content .= pdf_text($centerX - 186, $y, 'F1', 10, $line3);

    $y -= 60;
};

$start_new_page();

// --- EXTERNAL INFO BLOCK ---

// 1. DATE SECTION
$content .= pdf_text($marginL, $y, 'F2', 11, 'DATE');
$content .= pdf_text($marginL + 70, $y, 'F1', 11, ': ' . $dateStr);
$y -= 35;

// 2. TO SECTION (signature above name)
$content .= pdf_text($marginL, $y, 'F2', 11, 'TO');
$_presigH = 0.0;
if ($gaPresidentSigRef !== null) {
    $sd = $evidenceImageObjects[$gaPresidentSigRef];
    $_psc = min(35.0 / (float)$sd['h'], 120.0 / (float)$sd['w']);
    $_psw = $sd['w'] * $_psc; $_psh = $sd['h'] * $_psc;
    $content .= sprintf("q\n%.2f 0 0 %.2f %.2f %.2f cm\n/$gaPresidentSigRef Do\nQ\n", $_psw, $_psh, $marginL + 75, $y - $_psh);
    $_presigH = $_psh + 4.0;
}
$content .= pdf_text($marginL + 70, $y - $_presigH, 'F1', 11, ': ' . $gaManager);
if ($gaPresidentRoleLabel !== '') {
    $content .= pdf_text($marginL + 80, $y - $_presigH - 14, 'F1', 10, $gaPresidentRoleLabel);
}
$y -= (45.0 + $_presigH);

// 3. THRU SECTION (signature above name)
$content .= pdf_text($marginL, $y, 'F2', 11, 'THRU');
$_stafgH = 0.0;
if ($gaStaffSigRef !== null) {
    $sd = $evidenceImageObjects[$gaStaffSigRef];
    $_ssc = min(35.0 / (float)$sd['h'], 120.0 / (float)$sd['w']);
    $_ssw = $sd['w'] * $_ssc; $_ssh = $sd['h'] * $_ssc;
    $content .= sprintf("q\n%.2f 0 0 %.2f %.2f %.2f cm\n/$gaStaffSigRef Do\nQ\n", $_ssw, $_ssh, $marginL + 75, $y - $_ssh);
    $_stafgH = $_ssh + 4.0;
}
$content .= pdf_text($marginL + 70, $y - $_stafgH, 'F1', 11, ': ' . $gaStaffName);
if ($gaStaffRoleLabel !== '') {
    $content .= pdf_text($marginL + 80, $y - $_stafgH - 14, 'F1', 10, $gaStaffRoleLabel);
}
$y -= (45.0 + $_stafgH);

// --- 4. SUBJECT SECTION ---
$content .= pdf_text($marginL, $y, 'F2', 11, 'SUBJECT');

// We wrap the subject text. 
// 75 is the character limit before it moves to a new line.
$subjectLines = wrap_pdf_lines(': ' . $subjectLine, 50); 

foreach ($subjectLines as $index => $sLine) {
    // If it's the first line, we keep the colon. 
    // If it's the second line, we indent it to stay aligned.
    $indent = ($index === 0) ? 70 : 77; 
    
    $content .= pdf_text($marginL + $indent, $y, 'F2', 11, $sLine);
    
    // If there are more lines, move the Y coordinate down
    if (count($subjectLines) > 1 && $index < count($subjectLines) - 1) {
        $y -= 14; 
    }
}

// --- 5. LINE SEPARATOR ---
$y -= 30; // Gap between the last line of Subject and the Line
$content .= pdf_line($marginL, $y, $pageW - $marginR, $y, 0.5); 

$y -= 35; // Gap before Details starts

// --- CONDITIONAL BODY SECTIONS ---
// Removed assessment, recommendation, and observation as they are not in your database
$bodySections = [
    'Details'        => $report['details'] ?? '',
    'Action Taken'   => $report['actions_taken'] ?? '',
    'Remarks'        => $report['remarks'] ?? '',
    'Security Remarks' => $report['security_remarks'] ?? ''
];

foreach ($bodySections as $label => $val) {
    $val = trim((string)$val);
    if ($val !== '' && strtolower($val) !== 'n/a') {
        if ($y < $bottomY + 40) $start_new_page();
        $content .= pdf_text($marginL, $y, 'F2', 11, $label . ':'); $y -= 16;
        foreach (wrap_pdf_lines($val, 95) as $line) {
            if ($y < $bottomY + 20) $start_new_page();
            $content .= pdf_text($marginL + 15, $y, 'F1', 10, $line); $y -= 14;
        }
        $y -= 15;
    }
}

// --- EVIDENCE ---
if (!empty($evidence)) {
    if ($y < 200) $start_new_page();
    $content .= pdf_text($marginL, $y, 'F2', 11, 'Evidence / Attachments:'); $y -= 30;
    if (!$gdAvailableForPdfImages) {
        $content .= pdf_text($marginL + 15, $y, 'F1', 10, 'Images omitted (PHP GD extension is not enabled).');
        $y -= 20;
    } else {
        foreach ($evidence as $img) {
            $imgP = dirname(__DIR__) . '/' . $img['file_path'];
            $evid = build_pdf_image_objects_from_rgba($imgP);
            if ($evid) {
                $dispW = 400.0; $dispH = ($evid['h'] / $evid['w']) * $dispW;
                if ($y - $dispH < 100) $start_new_page();
                $ref = "ImEvid" . $img['id'];
                $content .= "q\n" . sprintf("%.2f 0 0 %.2f %.2f %.2f cm\n", $dispW, $dispH, $marginL, $y - $dispH) . "/$ref Do\nQ\n";
                $y -= ($dispH + 30); $evidenceImageObjects[$ref] = $evid;
            }
        }
    }
}

// --- SIGNATORY BLOCKS ---
if ($y < 250) $start_new_page();

$signatories = [];

// 1. Security — always shown
$signatories[] = [
    'label' => 'Prepared by:',
    'name'  => strtoupper((string)($report['submitted_by_name'] ?? 'OFFICER NAME')),
    'line1' => 'Detachment Commander',
    'line2' => 'SISCO-NCFL External Scty.',
    'sig'   => $report['submitted_by_signature'] ?? null,
    'key'   => 'security',
];

// 2. Department — shown once they acted
if (!empty($report['dept_acted_at'])) {
    $signatories[] = [
        'label' => 'Acknowledged by:',
        'name'  => strtoupper((string)($report['dept_acted_by'] ?? '')),
        'line1' => strtoupper((string)($report['department_name'] ?? 'Department')),
        'line2' => null,
        'sig'   => $report['dept_signature'] ?? null,
        'key'   => 'dept',
    ];
}

// Load signature images
$sigImgMaxH = 40.0;
foreach ($signatories as &$sig) {
    if (empty($sig['sig'])) continue;
    $sigImgPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string)$sig['sig']);
    if (!is_file($sigImgPath)) continue;
    $sigImgData = build_pdf_image_objects_from_rgba($sigImgPath);
    if (!$sigImgData) continue;
    $sigRef = 'ImSig' . $sig['key'];
    $evidenceImageObjects[$sigRef] = $sigImgData;
    $sig['img_ref'] = $sigRef;
    $sig['img_w']   = $sigImgData['w'];
    $sig['img_h']   = $sigImgData['h'];
}
unset($sig);

// Draw columns — signature image directly above printed name, no separator line
$numSigs  = count($signatories);
$colWidth = (float)($pageW - $marginL - $marginR) / max($numSigs, 1);

foreach ($signatories as $ci => $sig) {
    $cx   = (float)$marginL + $ci * $colWidth;
    $content .= pdf_text($cx, $y, 'F1', 9, $sig['label']);
    $rowY = $y - 14.0;
    if (!empty($sig['img_ref'])) {
        $iw    = (float)$sig['img_w'];
        $ih    = (float)$sig['img_h'];
        $scale = min($sigImgMaxH / $ih, ($colWidth - 6.0) / $iw);
        $dw    = $iw * $scale;
        $dh    = $ih * $scale;
        $content .= sprintf("q\n%.2f 0 0 %.2f %.2f %.2f cm\n/%s Do\nQ\n", $dw, $dh, $cx, $rowY - $dh, $sig['img_ref']);
        $nameY = $rowY - $dh - 4.0;
    } else {
        $nameY = $rowY;
    }
    $content .= pdf_text($cx, $nameY, 'F2', 9, $sig['name']);
    if (!empty($sig['line1'])) $content .= pdf_text($cx, $nameY - 12.0, 'F1', 8, $sig['line1']);
    if (!empty($sig['line2'])) $content .= pdf_text($cx, $nameY - 24.0, 'F1', 8, $sig['line2']);
}

if ($content !== '') $pages[] = $content;

// --- ASSEMBLY ---
$objects = [
    "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
    "2 0 obj\n<< /Type /Pages /Kids %KIDS% /Count %COUNT% >>\nendobj\n",
    "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
    "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n"
];
$evidMap = []; $nextIdx = count($objects) + 1;
foreach ($evidenceImageObjects as $ref => $data) {
    $mIdx = $nextIdx++; $iIdx = $nextIdx++;
    $objects[] = "$mIdx 0 obj\n" . $data['maskObj'] . "\nendobj\n";
    $objects[] = "$iIdx 0 obj\n" . str_replace('%SMASK%', (string)$mIdx, $data['imgObj']) . "\nendobj\n";
    $evidMap[$ref] = $iIdx;
}
$pageNums = []; $nextObj = $nextIdx;
foreach ($pages as $pContent) {
    $cNum = $nextObj++; $pNum = $nextObj++;
    $xRefs = '';
    foreach ($evidMap as $ref => $idx) $xRefs .= "/$ref $idx 0 R ";
    $objects[] = "$cNum 0 obj\n<< /Length " . strlen($pContent) . " >>\nstream\n{$pContent}endstream\nendobj\n";
    $objects[] = "$pNum 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageW $pageH] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> /XObject << $xRefs >> >> /Contents $cNum 0 R >>\nendobj\n";
    $pageNums[] = $pNum;
}
$objects[1] = str_replace(['%KIDS%', '%COUNT%'], ['[ ' . implode(' ', array_map(fn($n)=>"$n 0 R", $pageNums)) . ' ]', (string)count($pageNums)], $objects[1]);

$pdf = "%PDF-1.4\n"; $offsets = [0];
foreach ($objects as $obj) { $offsets[] = strlen($pdf); $pdf .= $obj; }
$xrefPos = strlen($pdf); $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
for ($i = 1; $i <= count($objects); $i++) $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
$pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF";

if (ob_get_length()) { ob_clean(); }
header('Content-Type: application/pdf');
$disposition = 'attachment';
header('Content-Disposition: ' . $disposition . '; filename="external_report_' . $reportNo . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
echo $pdf;
exit;
