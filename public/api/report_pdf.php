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
        u_submit.name AS submitted_by_name,
    u_submit.security_type AS submitted_by_security_type,
        gasr.reviewed_at,
        gasr.notes AS ga_staff_notes,
        u_staff.name AS ga_staff_reviewer,
        gapa.decided_at,
        gapa.decision AS ga_president_decision,
        gapa.notes AS ga_president_notes,
        u_pres.name AS ga_president_name,
        da.action_type,
        da.timeline_days,
        da.timeline_start,
        da.timeline_due,
        da.remarks AS dept_remarks,
        da.acted_at AS dept_acted_at,
        u_dept.name AS dept_acted_by,
        sfc.checked_at AS final_checked_at,
        sfc.decision AS final_decision,
        sfc.remarks AS final_remarks,
        u_sec.name AS final_checked_by,
        sfc.closed_at
     FROM reports r
     JOIN departments d ON d.id = r.responsible_department_id
LEFT JOIN users u_submit ON u_submit.id = r.submitted_by
LEFT JOIN ga_staff_reviews gasr ON gasr.report_id = r.id
LEFT JOIN users u_staff ON u_staff.id = gasr.reviewed_by
LEFT JOIN ga_president_approvals gapa ON gapa.report_id = r.id
LEFT JOIN users u_pres ON u_pres.id = gapa.decided_by
LEFT JOIN department_actions da ON da.report_id = r.id
LEFT JOIN users u_dept ON u_dept.id = da.acted_by
LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
LEFT JOIN users u_sec ON u_sec.id = sfc.checked_by
    WHERE r.report_no = ?";

$sql .= $whereExtra . ' LIMIT 1';

$report = db_fetch_one($sql, '', $params);
if (!$report) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Report not found']);
    exit;
}

function pdf_escape_text(string $s): string {
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
}

function norm_security_type(?string $raw): string {
    $v = strtolower(trim((string)$raw));
    return in_array($v, ['internal', 'external'], true) ? $v : 'external';
}

function parse_jpg(string $path): ?array {
    $data = @file_get_contents($path);
    if ($data === false) return null;
    
    // Simple check for JPEG header
    if (substr($data, 0, 3) !== "\xFF\xD8\xFF") return null;

    $size = @getimagesize($path);
    if (!$size) return null;

    $w = $size[0];
    $h = $size[1];

    // For JPEG, we don't need to uncompress it like PNG. 
    // We just wrap the raw data in a PDF stream.
    $stream = gzcompress($data, 6);

    $imgDict = "<< /Type /XObject /Subtype /Image /Width $w /Height $h /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($data) . " >>";
    $imgObj = $imgDict . "\nstream\n" . $data . "\nendstream";

    // JPEGs usually don't have transparency masks in this context
    return ['imgObj' => $imgObj, 'maskObj' => null, 'w' => $w, 'h' => $h];
}

