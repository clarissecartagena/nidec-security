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

$userBuilding = normalize_building($user['building'] ?? null);
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
        u_submit.security_type AS submitted_by_security_type,
        gasr.reviewed_at, u_staff.name AS ga_staff_reviewer,
        gapa.decided_at, gapa.decision AS ga_president_decision, u_pres.name AS ga_president_name
     FROM reports r
     JOIN departments d ON d.id = r.responsible_department_id
LEFT JOIN users u_submit ON u_submit.id = r.submitted_by
LEFT JOIN ga_staff_reviews gasr ON gasr.report_id = r.id
LEFT JOIN users u_staff ON u_staff.id = gasr.reviewed_by
LEFT JOIN ga_president_approvals gapa ON gapa.report_id = r.id
LEFT JOIN users u_pres ON u_pres.id = gapa.decided_by
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
    $src = ($info['mime'] === 'image/png') ? @imagecreatefrompng($path) : @imagecreatefromjpeg($path);
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
$evidence = db_fetch_all("SELECT id, file_path FROM report_attachments WHERE report_id = ?", '', [$report['id']]);
$gaManager = !empty($report['ga_president_name']) ? strtoupper($report['ga_president_name']) : "MS. KAREN F. ENRIQUEZ";
$gaStaffName = !empty($report['ga_staff_reviewer']) ? strtoupper($report['ga_staff_reviewer']) : "MS. LIZA ACOSTA";
$subjectLine = strtoupper((string)($report['category'] ?? 'REPORT')) . " RE: " . strtoupper((string)($report['subject'] ?? ''));
$dateStr = !empty($report['submitted_at']) ? date('d F Y', strtotime($report['submitted_at'])) : date('d F Y');

// --- PDF CONSTANTS ---
$pageW = 612; $pageH = 1008; $marginL = 50; $marginR = 50; $topY = $pageH - 50; $bottomY = 55;
$logoPath = dirname(__DIR__) . '/assets/images/internal-logo.png';
$logo = build_pdf_image_objects_from_rgba($logoPath);

$pages = []; $content = ''; $y = $topY; $evidenceImageObjects = [];

$start_new_page = function () use (&$pages, &$content, &$y, $topY, $pageW, $logo): void {
    if ($content !== '') { $pages[] = $content; }
    $content = ''; $y = $topY;
    $tx = ($pageW / 2) - (310 / 2);
    if ($logo) {
        $targetH = 75.0; $scale = $targetH / (float)$logo['h'];
        $drawW = (float)$logo['w'] * $scale; $drawH = $targetH;
        $startX = ($pageW / 2) - (($drawW + 310) / 2) - 20;
        $content .= "q\n" . sprintf("%.2f 0 0 %.2f %.2f %.2f cm\n", $drawW, $drawH, $startX, $y - $drawH + 15) . "/Im1 Do\nQ\n";
        $tx = $startX + $drawW + 5;
    }

    // Header text should not depend on GD/logo availability
    $headerLines = [
        ['f'=>'F2', 's'=>14, 't'=>'ARAGON SECURITY AND INVESTIGATION'],
        ['f'=>'F2', 's'=>11, 't'=>'AGENCY, CORPORATION'],
        ['f'=>'F1', 's'=>10, 't'=>'NIDEC PHILIPPINES CORPORATION DETACHMENT'],
        ['f'=>'F1', 's'=>9,  't'=>'136 North Science Avenue Extension, Laguna Technopark, Binan, Laguna']
    ];
    foreach ($headerLines as $idx => $hl) {
        $ly = $y - ($idx * 16);
        if ($idx === 0) {
            $content .= "0.7 0 0 rg\n" . pdf_text($tx + 18, $ly, 'F2', 14, 'ARAGON ') . "0 0.3 0.6 rg\n" . pdf_text($tx + 86, $ly, 'F2', 14, 'SECURITY AND INVESTIGATION');
        } else {
            $content .= ($idx === 1 ? "0 0.3 0.6 rg\n" : "0 0 0 rg\n") . pdf_text($tx + 40, $ly, $hl['f'], $hl['s'], $hl['t']);
        }
    }

    $y -= 80;
};

$start_new_page();

