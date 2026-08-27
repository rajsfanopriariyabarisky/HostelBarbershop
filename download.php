<?php
// ============================================================
// HOSTEL BARBERSHOP - RECEIPT PDF
// ============================================================

// Buffer output
if (ob_get_level() == 0) {
    ob_start();
}

// ============================================================
// AUTO GENERATE FONT FILES
// ============================================================
$fontDir = __DIR__ . '/fpdf/font/';
if (!is_dir($fontDir)) {
    mkdir($fontDir, 0777, true);
}

$helveticaFile = $fontDir . 'helvetica.php';
if (!file_exists($helveticaFile)) {
    $f = fopen($helveticaFile, 'w');
    fwrite($f, "<?php\n");
    fwrite($f, '$name = \'Helvetica\';' . "\n");
    fwrite($f, '$type = \'Core\';' . "\n");
    fwrite($f, '$up = -100;' . "\n");
    fwrite($f, '$ut = 50;' . "\n");
    fwrite($f, '$cw = array(' . "\n");
    fwrite($f, "    chr(0)=>278,chr(1)=>278,chr(2)=>278,chr(3)=>278,chr(4)=>278,chr(5)=>278,chr(6)=>278,chr(7)=>278,chr(8)=>278,chr(9)=>278,chr(10)=>278,chr(11)=>278,chr(12)=>278,chr(13)=>278,chr(14)=>278,chr(15)=>278,chr(16)=>278,chr(17)=>278,chr(18)=>278,chr(19)=>278,chr(20)=>278,chr(21)=>278,chr(22)=>278,chr(23)=>278,chr(24)=>278,chr(25)=>278,chr(26)=>278,chr(27)=>278,chr(28)=>278,chr(29)=>278,chr(30)=>278,chr(31)=>278,\n");
    fwrite($f, "    ' '=>278,'!'=>278,'\"'=>355,'#'=>556,'\$'=>556,'%'=>889,'&'=>667,\"'\"=>191,'('=>333,')'=>333,'*'=>389,'+'=>584,','=>278,'-'=>333,'.'=>278,'/'=>278,\n");
    fwrite($f, "    '0'=>556,'1'=>556,'2'=>556,'3'=>556,'4'=>556,'5'=>556,'6'=>556,'7'=>556,'8'=>556,'9'=>556,':'=>278,';'=>278,'<'=>584,'='=>584,'>'=>584,'?'=>556,\n");
    fwrite($f, "    '@'=>1015,'A'=>667,'B'=>667,'C'=>722,'D'=>722,'E'=>667,'F'=>611,'G'=>778,'H'=>722,'I'=>278,'J'=>500,'K'=>667,'L'=>556,'M'=>833,'N'=>722,'O'=>778,\n");
    fwrite($f, "    'P'=>667,'Q'=>778,'R'=>722,'S'=>667,'T'=>611,'U'=>722,'V'=>667,'W'=>944,'X'=>667,'Y'=>667,'Z'=>611,'['=>278,'\\\\'=>278,']'=>278,'^'=>469,'_'=>556,\n");
    fwrite($f, "    '`'=>333,'a'=>556,'b'=>556,'c'=>500,'d'=>556,'e'=>556,'f'=>278,'g'=>556,'h'=>556,'i'=>222,'j'=>222,'k'=>500,'l'=>222,'m'=>833,'n'=>556,'o'=>556,\n");
    fwrite($f, "    'p'=>556,'q'=>556,'r'=>333,'s'=>500,'t'=>278,'u'=>556,'v'=>500,'w'=>722,'x'=>500,'y'=>500,'z'=>500,'{'=>334,'|'=>260,'}'=>334,'~'=>584,chr(127)=>350,\n");
    fwrite($f, "    chr(128)=>556,chr(129)=>350,chr(130)=>222,chr(131)=>556,chr(132)=>333,chr(133)=>1000,chr(134)=>556,chr(135)=>556,chr(136)=>333,chr(137)=>1000,chr(138)=>667,chr(139)=>333,chr(140)=>1000,chr(141)=>350,chr(142)=>611,chr(143)=>350,chr(144)=>350,chr(145)=>222,chr(146)=>222,chr(147)=>333,chr(148)=>333,chr(149)=>350,chr(150)=>556,chr(151)=>1000,chr(152)=>333,chr(153)=>1000,\n");
    fwrite($f, "    chr(154)=>500,chr(155)=>333,chr(156)=>944,chr(157)=>350,chr(158)=>500,chr(159)=>667,chr(160)=>278,chr(161)=>333,chr(162)=>556,chr(163)=>556,chr(164)=>556,chr(165)=>556,chr(166)=>260,chr(167)=>556,chr(168)=>333,chr(169)=>737,chr(170)=>370,chr(171)=>556,chr(172)=>584,chr(173)=>333,chr(174)=>737,chr(175)=>333,\n");
    fwrite($f, "    chr(176)=>400,chr(177)=>584,chr(178)=>333,chr(179)=>333,chr(180)=>333,chr(181)=>556,chr(182)=>537,chr(183)=>278,chr(184)=>333,chr(185)=>333,chr(186)=>365,chr(187)=>556,chr(188)=>834,chr(189)=>834,chr(190)=>834,chr(191)=>611,chr(192)=>667,chr(193)=>667,chr(194)=>667,chr(195)=>667,chr(196)=>667,chr(197)=>667,\n");
    fwrite($f, "    chr(198)=>1000,chr(199)=>722,chr(200)=>667,chr(201)=>667,chr(202)=>667,chr(203)=>667,chr(204)=>278,chr(205)=>278,chr(206)=>278,chr(207)=>278,chr(208)=>722,chr(209)=>722,chr(210)=>778,chr(211)=>778,chr(212)=>778,chr(213)=>778,chr(214)=>778,chr(215)=>584,chr(216)=>778,chr(217)=>722,chr(218)=>722,chr(219)=>722,\n");
    fwrite($f, "    chr(220)=>722,chr(221)=>667,chr(222)=>667,chr(223)=>611,chr(224)=>556,chr(225)=>556,chr(226)=>556,chr(227)=>556,chr(228)=>556,chr(229)=>556,chr(230)=>889,chr(231)=>500,chr(232)=>556,chr(233)=>556,chr(234)=>556,chr(235)=>556,chr(236)=>278,chr(237)=>278,chr(238)=>278,chr(239)=>278,chr(240)=>556,chr(241)=>556,\n");
    fwrite($f, "    chr(242)=>556,chr(243)=>556,chr(244)=>556,chr(245)=>556,chr(246)=>556,chr(247)=>584,chr(248)=>611,chr(249)=>556,chr(250)=>556,chr(251)=>556,chr(252)=>556,chr(253)=>500,chr(254)=>556,chr(255)=>500);\n");
    fwrite($f, '$enc = \'cp1252\';' . "\n");
    fwrite($f, '$uv = array(0=>array(0,128),128=>8364,130=>8218,131=>402,132=>8222,133=>8230,134=>array(8224,2),136=>710,137=>8240,138=>352,139=>8249,140=>338,142=>381,145=>array(8216,2),147=>array(8220,2),149=>8226,150=>array(8211,2),152=>732,153=>8482,154=>353,155=>8250,156=>339,158=>382,159=>376,160=>array(160,96));' . "\n");
    fwrite($f, "?>\n");
    fclose($f);
}