function parse_png_rgba(string $path): ?array {
    $data = @file_get_contents($path);
    if ($data === false || strlen($data) < 64) return null;
    if (substr($data, 0, 8) !== "\x89PNG\r\n\x1a\n") return null;

    $offset = 8;
    $len = strlen($data);

    $width = null;
    $height = null;
    $bitDepth = null;
    $colorType = null;
    $interlace = null;

    $idat = '';
    while ($offset + 8 <= $len) {
        $chunkLen = unpack('N', substr($data, $offset, 4))[1];
        $type = substr($data, $offset + 4, 4);
        $chunkData = substr($data, $offset + 8, $chunkLen);
        $offset += 12 + $chunkLen;

        if ($type === 'IHDR') {
            if (strlen($chunkData) < 13) return null;
            $width = unpack('N', substr($chunkData, 0, 4))[1];
            $height = unpack('N', substr($chunkData, 4, 4))[1];
            $bitDepth = ord($chunkData[8]);
            $colorType = ord($chunkData[9]);
            $interlace = ord($chunkData[12]);
        } elseif ($type === 'IDAT') {
            $idat .= $chunkData;
        } elseif ($type === 'IEND') {
            break;
        }
    }

    if (!$width || !$height || $bitDepth !== 8 || $colorType !== 6) return null;
    if ($interlace !== 0) return null; // only non-interlaced supported

    $raw = @gzuncompress($idat);
    if ($raw === false) {
        // fallback for some zlib streams
        $raw = @gzinflate(substr($idat, 2, -4));
        if ($raw === false) return null;
    }

    $bpp = 4;
    $stride = $width * $bpp;
    $rowLen = 1 + $stride;
    if (strlen($raw) < $rowLen * $height) return null;

    $out = '';
    $prev = array_fill(0, $stride, 0);
    $pos = 0;
    for ($y = 0; $y < $height; $y++) {
        $filter = ord($raw[$pos]);
        $pos++;
        $row = substr($raw, $pos, $stride);
        $pos += $stride;

        $cur = [];
        for ($i = 0; $i < $stride; $i++) {
            $x = ord($row[$i]);
            $left = ($i >= $bpp) ? $cur[$i - $bpp] : 0;
            $up = $prev[$i];
            $upLeft = ($i >= $bpp) ? $prev[$i - $bpp] : 0;

            if ($filter === 0) {
                $val = $x;
            } elseif ($filter === 1) {
                $val = ($x + $left) & 0xFF;
            } elseif ($filter === 2) {
                $val = ($x + $up) & 0xFF;
            } elseif ($filter === 3) {
                $val = ($x + intdiv($left + $up, 2)) & 0xFF;
            } elseif ($filter === 4) {
                $p = $left + $up - $upLeft;
                $pa = abs($p - $left);
                $pb = abs($p - $up);
                $pc = abs($p - $upLeft);
                if ($pa <= $pb && $pa <= $pc) $pr = $left;
                elseif ($pb <= $pc) $pr = $up;
                else $pr = $upLeft;
                $val = ($x + $pr) & 0xFF;
            } else {
                return null;
            }

            $cur[$i] = $val;
        }

        $prev = $cur;
        $bytes = '';
        foreach ($cur as $b) {
            $bytes .= chr($b);
        }
        $out .= $bytes;
    }

    return ['w' => $width, 'h' => $height, 'rgba' => $out];
}

function build_pdf_image_objects_from_rgba($path): ?array {
    // FIX: If $path is accidentally passed as an array (the database row), 
    // extract the 'file_path' string from it.
    if (is_array($path)) {
        if (!isset($path['file_path'])) return null;
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path['file_path']);
    }

    if (!is_string($path) || !is_file($path)) return null;

    $info = @getimagesize($path);
    if (!$info) return null;

    // GD is required for imagecreatefrompng/jpeg and pixel access
    if (!function_exists('imagecreatefrompng') || !function_exists('imagecreatefromjpeg') || !function_exists('imagecolorat') || !function_exists('imagecolorsforindex')) {
        return null;
    }
    
    // Support both PNG and JPG via GD
    $src = ($info['mime'] === 'image/png') ? @imagecreatefrompng($path) : @imagecreatefromjpeg($path);
    if (!$src) return null;

    $w = imagesx($src);
    $h = imagesy($src);

    $rgb = ''; $alpha = '';
    for ($py = 0; $py < $h; $py++) {
        for ($px = 0; $px < $w; $px++) {
            $colorIdx = imagecolorat($src, $px, $py);
            $c = imagecolorsforindex($src, $colorIdx);
            $rgb .= chr($c['red']) . chr($c['green']) . chr($c['blue']);
            $alphaByte = (int)((127 - $c['alpha']) * 2);
            $alpha .= chr($alphaByte);
        }
    }
    imagedestroy($src);

    $rgbS = gzcompress($rgb, 6);
    $maskS = gzcompress($alpha, 6);

    return [
        'maskObj' => "<< /Type /XObject /Subtype /Image /Width $w /Height $h /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode /Length " . strlen($maskS) . " >>\nstream\n" . $maskS . "\nendstream",
        'imgObj'  => "<< /Type /XObject /Subtype /Image /Width $w /Height $h /ColorSpace /DeviceRGB /BitsPerComponent 8 /SMask %SMASK% 0 R /Filter /FlateDecode /Length " . strlen($rgbS) . " >>\nstream\n" . $rgbS . "\nendstream",
        'w' => $w, 'h' => $h
    ];
}