// --- MEMORANDUM HEADER ---
$content .= "0 0 0 rg\nBT /F2 12 Tf $marginL $y Td (MEMORANDUM) ET\n"; $y -= 25;
$content .= pdf_text($marginL, $y, 'F2', 11, 'FOR');
$content .= pdf_text($marginL + 85, $y, 'F1', 11, ': ' . $gaManager);
$content .= pdf_text($marginL + 95, $y - 14, 'F1', 10, 'Senior GA Manager'); $y -= 45;
$content .= pdf_text($marginL, $y, 'F2', 11, 'THRU');
$content .= pdf_text($marginL + 85, $y, 'F1', 11, ': ' . $gaStaffName);
$content .= pdf_text($marginL + 95, $y - 14, 'F1', 10, 'General Affair Supervisor'); $y -= 45;
$content .= pdf_text($marginL, $y, 'F2', 11, 'SUBJECT');
$content .= pdf_text($marginL + 85, $y, 'F2', 11, ': ');
foreach (wrap_pdf_lines($subjectLine, 50) as $idx => $line) {
    if ($idx > 0) $y -= 14;
    $content .= pdf_text($marginL + 95, $y, 'F2', 11, $line);
}
$y -= 35;
$content .= pdf_text($marginL, $y, 'F2', 11, 'DATE');
$content .= pdf_text($marginL + 85, $y, 'F1', 11, ': ' . strtoupper($dateStr)); $y -= 25;
$content .= pdf_line($marginL, $y, $pageW - $marginR, $y, 0.5); $y -= 30;

// --- BODY ---
$sections = ['Details' => $report['details'], 'Observation' => $report['observation'] ?? '', 'Action Taken' => $report['actions_taken'], 'Remarks' => $report['remarks']];
foreach ($sections as $lbl => $val) {
    $val = trim((string)$val);
    if ($val !== '' && strtolower($val) !== 'n/a') {
        $content .= pdf_text($marginL, $y, 'F2', 11, "$lbl:"); $y -= 16;
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

// --- SIGNATURE ---
if ($y < 150) $start_new_page();
$content .= pdf_text($marginL, $y, 'F1', 11, 'Prepared by:'); $y -= 45;
$content .= pdf_text($marginL, $y, 'F2', 11, strtoupper($report['submitted_by_name'])); $y -= 14;
$content .= pdf_text($marginL, $y, 'F1', 10, $report['building'] . " / Security Officer"); $y -= 12;
$content .= pdf_text($marginL, $y, 'F1', 10, "Internal Security");

if ($content !== '') $pages[] = $content;

// --- ASSEMBLY ---
$objects = [
    "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
    "2 0 obj\n<< /Type /Pages /Kids %KIDS% /Count %COUNT% >>\nendobj\n",
    "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
    "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n"
];
$imgObjNum = null;
if ($logo) {
    $objects[] = "5 0 obj\n" . $logo['maskObj'] . "\nendobj\n";
    $objects[] = "6 0 obj\n" . str_replace('%SMASK%', '5', $logo['imgObj']) . "\nendobj\n";
    $imgObjNum = 6;
}
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
    $xRefs = ($imgObjNum ? "/Im1 $imgObjNum 0 R " : "");
    foreach ($evidMap as $ref => $idx) $xRefs .= "/$ref $idx 0 R ";
    $objects[] = "$cNum 0 obj\n<< /Length " . strlen($pContent) . " >>\nstream\n{$pContent}endstream\nendobj\n";
    $objects[] = "$pNum 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageW $pageH] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> /XObject << $xRefs >> >> /Contents $cNum 0 R >>\nendobj\n";
    $pageNums[] = $pNum;
}
$objects[1] = str_replace(['%KIDS%', '%COUNT%'], ['[ ' . implode(' ', array_map(fn($n)=>"$n 0 R", $pageNums)) . ' ]', (string)count($pageNums)], $objects[1]);

$pdf = "%PDF-1.4\n"; $offsets = [0];
foreach ($objects as $obj) { $offsets[] = strlen($pdf); $pdf .= $obj; }
$xrefPos = strlen($pdf); $pdf .= "xref\n0 " . count($objects) + 1 . "\n0000000000 65535 f \n";
for ($i = 1; $i <= count($objects); $i++) $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
$pdf .= "trailer\n<< /Size " . count($objects) + 1 . " /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="internal_report_' . $reportNo . '.pdf"');
echo $pdf;
exit;