$courierFile = $fontDir . 'courier.php';
if (!file_exists($courierFile)) {
    $f = fopen($courierFile, 'w');
    fwrite($f, "<?php\n");
    fwrite($f, '$name = \'Courier\';' . "\n");
    fwrite($f, '$type = \'Core\';' . "\n");
    fwrite($f, '$up = -100;' . "\n");
    fwrite($f, '$ut = 50;' . "\n");
    fwrite($f, '$cw = array(' . "\n");
    fwrite($f, "    chr(0)=>600,chr(1)=>600,chr(2)=>600,chr(3)=>600,chr(4)=>600,chr(5)=>600,chr(6)=>600,chr(7)=>600,chr(8)=>600,chr(9)=>600,chr(10)=>600,chr(11)=>600,chr(12)=>600,chr(13)=>600,chr(14)=>600,chr(15)=>600,chr(16)=>600,chr(17)=>600,chr(18)=>600,chr(19)=>600,chr(20)=>600,chr(21)=>600,chr(22)=>600,chr(23)=>600,chr(24)=>600,chr(25)=>600,chr(26)=>600,chr(27)=>600,chr(28)=>600,chr(29)=>600,chr(30)=>600,chr(31)=>600,\n");
    fwrite($f, "    ' '=>600,'!'=>600,'\"'=>600,'#'=>600,'\$'=>600,'%'=>600,'&'=>600,\"'\"=>600,'('=>600,')'=>600,'*'=>600,'+'=>600,','=>600,'-'=>600,'.'=>600,'/'=>600,\n");
    fwrite($f, "    '0'=>600,'1'=>600,'2'=>600,'3'=>600,'4'=>600,'5'=>600,'6'=>600,'7'=>600,'8'=>600,'9'=>600,':'=>600,';'=>600,'<'=>600,'='=>600,'>'=>600,'?'=>600,\n");
    fwrite($f, "    '@'=>600,'A'=>600,'B'=>600,'C'=>600,'D'=>600,'E'=>600,'F'=>600,'G'=>600,'H'=>600,'I'=>600,'J'=>600,'K'=>600,'L'=>600,'M'=>600,'N'=>600,'O'=>600,\n");
    fwrite($f, "    'P'=>600,'Q'=>600,'R'=>600,'S'=>600,'T'=>600,'U'=>600,'V'=>600,'W'=>600,'X'=>600,'Y'=>600,'Z'=>600,'['=>600,'\\\\'=>600,']'=>600,'^'=>600,'_'=>600,\n");
    fwrite($f, "    '`'=>600,'a'=>600,'b'=>600,'c'=>600,'d'=>600,'e'=>600,'f'=>600,'g'=>600,'h'=>600,'i'=>600,'j'=>600,'k'=>600,'l'=>600,'m'=>600,'n'=>600,'o'=>600,\n");
    fwrite($f, "    'p'=>600,'q'=>600,'r'=>600,'s'=>600,'t'=>600,'u'=>600,'v'=>600,'w'=>600,'x'=>600,'y'=>600,'z'=>600,'{'=>600,'|'=>600,'}'=>600,'~'=>600,chr(127)=>600,\n");
    fwrite($f, "    chr(128)=>600,chr(129)=>600,chr(130)=>600,chr(131)=>600,chr(132)=>600,chr(133)=>600,chr(134)=>600,chr(135)=>600,chr(136)=>600,chr(137)=>600,chr(138)=>600,chr(139)=>600,chr(140)=>600,chr(141)=>600,chr(142)=>600,chr(143)=>600,chr(144)=>600,chr(145)=>600,chr(146)=>600,chr(147)=>600,chr(148)=>600,chr(149)=>600,chr(150)=>600,chr(151)=>600,chr(152)=>600,chr(153)=>600,\n");
    fwrite($f, "    chr(154)=>600,chr(155)=>600,chr(156)=>600,chr(157)=>600,chr(158)=>600,chr(159)=>600,chr(160)=>600,chr(161)=>600,chr(162)=>600,chr(163)=>600,chr(164)=>600,chr(165)=>600,chr(166)=>600,chr(167)=>600,chr(168)=>600,chr(169)=>600,chr(170)=>600,chr(171)=>600,chr(172)=>600,chr(173)=>600,chr(174)=>600,chr(175)=>600,\n");
    fwrite($f, "    chr(176)=>600,chr(177)=>600,chr(178)=>600,chr(179)=>600,chr(180)=>600,chr(181)=>600,chr(182)=>600,chr(183)=>600,chr(184)=>600,chr(185)=>600,chr(186)=>600,chr(187)=>600,chr(188)=>600,chr(189)=>600,chr(190)=>600,chr(191)=>600,chr(192)=>600,chr(193)=>600,chr(194)=>600,chr(195)=>600,chr(196)=>600,chr(197)=>600,\n");
    fwrite($f, "    chr(198)=>600,chr(199)=>600,chr(200)=>600,chr(201)=>600,chr(202)=>600,chr(203)=>600,chr(204)=>600,chr(205)=>600,chr(206)=>600,chr(207)=>600,chr(208)=>600,chr(209)=>600,chr(210)=>600,chr(211)=>600,chr(212)=>600,chr(213)=>600,chr(214)=>600,chr(215)=>600,chr(216)=>600,chr(217)=>600,chr(218)=>600,chr(219)=>600,\n");
    fwrite($f, "    chr(220)=>600,chr(221)=>600,chr(222)=>600,chr(223)=>600,chr(224)=>600,chr(225)=>600,chr(226)=>600,chr(227)=>600,chr(228)=>600,chr(229)=>600,chr(230)=>600,chr(231)=>600,chr(232)=>600,chr(233)=>600,chr(234)=>600,chr(235)=>600,chr(236)=>600,chr(237)=>600,chr(238)=>600,chr(239)=>600,chr(240)=>600,chr(241)=>600,\n");
    fwrite($f, "    chr(242)=>600,chr(243)=>600,chr(244)=>600,chr(245)=>600,chr(246)=>600,chr(247)=>600,chr(248)=>600,chr(249)=>600,chr(250)=>600,chr(251)=>600,chr(252)=>600,chr(253)=>600,chr(254)=>600,chr(255)=>600);\n");
    fwrite($f, '$enc = \'cp1252\';' . "\n");
    fwrite($f, '$uv = array(0=>array(0,128),128=>8364,130=>8218,131=>402,132=>8222,133=>8230,134=>array(8224,2),136=>710,137=>8240,138=>352,139=>8249,140=>338,142=>381,145=>array(8216,2),147=>array(8220,2),149=>8226,150=>array(8211,2),152=>732,153=>8482,154=>353,155=>8250,156=>339,158=>382,159=>376,160=>array(160,96));' . "\n");
    fwrite($f, "?>\n");
    fclose($f);
}

