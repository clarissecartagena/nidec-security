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
$allowedRoles = ['ga_president', 'ga_manager', 'ga_staff', 'security', 'department', 'pic'];
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

$userBuilding      = normalize_building($user['building'] ?? null);
$userDepartmentId  = (int)($user['department_id'] ?? 0);

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
} elseif ($role === 'department' || $role === 'pic') {
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
        r.id,
        r.report_no,
        r.subject,
        r.category,
        r.location,
        r.severity,
        r.building,
        r.status,
        r.submitted_at,
        r.details,
        r.actions_taken,
        r.remarks,
        r.security_remarks,
        r.fix_due_date,
        d.name AS department_name,
        u_submit.name          AS submitted_by_name,
        u_submit.security_type AS submitted_by_security_type,
        gasr.reviewed_at,
        gasr.notes             AS ga_staff_notes,
        u_staff.name           AS ga_staff_reviewer,
        gapa.decided_at,
        gapa.decision          AS ga_president_decision,
        gapa.notes             AS ga_president_notes,
        u_pres.name            AS ga_president_name,
        da.action_type,
        da.timeline_days,
        da.timeline_start,
        da.timeline_due,
        da.remarks             AS dept_remarks,
        da.acted_at            AS dept_acted_at,
        u_dept.name            AS dept_acted_by,
        sfc.checked_at         AS final_checked_at,
        sfc.decision           AS final_decision,
        sfc.remarks            AS final_remarks,
        u_sec.name             AS final_checked_by,
        sfc.closed_at
     FROM reports r
     JOIN departments d         ON d.id = r.responsible_department_id
LEFT JOIN users u_submit        ON u_submit.id = r.submitted_by
LEFT JOIN ga_staff_reviews gasr ON gasr.report_id = r.id
LEFT JOIN users u_staff         ON u_staff.id = gasr.reviewed_by
LEFT JOIN ga_president_approvals gapa ON gapa.report_id = r.id
LEFT JOIN users u_pres          ON u_pres.id = gapa.decided_by
LEFT JOIN department_actions da ON da.report_id = r.id
LEFT JOIN users u_dept          ON u_dept.id = da.acted_by
LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
LEFT JOIN users u_sec           ON u_sec.id = sfc.checked_by
    WHERE r.report_no = ?";

$sql .= $whereExtra . ' LIMIT 1';

$report = db_fetch_one($sql, '', $params);
if (!$report) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Report not found']);
    exit;
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function pdf_escape_text(string $s): string {
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
}

/**
 * FIX #1: norm_security_type now defaults to 'internal' when the value is
 * missing/null, matching the majority of reports in this system.
 * Change the fallback to whichever type is your "default" agency.
 */
function norm_security_type(?string $raw): string {
    $v = strtolower(trim((string)$raw));
    if ($v === 'internal') return 'internal';
    if ($v === 'external') return 'external';
    // Explicit fallback — set to 'internal' so Aragon reports are never
    // accidentally rendered with the SISCO external header.
    return 'internal';
}

function wrap_pdf_lines(string $text, int $maxLen): array {
    $text = trim($text);
    if ($text === '') return [];
    $out  = [];
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    foreach (explode("\n", $text) as $p) {
        $p = trim($p);
        if ($p === '') continue;
        $words = preg_split('/\s+/', $p, -1, PREG_SPLIT_NO_EMPTY);
        $line  = '';
        foreach ($words as $w) {
            if ($line === '') { $line = $w; continue; }
            if (strlen($line) + 1 + strlen($w) <= $maxLen) {
                $line .= ' ' . $w;
            } else {
                $out[] = $line;
                $line  = $w;
            }
        }
        if ($line !== '') $out[] = $line;
    }
    return $out;
}

function pdf_text(float $x, float $y, string $font, int $size, string $text): string {
    return sprintf("BT\n/$font %d Tf\n%.2f %.2f Td\n(%s) Tj\nET\n",
        $size, $x, $y, pdf_escape_text($text));
}

function pdf_line(float $x1, float $y1, float $x2, float $y2, float $width = 1.0): string {
    return sprintf("%.2f w\n%.2f %.2f m\n%.2f %.2f l\nS\n", $width, $x1, $y1, $x2, $y2);
}

/**
 * FIX #2: $gdAvailableForPdfImages is now determined INSIDE the function
 * (or passed as a parameter) so it is always in scope.
 */
function gd_available(): bool {
    return function_exists('imagecreatefrompng')
        && function_exists('imagecreatefromjpeg')
        && function_exists('imagecolorat')
        && function_exists('imagecolorsforindex');
}

function build_pdf_image_objects_from_rgba(string $path): ?array {
    if (!is_file($path)) return null;
    if (!gd_available())  return null;

    $info = @getimagesize($path);
    if (!$info) return null;

    $src = ($info['mime'] === 'image/png')
        ? @imagecreatefrompng($path)
        : @imagecreatefromjpeg($path);
    if (!$src) return null;

    $w = imagesx($src);
    $h = imagesy($src);
    $rgb = ''; $alpha = '';
    for ($py = 0; $py < $h; $py++) {
        for ($px = 0; $px < $w; $px++) {
            $c = imagecolorsforindex($src, imagecolorat($src, $px, $py));
            $rgb   .= chr($c['red']) . chr($c['green']) . chr($c['blue']);
            $alpha .= chr((int)((127 - $c['alpha']) * 2));
        }
    }
    imagedestroy($src);

    $rgbS  = gzcompress($rgb,   6);
    $maskS = gzcompress($alpha, 6);

    return [
        'maskObj' => "<< /Type /XObject /Subtype /Image /Width $w /Height $h /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode /Length " . strlen($maskS) . " >>\nstream\n" . $maskS . "\nendstream",
        'imgObj'  => "<< /Type /XObject /Subtype /Image /Width $w /Height $h /ColorSpace /DeviceRGB  /BitsPerComponent 8 /SMask %SMASK% 0 R /Filter /FlateDecode /Length " . strlen($rgbS)  . " >>\nstream\n" . $rgbS  . "\nendstream",
        'w' => $w, 'h' => $h,
    ];
}

// ── Evidence query ────────────────────────────────────────────────────────────
$evidenceSql = "SELECT id, file_path FROM report_attachments WHERE report_id = ?";
$evidence    = db_fetch_all($evidenceSql, '', [$report['id']]);

// ── Main PDF builder ──────────────────────────────────────────────────────────
function output_report_template_pdf(array $report, string $filename, array $evidence = []): void
{
    $pageW   = 612;
    $pageH   = 1008;
    $marginL = 50;
    $marginR = 50;
    $topY    = $pageH - 50;
    $bottomY = 55;

    $isPreview = isset($_GET['preview']) && $_GET['preview'] == '1';
    $showGrid  = isset($_GET['grid'])    && $_GET['grid']    == '1';

    // FIX #2 (continued): call the helper function — no global variable needed
    $gdAvailable = gd_available();

    // ── Dynamic names ────────────────────────────────────────────────────────
    $gaManager   = !empty($report['ga_president_name'])  ? strtoupper($report['ga_president_name'])  : 'MS. KAREN F. ENRIQUEZ';
    $gaStaffName = !empty($report['ga_staff_reviewer'])  ? strtoupper($report['ga_staff_reviewer'])  : 'MS. LIZA ACOSTA';
    $subjectLine = strtoupper((string)($report['category'] ?? 'REPORT')) . ' RE: ' . strtoupper((string)($report['subject'] ?? ''));
    $dateStr     = !empty($report['submitted_at']) ? date('d F Y', strtotime($report['submitted_at'])) : date('d F Y');

    // FIX #3 (THE CORE FIX): determine template from the report's submitter security_type
    // norm_security_type returns 'internal' or 'external' — never an empty/wrong value
    $template = norm_security_type($report['submitted_by_security_type'] ?? null);

    // ── Logo ─────────────────────────────────────────────────────────────────
    $logoFile = ($template === 'internal') ? 'assets/images/internal-logo.png' : 'assets/images/external-logo.png';
    $logoPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $logoFile);
    $logo     = ($gdAvailable && is_file($logoPath)) ? build_pdf_image_objects_from_rgba($logoPath) : null;

    // ── Header text lines (depend entirely on $template) ─────────────────────
    if ($template === 'internal') {
        $headerLines = [
            ['font' => 'F2', 'size' => 14, 'text' => 'ARAGON SECURITY AND INVESTIGATION'],
            ['font' => 'F2', 'size' => 11, 'text' => 'AGENCY, CORPORATION'],
            ['font' => 'F1', 'size' => 10, 'text' => 'NIDEC PHILIPPINES CORPORATION DETACHMENT'],
            ['font' => 'F1', 'size' =>  9, 'text' => '136 North Science Avenue Extension, Laguna Technopark, Binan, Laguna'],
        ];
    } else {
        // EXTERNAL (SISCO)
        $headerLines = [
            ['font' => 'F2', 'size' => 12, 'text' => 'SISCO INVESTIGATION & SECURITY CORPORATION'],
            ['font' => 'F1', 'size' => 10, 'text' => 'NIDEC Philippines Corporation - Security Detachment'],
            ['font' => 'F1', 'size' =>  9, 'text' => '119 Technology Avenue Special Economic Zone Laguna Technopark, Binan Laguna'],
        ];
    }

    $pages   = [];
    $content = '';
    $y       = $topY;

    // ── Page renderer (closure) ───────────────────────────────────────────────
    $start_new_page = function () use (
        &$pages, &$content, &$y,
        $topY, $marginL, $pageW, $template, $headerLines, $logo, $pageH, $showGrid
    ): void {
        if ($content !== '') {
            $pages[] = $content;
        }
        $content = '';
        $y       = $topY;

        // Optional debug grid
        if ($showGrid) {
            $content .= "q 0.8 G 0.3 w\n";
            for ($gx = 0; $gx <= $pageW; $gx += 50) {
                $content .= "$gx 0 m $gx $pageH l S\nBT /F1 8 Tf $gx 5 Td ($gx) ET\n";
            }
            for ($gy = 0; $gy <= $pageH; $gy += 50) {
                $content .= "0 $gy m $pageW $gy l S\nBT /F1 8 Tf 5 $gy Td ($gy) ET\n";
            }
            $content .= "Q\n";
        }

        // ── Centered header ───────────────────────────────────────────────────
        $horizontalGap   = 5;
        $textAreaWidth   = 320;
        $tx = ($pageW / 2) - ($textAreaWidth / 2);

        if ($logo) {
            $targetH  = 75.0;
            $scale    = $targetH / max(1.0, (float)$logo['h']);
            $drawW    = (float)$logo['w'] * $scale;
            $drawH    = (float)$logo['h'] * $scale;
            $approxTW = 300;
            $startX   = ($pageW / 2) - (($drawW + $horizontalGap + $approxTW) / 2) - 20;
            $yImg     = $y - $drawH + 15;
            $content .= sprintf("q\n%.2f 0 0 %.2f %.2f %.2f cm\n/Im1 Do\nQ\n",
                $drawW, $drawH, $startX, $yImg);
            $tx = $startX + $drawW + $horizontalGap;
        }

        // FIX #3 (continued): render header lines with proper per-template styling
        foreach ($headerLines as $idx => $hl) {
            $lineY    = $y - ($idx * 16);
            $fontSize = (int)$hl['size'];
            $fontObj  = $hl['font'];
            $text     = (string)$hl['text'];

            $widthFactor       = ($fontObj === 'F2') ? 0.52 : 0.46;
            $estimatedLineWidth = strlen($text) * ($fontSize * $widthFactor);
            $lineX             = $tx + ($textAreaWidth / 2) - ($estimatedLineWidth / 2);

            if ($template === 'internal') {
                if ($idx === 0) {
                    // "ARAGON" in red, rest in blue
                    $fullWidth = strlen('ARAGON SECURITY AND INVESTIGATION') * ($fontSize * 0.52);
                    $line1X    = $tx + ($textAreaWidth / 2) - ($fullWidth / 2);
                    $content .= "0.7 0 0 rg\n";
                    $content .= pdf_text($line1X,       $lineY, 'F2', $fontSize, 'ARAGON ');
                    $content .= "0 0.3 0.6 rg\n";
                    $content .= pdf_text($line1X + 68,  $lineY, 'F2', $fontSize, 'SECURITY AND INVESTIGATION');
                } elseif ($idx === 1) {
                    // "AGENCY, CORPORATION" in blue
                    $content .= "0 0.3 0.6 rg\n";
                    $content .= pdf_text($lineX, $lineY, 'F2', $fontSize, $text);
                } else {
                    // Address lines in black
                    $content .= "0 0 0 rg\n";
                    $content .= pdf_text($lineX, $lineY, $fontObj, $fontSize, $text);
                }
            } else {
                // EXTERNAL: all header lines in plain black
                $content .= "0 0 0 rg\n";
                $content .= pdf_text($lineX, $lineY, $fontObj, $fontSize, $text);
            }
        }

        $y -= 80;
    };

    $start_new_page();

    // ── Memo fields (internal vs external layout) ─────────────────────────────
    if ($template === 'internal') {
        $content .= "BT /F2 12 Tf $marginL $y Td (MEMORANDUM) ET\n"; $y -= 25;
        $content .= pdf_text($marginL,      $y, 'F2', 11, 'FOR');
        $content .= pdf_text($marginL + 85, $y, 'F1', 11, ': ' . $gaManager);
        $content .= pdf_text($marginL + 95, $y - 14, 'F1', 10, 'Senior GA Manager'); $y -= 45;
        $content .= pdf_text($marginL,      $y, 'F2', 11, 'THRU');
        $content .= pdf_text($marginL + 85, $y, 'F1', 11, ': ' . $gaStaffName);
        $content .= pdf_text($marginL + 95, $y - 14, 'F1', 10, 'General Affair Supervisor'); $y -= 45;
        $content .= pdf_text($marginL,      $y, 'F2', 11, 'SUBJECT');
        $content .= pdf_text($marginL + 85, $y, 'F2', 11, ': ');
        foreach (wrap_pdf_lines($subjectLine, 50) as $i => $line) {
            if ($i > 0) $y -= 14;
            $content .= pdf_text($marginL + 95, $y, 'F2', 11, $line);
        }
        $y -= 35;
        $content .= pdf_text($marginL,      $y, 'F2', 11, 'DATE');
        $content .= pdf_text($marginL + 85, $y, 'F1', 11, ': ' . strtoupper($dateStr)); $y -= 25;
        $content .= pdf_line($marginL, $y, $pageW - $marginR, $y, 0.5);
        $y -= 30;
    } else {
        // EXTERNAL (SISCO) memo fields
        $content .= pdf_text($marginL, $y, 'F1', 11, 'Date                : ' . $dateStr); $y -= 20;
        $content .= pdf_text($marginL,      $y, 'F2', 11, 'To');
        $content .= pdf_text($marginL + 75, $y, 'F1', 11, ': ' . $gaManager); $y -= 14;
        $content .= pdf_text($marginL + 85, $y, 'F1', 10, 'GA - Admin / Senior Manager'); $y -= 25;
        $content .= pdf_text($marginL,      $y, 'F2', 11, 'Thru');
        $content .= pdf_text($marginL + 75, $y, 'F1', 11, ': ' . $gaStaffName);
        $content .= pdf_text($marginL + 180, $y, 'F1', 11, 'Ms. Cherry Buenconsejo'); $y -= 14;
        $content .= pdf_text($marginL + 85, $y, 'F1', 10, 'GA - Supervisor');
        $content .= pdf_text($marginL + 190, $y, 'F1', 10, 'GA - Senior Staff'); $y -= 40;
        $content .= pdf_text($marginL,      $y, 'F2', 11, 'Subject');
        $subjectLines = wrap_pdf_lines(': ' . $subjectLine, 50);
        foreach ($subjectLines as $i => $sLine) {
            $indent = ($i === 0) ? 70 : 77;
            $content .= pdf_text($marginL + $indent, $y, 'F2', 11, $sLine);
            if (count($subjectLines) > 1 && $i < count($subjectLines) - 1) $y -= 14;
        }
        $y -= 30;
        $content .= pdf_line($marginL, $y, $pageW - $marginR, $y, 0.5);
        $y -= 35;
    }

    // ── Body sections ─────────────────────────────────────────────────────────
    $bodySections = [
        'Details'      => $report['details']       ?? '',
        'Action Taken' => $report['actions_taken'] ?? '',
        'Remarks'      => $report['remarks']        ?? '',
        'Security Remarks' => $report['security_remarks'] ?? '',
    ];
    foreach ($bodySections as $label => $val) {
        $val = trim((string)$val);
        if ($val === '' || strtolower($val) === 'n/a') continue;
        $content .= pdf_text($marginL, $y, 'F2', 11, $label . ':'); $y -= 16;
        foreach (wrap_pdf_lines($val, 95) as $line) {
            if ($y < $bottomY + 20) $start_new_page();
            $content .= pdf_text($marginL + 15, $y, 'F1', 10, $line); $y -= 14;
        }
        $y -= 15;
    }

    // ── Evidence / Attachments ────────────────────────────────────────────────
    $evidenceImageObjects = [];
    if (!empty($evidence)) {
        if ($y < 200) $start_new_page();
        $content .= pdf_text($marginL, $y, 'F2', 11, 'Evidence / Attachments:'); $y -= 30;

        if (!$gdAvailable) {
            // FIX #2: uses the local $gdAvailable — not a missing global
            $content .= pdf_text($marginL + 15, $y, 'F1', 10, 'Images omitted (PHP GD extension is not enabled).');
            $y -= 20;
        } else {
            foreach ($evidence as $img) {
                $imgLocalPath = dirname(__DIR__) . DIRECTORY_SEPARATOR
                    . str_replace('/', DIRECTORY_SEPARATOR, $img['file_path']);
                if (!is_file($imgLocalPath)) continue;
                $evidImg = build_pdf_image_objects_from_rgba($imgLocalPath);
                if (!$evidImg) continue;
                $dispW = 400.0;
                $dispH = ($evidImg['h'] / $evidImg['w']) * $dispW;
                if ($y - $dispH < 100) $start_new_page();
                $imgRef   = 'ImEvid' . $img['id'];
                $content .= sprintf("q\n%.2f 0 0 %.2f %.2f %.2f cm\n/$imgRef Do\nQ\n",
                    $dispW, $dispH, $marginL, $y - $dispH);
                $y -= ($dispH + 30);
                $evidenceImageObjects[$imgRef] = $evidImg;
            }
        }
    }

    // ── Signature ─────────────────────────────────────────────────────────────
    $y -= 30;
    if ($y < 150) $start_new_page();
    $officerName  = strtoupper((string)($report['submitted_by_name'] ?? 'OFFICER NAME'));
    $officerBuilding = strtoupper((string)($report['building'] ?? 'NCFL'));

    // FIX #4: use the correct agency label based on $template
    $agencyLabel = ($template === 'internal') ? 'Internal Security' : 'External Security';

    $content .= pdf_text($marginL, $y, 'F1', 11, 'Prepared by:'); $y -= 45;
    $content .= pdf_text($marginL, $y, 'F2', 11, $officerName);   $y -= 14;
    $content .= pdf_text($marginL, $y, 'F1', 10, $officerBuilding . ' / Security Officer'); $y -= 12;
    $content .= pdf_text($marginL, $y, 'F1', 10, $agencyLabel);

    // ── Assemble PDF objects ──────────────────────────────────────────────────
    if ($content !== '') $pages[] = $content;

    $objects   = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[] = "2 0 obj\n<< /Type /Pages /Kids %KIDS% /Count %COUNT% >>\nendobj\n";
    $objects[] = "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica      /Encoding /WinAnsiEncoding >>\nendobj\n";
    $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>\nendobj\n";

    $imgObjNum = null;
    if ($logo) {
        $objects[] = "5 0 obj\n" . $logo['maskObj'] . "\nendobj\n";
        $img       = str_replace('%SMASK%', '5', $logo['imgObj']);
        $objects[] = "6 0 obj\n" . $img . "\nendobj\n";
        $imgObjNum = 6;
    }

    $evidMap    = [];
    $nextObjIdx = count($objects) + 1;
    foreach ($evidenceImageObjects as $ref => $data) {
        if (!empty($data['maskObj'])) {
            $maskIdx         = $nextObjIdx++;
            $imgIdx          = $nextObjIdx++;
            $objects[]       = "$maskIdx 0 obj\n" . $data['maskObj'] . "\nendobj\n";
            $objects[]       = "$imgIdx 0 obj\n"  . str_replace('%SMASK%', (string)$maskIdx, $data['imgObj']) . "\nendobj\n";
        } else {
            $imgIdx    = $nextObjIdx++;
            $objects[] = "$imgIdx 0 obj\n" . $data['imgObj'] . "\nendobj\n";
        }
        $evidMap[$ref] = $imgIdx;
    }

    $pageNums = [];
    $nextObj  = $nextObjIdx;
    foreach ($pages as $pageContent) {
        $contentObjNum = $nextObj++;
        $pageObjNum    = $nextObj++;
        $xRefs = '';
        if ($imgObjNum) $xRefs .= "/Im1 $imgObjNum 0 R ";
        foreach ($evidMap as $ref => $idx) $xRefs .= "/$ref $idx 0 R ";
        $objects[] = $contentObjNum . " 0 obj\n<< /Length " . strlen($pageContent) . " >>\nstream\n" . $pageContent . "endstream\nendobj\n";
        $res       = "<< /Font << /F1 3 0 R /F2 4 0 R >> /XObject << $xRefs >> >>";
        $objects[] = $pageObjNum . " 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageW $pageH] /Resources $res /Contents $contentObjNum 0 R >>\nendobj\n";
        $pageNums[] = $pageObjNum;
    }

    $kids       = '[ ' . implode(' ', array_map(fn($n) => $n . ' 0 R', $pageNums)) . ' ]';
    $objects[1] = str_replace(['%KIDS%', '%COUNT%'], [$kids, (string)count($pageNums)], $objects[1]);

    $pdf     = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) { $offsets[] = strlen($pdf); $pdf .= $obj; }
    $xrefPos = strlen($pdf);
    $count   = count($objects) + 1;
    $pdf .= "xref\n0 $count\n0000000000 65535 f \n";
    for ($i = 1; $i < $count; $i++) {
        $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }
    $pdf .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF";

    if (ob_get_length()) ob_clean();
    header('Content-Type: application/pdf');
    $disposition = ($isPreview || $showGrid) ? 'inline' : 'attachment';
    header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}

// ── Entry point ───────────────────────────────────────────────────────────────
$reportNoOut = (string)($report['report_no'] ?? $reportNo);
$filename    = 'report_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $reportNoOut) . '_' . date('Ymd_His') . '.pdf';
output_report_template_pdf($report, $filename, $evidence);