$gdAvailableForPdfImages = function_exists('imagecreatefrompng') && function_exists('imagecreatefromjpeg') && function_exists('imagecolorat') && function_exists('imagecolorsforindex');

function wrap_pdf_lines(string $text, int $maxLen): array {
    $text = trim($text);
    if ($text === '') return [];

    $out = [];
    // Standardize newlines
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    
    // Use explode instead of preg_split for the first split to avoid regex errors
    $paragraphs = explode("\n", $text);

    foreach ($paragraphs as $p) {
        $p = trim($p);
        if ($p === '') {
            // Keep empty lines as spacing if needed, or skip them
            continue; 
        }

        // Split words by space/tabs
        $words = preg_split('/\s+/', $p, -1, PREG_SPLIT_NO_EMPTY);
        $line = '';
        
        foreach ($words as $w) {
            if ($line === '') {
                $line = $w;
                continue;
            }
            // Check if adding this word exceeds max length
            if (strlen($line) + 1 + strlen($w) <= $maxLen) {
                $line .= ' ' . $w;
            } else {
                $out[] = $line;
                $line = $w;
            }
        }
        if ($line !== '') $out[] = $line;
    }

    return $out;
}

function pdf_text(float $x, float $y, string $font, int $size, string $text): string {
    $x = number_format($x, 2, '.', '');
    $y = number_format($y, 2, '.', '');
    return "BT\n/$font $size Tf\n$x $y Td\n(" . pdf_escape_text($text) . ") Tj\nET\n";
}

function pdf_line(float $x1, float $y1, float $x2, float $y2, float $width = 1.0): string {
    $x1 = number_format($x1, 2, '.', '');
    $y1 = number_format($y1, 2, '.', '');
    $x2 = number_format($x2, 2, '.', '');
    $y2 = number_format($y2, 2, '.', '');
    $w = number_format($width, 2, '.', '');
    return "$w w\n$x1 $y1 m\n$x2 $y2 l\nS\n";
}

// Query the correct table: report_attachments
$evidenceSql = "SELECT id, file_path FROM report_attachments WHERE report_id = ?";
$evidence = db_fetch_all($evidenceSql, '', [$report['id']]);

$evidenceImageObjects = []; // This MUST be outside all loops