define('FPDF_FONTPATH', $fontDir);
require_once __DIR__ . '/fpdf/fpdf.php';

// ============================================================
// DATABASE CONNECTION
// ============================================================
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'barbernaatu';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ============================================================
// FETCH BOOKING DATA
// ============================================================
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id <= 0) {
    die("Invalid booking ID");
}

$sql = "SELECT 
            b.id as booking_id,
            b.tanggal,
            b.jam,
            b.status,
            b.created_at as booked_at,
            u.username as customer_name,
            u.email as customer_email,
            u.phone as customer_phone,
            p.nama_paket as package_name,
            p.harga as package_price,
            br.nama as barber_name
        FROM booking b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN paket p ON b.paket_id = p.id
        LEFT JOIN barber br ON b.barber_id = br.id
        WHERE b.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Booking not found");
}

$data = $result->fetch_assoc();

// ============================================================
// HELPER: CLEAN PACKAGE NAME (remove parentheses and dashes)
// ============================================================
function cleanPackageName($name) {
    if (empty($name)) return 'Custom';
    // Remove everything from ( or - onwards
    $name = preg_replace('/\s*[\(\-].*$/', '', $name);
    return trim($name);
}

// ============================================================
// PDF RECEIPT CLASS
// ============================================================
class HostelReceipt extends FPDF
{
    function __construct()
    {
        parent::__construct('P', 'mm', array(80, 150));
    }

    function Header()
    {
        // Shop Name - bold effect with uppercase + larger size
        $this->SetFont('courier', '', 11);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 5, 'HOSTEL BARBERSHOP', 0, 1, 'C');

        // Shop Info
        $this->SetFont('courier', '', 8);
        $this->Cell(0, 4, 'Jl. Hostel Streets No.14', 0, 1, 'C');
        $this->Cell(0, 4, 'Jakarta Pusat', 0, 1, 'C');
        $this->Cell(0, 4, 'Telp: (021) 123-456-789', 0, 1, 'C');

        // Separator
        $this->SetDrawColor(100, 100, 100);
        $this->Line(5, $this->GetY(), 75, $this->GetY());
        $this->Ln(2);
    }

    function Footer()
    {
        $this->SetY(-18);
        $this->SetFont('courier', '', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 4, '------------------------------', 0, 1, 'C');
        $this->Cell(0, 4, 'Thank you for your visit!', 0, 1, 'C');
        $this->Cell(0, 4, 'Look Good, Feel Good, Be Hostel', 0, 1, 'C');
    }

    function Separator()
    {
        $this->SetDrawColor(150, 150, 150);
        $this->SetLineWidth(0.2);
        $this->Line(5, $this->GetY(), 75, $this->GetY());
        $this->Ln(2);
    }

    function SectionTitle($title)
    {
        $this->Ln(1);
        $this->SetFont('courier', '', 9);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 4, strtoupper($title), 0, 1, 'C');
        $this->Ln(1);
    }

    function InfoRow($label, $value)
    {
        $this->SetFont('courier', '', 8);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(20, 4, $label, 0, 0, 'L');
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 4, ': ' . $value, 0, 1, 'L');
    }

    function PriceRow($label, $value, $isTotal = false)
    {
        if ($isTotal) {
            $this->SetFont('courier', '', 9);
        } else {
            $this->SetFont('courier', '', 8);
        }
        $this->SetTextColor(0, 0, 0);

        $labelWidth = $this->GetStringWidth($label);
        $valueWidth = $this->GetStringWidth($value);
        $spaceWidth = 70 - $labelWidth - $valueWidth;

        $this->Cell($labelWidth, 4, $label, 0, 0, 'L');
        if ($spaceWidth > 0) {
            $this->Cell($spaceWidth, 4, str_repeat('.', floor($spaceWidth / 2)), 0, 0, 'L');
        }
        $this->Cell(0, 4, $value, 0, 1, 'R');
    }
}