// Change this line
function output_report_template_pdf(array $report, string $filename, array $evidence = []): void {
    // Legal portrait: 8.5x14 inches
    $pageW = 612;
    $pageH = 1008;

    $isPreview = isset($_GET['preview']) && $_GET['preview'] == '1';
    $showGrid = isset($_GET['grid']) && $_GET['grid'] == '1';
    
    // --- 1. DYNAMIC NAMES FROM DATABASE ---
    // Fetching names based on your existing $report array keys
    $gaManager = !empty($report['ga_president_name']) ? strtoupper($report['ga_president_name']) : "MS. KAREN F. ENRIQUEZ";
    $gaStaffName = !empty($report['ga_staff_reviewer']) ? strtoupper($report['ga_staff_reviewer']) : "MS. LIZA ACOSTA";
    $subjectLine = strtoupper((string)($report['category'] ?? 'REPORT')) . " RE: " . strtoupper((string)($report['subject'] ?? ''));
    $dateStr = !empty($report['submitted_at']) ? date('d F Y', strtotime($report['submitted_at'])) : date('d F Y');

    $template = norm_security_type($report['submitted_by_security_type'] ?? null);

    // Logo setup
    $logoFile = ($template === 'internal') ? 'assets/images/internal-logo.png' : 'assets/images/external-logo.png';
    $logoPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $logoFile);
    $logo = null;

    if (is_file($logoPath)) {
        // PASS THE STRING PATH, NOT AN ARRAY
        $logo = build_pdf_image_objects_from_rgba($logoPath);
    }

    $headerLines = [];
    if ($template === 'internal') {
        $headerLines = [
            ['font' => 'F2', 'size' => 14, 'text' => 'ARAGON SECURITY AND INVESTIGATION'],
            ['font' => 'F2', 'size' => 11, 'text' => 'AGENCY, CORPORATION'],
            ['font' => 'F1', 'size' => 10, 'text' => 'NIDEC PHILIPPINES CORPORATION DETACHMENT'],
            ['font' => 'F1', 'size' => 9, 'text' => '136 North Science Avenue Extension, Laguna Technopark, Binan, Laguna'],
        ];
    } else {
        $headerLines = [
            ['font' => 'F2', 'size' => 12, 'text' => 'SISCO INVESTIGATION & SECURITY CORPORATION'],
            ['font' => 'F1', 'size' => 10, 'text' => 'NIDEC Philippines Corporation - Security Detachment'],
            ['font' => 'F1', 'size' => 9, 'text' => '119 Technology Avenue Special Economic Zone Laguna Technopark, Biñan Laguna'],
        ];
    }

    $marginL = 50; $marginR = 50; $topY = $pageH - 50; $bottomY = 55;
    $pages = []; $content = ''; $y = $topY;

    // --- PAGE GENERATOR FUNCTION ---
    $start_new_page = function () use (&$pages, &$content, &$y, $topY, $marginL, $pageW, $marginR, $template, $headerLines, $logo, $pageH, $showGrid): void {
        if ($content !== '') {
            $pages[] = $content;
        }

        $content = '';
        $y = $topY;

        // 1. Grid Logic
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

        // 2. Centered Header Logic
        $horizontalGap = 5;
        $textAreaWidth = 320;

        // Compute text origin; if the logo exists we draw it and shift text right.
        $tx = ($pageW / 2) - ($textAreaWidth / 2);

        if ($logo) {
            $targetH = 75.0;
            $scale = $targetH / max(1.0, (float)$logo['h']);
            $drawW = (float)$logo['w'] * $scale;
            $drawH = (float)$logo['h'] * $scale;

            $approxTextWidth = 300;
            $totalHeaderWidth = $drawW + $horizontalGap + $approxTextWidth;

            // Calculate starting X to center the group
            $startX = ($pageW / 2) - ($totalHeaderWidth / 2) + -20;

            // Draw Logo
            $yImg = $y - $drawH + 15;
            $content .= "q\n" . number_format($drawW, 2, '.', '') . " 0 0 " . number_format($drawH, 2, '.', '') . " " . number_format($startX, 2, '.', '') . " " . number_format($yImg, 2, '.', '') . " cm\n/Im1 Do\nQ\n";

            // Draw Text next to logo
            $tx = $startX + $drawW + $horizontalGap;
        }

        // Header text should not depend on GD/logo availability
        foreach ($headerLines as $idx => $hl) {
            $lineY = $y - ($idx * 16);
            $fontSize = ($idx < 2) ? 14 : (int)$hl['size'];
            $fontObj = ($idx < 2) ? 'F2' : 'F1';
            $text = (string)$hl['text'];

            // --- PRECISE CENTERING CALCULATION ---
            $widthFactor = ($fontObj === 'F2') ? 0.52 : 0.46;
            $estimatedLineWidth = strlen($text) * ($fontSize * $widthFactor);
            $lineX = $tx + ($textAreaWidth / 2) - ($estimatedLineWidth / 2);

            if ($template === 'internal' && $idx === 0) {
                // Line 1: ARAGON (Red) + SECURITY... (Blue)
                $fullLine = "ARAGON SECURITY AND INVESTIGATION";
                $fullWidth = strlen($fullLine) * ($fontSize * 0.52);
                $line1X = $tx + ($textAreaWidth / 2) - ($fullWidth / 2);

                $content .= "0.7 0 0 rg\n";
                $content .= pdf_text($line1X, $lineY, 'F2', $fontSize, 'ARAGON ');
                $content .= "0 0.3 0.6 rg\n";
                $content .= pdf_text($line1X + 68, $lineY, 'F2', $fontSize, 'SECURITY AND INVESTIGATION');

            } elseif ($template === 'internal' && $idx === 1) {
                // Line 2: AGENCY, CORPORATION
                $content .= "0 0.3 0.6 rg\n";
                $content .= pdf_text($lineX, $lineY, 'F2', $fontSize, $text);

            } else {
                // Line 3 & 4 (Address line)
                $content .= "0 0 0 rg\n";
                $nudge = ($idx === 3) ? 10 : 0; // Move line 3 right by 10 points
                $content .= pdf_text($lineX + $nudge, $lineY, $fontObj, $fontSize, $text);
            }
        }
        $y -= 80; // Space for the header height
    }; // <--- THIS SEMICOLON IS REQUIRED
    // --- END OF REPLACEMENT BLOCK ---

    $start_new_page();

    // --- CONSOLIDATED HEADER LOGIC ---
    // --- CONSOLIDATED HEADER LOGIC ---
    if ($template === 'internal') {
        // --- INTERNAL TEMPLATE (ARAGON) ---
        $content .= "BT /F2 12 Tf " . $marginL . " " . $y . " Td (MEMORANDUM) ET\n"; $y -= 25;
        $content .= pdf_text($marginL, $y, 'F2', 11, 'FOR');
        $content .= pdf_text($marginL + 85, $y, 'F1', 11, ': ' . $gaManager);
        $content .= pdf_text($marginL + 95, $y - 14, 'F1', 10, 'Senior GA Manager'); $y -= 45;
        $content .= pdf_text($marginL, $y, 'F2', 11, 'THRU');
        $content .= pdf_text($marginL + 85, $y, 'F1', 11, ': ' . $gaStaffName);
        $content .= pdf_text($marginL + 95, $y - 14, 'F1', 10, 'General Affair Supervisor'); $y -= 45;
        $content .= pdf_text($marginL, $y, 'F2', 11, 'SUBJECT');
        $content .= pdf_text($marginL + 85, $y, 'F2', 11, ': ');
        foreach (wrap_pdf_lines($subjectLine, 50) as $index => $line) {
            if ($index > 0) $y -= 14;
            $content .= pdf_text($marginL + 95, $y, 'F2', 11, $line);
        }
        $y -= 35;
        $content .= pdf_text($marginL, $y, 'F2', 11, 'DATE');
        $content .= pdf_text($marginL + 85, $y, 'F1', 11, ': ' . strtoupper($dateStr)); $y -= 25;
        // The Line for Internal
        $content .= pdf_line($marginL, $y, $pageW - $marginR, $y, 0.5); 
        $y -= 30;
    } else {
        // --- EXTERNAL TEMPLATE (SISCO) ---
        // 1. Date line at the top
        $content .= pdf_text($marginL, $y, 'F1', 11, 'Date                : ' . $dateStr);
        $y -= 20;

        // 2. "To" Section
        $content .= pdf_text($marginL, $y, 'F2', 11, 'To');
        $content .= pdf_text($marginL + 75, $y, 'F1', 11, ': ' . $gaManager);
        $y -= 14;
        $content .= pdf_text($marginL + 85, $y, 'F1', 10, 'GA – Admin / Senior Manager');
        $y -= 25;

        // 3. "Thru" Section
        $content .= pdf_text($marginL, $y, 'F2', 11, 'Thru');
        $content .= pdf_text($marginL + 75, $y, 'F1', 11, ': ' . $gaStaffName);
        $content .= pdf_text($marginL + 180, $y, 'F1', 11, 'Ms. Cherry Buenconsejo'); 
        $y -= 14;
        $content .= pdf_text($marginL + 85, $y, 'F1', 10, 'GA - Supervisor');
        $content .= pdf_text($marginL + 190, $y, 'F1', 10, 'GA- Senior Staff');
        $y -= 40;

        // 4. Subject Section (Bold)
        $content .= pdf_text($marginL, $y, 'F2', 11, 'Subject');
        $content .= pdf_text($marginL + 75, $y, 'F2', 11, ': ' . strtoupper($subjectLine));
        
        // Separation Space (No Line)
        $y -= 40; 
    }

    // --- CONDITIONAL BODY SECTIONS ---
    $bodySections = [
        'Details'        => $report['details'] ?? '',
        'Observation'    => $report['observation'] ?? '',
        'Action Taken'   => $report['actions_taken'] ?? '',
        'Remarks'        => $report['remarks'] ?? '',
        'Assessment'     => $report['assessment'] ?? '',
        'Recommendation' => $report['recommendation'] ?? ''
    ];

    foreach ($bodySections as $label => $val) {
        $val = trim((string)$val);
        // This 'if' hides the label if the value is empty or N/A
        if ($val !== '' && strtolower($val) !== 'n/a') {
            $content .= pdf_text($marginL, $y, 'F2', 11, $label . ':'); 
            $y -= 16;
            foreach (wrap_pdf_lines($val, 95) as $line) {
                if ($y < $bottomY + 20) { $start_new_page(); }
                $content .= pdf_text($marginL + 15, $y, 'F1', 10, $line);
                $y -= 14;
            }
            $y -= 15; // Gap between sections
        }
    }



    // --- EVIDENCE PHOTOS SECTION ---
    $evidenceImageObjects = []; 

    if (!empty($evidence)) {
        if ($y < 200) { $start_new_page(); } 
        $content .= pdf_text($marginL, $y, 'F2', 11, 'Evidence / Attachments:');
        $y -= 30;

        if (!$gdAvailableForPdfImages) {
            $content .= pdf_text($marginL + 15, $y, 'F1', 10, 'Images omitted (PHP GD extension is not enabled).');
            $y -= 20;
        } else {
        foreach ($evidence as $img) {
        $imgLocalPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $img['file_path']);
        
        if (is_file($imgLocalPath)) {
            // We pass the string path here
            $evidImg = build_pdf_image_objects_from_rgba($imgLocalPath);

            if ($evidImg) {
                $dispW = 400.0;
                $dispH = ($evidImg['h'] / $evidImg['w']) * $dispW;

                if ($y - $dispH < 100) { $start_new_page(); }

                $imgRef = "ImEvid" . $img['id'];
                $content .= "q\n" . sprintf("%.2f 0 0 %.2f %.2f %.2f cm\n", $dispW, $dispH, $marginL, $y - $dispH) . "/$imgRef Do\nQ\n";
                
                $y -= ($dispH + 30);
                $evidenceImageObjects[$imgRef] = $evidImg;
            }
        }
    }
        }
    }

    // --- SIGNATURE SECTION ---
    $y -= 30;
    if ($y < 150) { $start_new_page(); }
    $officerName = strtoupper((string)($report['submitted_by_name'] ?? 'OFFICER NAME'));
    $officerRole = (string)($report['building'] ?? 'NCFL') . " / Security Officer";

    $content .= pdf_text($marginL, $y, 'F1', 11, 'Prepared by:'); $y -= 45; 
    $content .= pdf_text($marginL, $y, 'F2', 11, $officerName); $y -= 14;
    $content .= pdf_text($marginL, $y, 'F1', 10, $officerRole); $y -= 12;
    $content .= pdf_text($marginL, $y, 'F1', 10, 'Internal Security');

    // --- FINAL PDF ASSEMBLY ---
    if ($content !== '') { $pages[] = $content; }

    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[] = "2 0 obj\n<< /Type /Pages /Kids %KIDS% /Count %COUNT% >>\nendobj\n";
    $objects[] = "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";

    // Register Logo
    $imgObjNum = null;
    if ($logo) {
        $objects[] = "5 0 obj\n" . $logo['maskObj'] . "\nendobj\n";
        $img = str_replace('%SMASK%', '5', $logo['imgObj']);
        $objects[] = "6 0 obj\n" . $img . "\nendobj\n";
        $imgObjNum = 6;
    }

    // Register Evidence Images
    $evidMap = [];
    $nextObjIdx = count($objects) + 1;
    foreach ($evidenceImageObjects as $ref => $data) {
        if (!empty($data['maskObj'])) {
            $maskIdx = $nextObjIdx++;
            $imgIdx = $nextObjIdx++;
            $objects[] = "$maskIdx 0 obj\n" . $data['maskObj'] . "\nendobj\n";
            $objects[] = "$imgIdx 0 obj\n" . str_replace('%SMASK%', (string)$maskIdx, $data['imgObj']) . "\nendobj\n";
        } else {
            $imgIdx = $nextObjIdx++;
            $objects[] = "$imgIdx 0 obj\n" . $data['imgObj'] . "\nendobj\n";
        }
        $evidMap[$ref] = $imgIdx;
    }

    $pageNums = []; 
    $nextObj = $nextObjIdx;
    foreach ($pages as $pageContent) {
        $contentObjNum = $nextObj++; 
        $pageObjNum = $nextObj++;
        
        $xRefs = "";
        if ($imgObjNum) { $xRefs .= "/Im1 $imgObjNum 0 R "; }
        foreach ($evidMap as $ref => $idx) {
            $xRefs .= "/$ref $idx 0 R ";
        }

        $objects[] = $contentObjNum . " 0 obj\n<< /Length " . strlen($pageContent) . " >>\nstream\n" . $pageContent . "endstream\nendobj\n";
        
        $res = "<< /Font << /F1 3 0 R /F2 4 0 R >> /XObject << $xRefs >> >>";
        $objects[] = $pageObjNum . " 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageW $pageH] /Resources $res /Contents $contentObjNum 0 R >>\nendobj\n";
        $pageNums[] = $pageObjNum;
    }

    $kids = '[ ' . implode(' ', array_map(fn($n) => $n . ' 0 R', $pageNums)) . ' ]';
    $objects[1] = str_replace(['%KIDS%', '%COUNT%'], [$kids, (string)count($pageNums)], $objects[1]);

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) { $offsets[] = strlen($pdf); $pdf .= $obj; }
    $xrefPos = strlen($pdf); $count = count($objects) + 1;
    $pdf .= "xref\n0 $count\n0000000000 65535 f \n";
    for ($i = 1; $i < $count; $i++) { $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n"; }
    $pdf .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF";

    // --- RESTORED VIEWING LOGIC ---
    if (ob_get_length()) { ob_clean(); }
    header('Content-Type: application/pdf');
    
    // Check for preview/grid flags from the URL to decide between View or Download
    $disposition = (isset($_GET['preview']) && $_GET['preview'] == '1') || (isset($_GET['grid']) && $_GET['grid'] == '1') ? 'inline' : 'attachment';
    
    header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
} // Ends output_report_template_pdf function