// ============================================================
// GENERATE PDF
// ============================================================
$pdf = new HostelReceipt();
$pdf->SetAutoPageBreak(false);
$pdf->SetMargins(5, 5, 5);
$pdf->AddPage();

// === RECEIPT HEADER ===
$pdf->SetFont('courier', '', 10);
$pdf->Cell(0, 4, '*** RECEIPT ***', 0, 1, 'C');
$pdf->Separator();

// Receipt Info
$pdf->InfoRow('No', 'HSTL-' . str_pad($data['booking_id'], 5, '0', STR_PAD_LEFT));
$pdf->InfoRow('Date', date('d/m/Y H:i', strtotime($data['booked_at'])));
$pdf->InfoRow('Status', strtoupper($data['status']));
$pdf->Separator();

// === BOOKING DETAILS ===
$pdf->SectionTitle('BOOKING');
$pdf->InfoRow('ID', '#' . $data['booking_id']);
$pdf->InfoRow('Date', date('d/m/Y', strtotime($data['tanggal'])));
$pdf->InfoRow('Time', $data['jam'] . ' WIB');
$pdf->Separator();

// === CUSTOMER DETAILS ===
$pdf->SectionTitle('CUSTOMER');
$pdf->InfoRow('Name', $data['customer_name'] ?? 'Guest');
$pdf->InfoRow('Email', $data['customer_email'] ?? '-');
$pdf->InfoRow('Phone', $data['customer_phone'] ?? '-');
$pdf->Separator();

// === SERVICE DETAILS ===
$pdf->SectionTitle('SERVICE');
$cleanPackage = cleanPackageName($data['package_name']);
$pdf->InfoRow('Package', $cleanPackage);
$pdf->InfoRow('Barber', $data['barber_name'] ?? 'Any');
$pdf->Separator();

// === PAYMENT ===
$pdf->SectionTitle('PAYMENT');
$price = $data['package_price'] ?? 0;
$pdf->PriceRow($cleanPackage, 'Rp ' . number_format($price, 0, ',', '.'));
$pdf->Separator();
$pdf->PriceRow('TOTAL', 'Rp ' . number_format($price, 0, ',', '.'), true);
$pdf->Separator();

// ============================================================
// OUTPUT PDF
// ============================================================
$filename = 'Hostel_Struk_' . str_pad($data['booking_id'], 5, '0', STR_PAD_LEFT) . '.pdf';

while (ob_get_level()) {
    ob_end_clean();
}

$pdf->Output('D', $filename);

$stmt->close();
$conn->close();
?>