// --- TRIGGER EXECUTION ---
$reportNoOut = (string)($report['report_no'] ?? $reportNo);
$filename = 'report_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $reportNoOut) . '_' . date('Ymd_His') . '.pdf';

output_report_template_pdf($report, $filename, $evidence);

    // --- PAGE CONSTRUCTION ---
    $pageNums = []; 
    $nextObj = $nextObjIdx;
    foreach ($pages as $pageContent) {
        $contentObjNum = $nextObj++; 
        $pageObjNum = $nextObj++;
        
        $stream = $pageContent;
        $objects[] = $contentObjNum . " 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream\nendobj\n";
        
        // Build the dictionary of all images
        $xobjMap = "";
        if ($imgObjNum) { $xobjMap .= "/Im1 $imgObjNum 0 R "; }
        foreach ($evidMap as $ref => $idx) {
            $xobjMap .= "/$ref $idx 0 R ";
        }

        $res = "<< /Font << /F1 3 0 R /F2 4 0 R >> /XObject << $xobjMap >> >>";
        $objects[] = $pageObjNum . " 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageW $pageH] /Resources $res /Contents $contentObjNum 0 R >>\nendobj\n";
        $pageNums[] = $pageObjNum;
    }

    $kids = '[ ' . implode(' ', array_map(fn($n) => $n . ' 0 R', $pageNums)) . ' ]';
    $objects[1] = str_replace(['%KIDS%', '%COUNT%'], [$kids, (string)count($pageNums)], $objects[1]);

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) { $offsets[] = strlen($pdf); $pdf .= $obj; }
    $xrefPos = strlen($pdf); $count = count($objects) + 1;
    $pdf .= "xref\n0 $count\n0000000000 65535 f \n";
    for ($i = 1; $i < $count; $i++) { $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n"; }
    $pdf .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF";

    if (ob_get_length()) { ob_clean(); }
    header('Content-Type: application/pdf');
    if (isset($_GET['grid']) && $_GET['grid'] == '1') {
        header('Content-Disposition: inline; filename="' . $filename . '"');
    } else {
        header('Content-Disposition: attachment; filename="' . $filename . '"');
    }
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;








function add_kv(array &$lines, string $k, $v): void {
    if ($v === null) return;
    $s = trim((string)$v);
    if ($s === '') return;
    $lines[] = $k . ': ' . $s;
}

// Prepare filename safely
$reportNoOut = (string)($report['report_no'] ?? $reportNo);
$filename = 'report_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $reportNoOut) . '_' . date('Ymd_His') . '.pdf';

// Trigger output
output_report_template_pdf($report, $filename, $evidence);
