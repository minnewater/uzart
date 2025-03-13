<?php
//header("Content-Type: application/json");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || !isset($_SESSION['dashboardid'])) {
    header("Location: /uzart");
    exit();
}

include_once(__DIR__ . "/../../include/_common.php");
// tFPDF 라이브러리 호출
require "../../lib/tfpdf.php";
$remote_ip = $_SERVER['REMOTE_ADDR']; // 접속 IP

// 글로벌 설정 파일
$configFile = $_SERVER['DOCUMENT_ROOT'] . '/uzart/config/uzart.conf';
$conf = file_exists($configFile) ? parse_ini_file($configFile) : [];
$companyName = isset($conf['COMPANY_NAME']) ? $conf['COMPANY_NAME'] : 'WELLCLOUD';


// 사용자 정보
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, position, office_phone, mobile_phone, email FROM users where id = :id");
$stmt->bindParam(":id", $userId);
$stmt->execute();
$users = $stmt->fetch(PDO::FETCH_ASSOC);
$userId    = $_SESSION['username'] ?? "admin";
//$userId    = $users['username']	?? "admin";
$userName  = $users['name']         ?? "관리자";
$userRole  = $users['position']     ?? "관리자";
$userOffice = $users['office_phone'] ?? "0000";
$userPhone = $users['mobile_phone'] ?? "000-0000-0000";
$userMail  = $users['email']        ?? "support@wellcloud.co.kr";


// 단일/다중 선택을 통합 처리: "ids"가 있으면 여러 개, 없으면 "id" 하나를 배열로 만듦
if (isset($_GET['ids'])) {
    $idArr = array_filter(array_map('intval', explode(',', $_GET['ids'])));
} elseif (isset($_GET['id'])) {
    $idArr = [intval($_GET['id'])];
} else {
    echo json_encode(['success' => false, 'message' => '보고서 ID가 지정되지 않았습니다.']);
    exit();
}
if (empty($idArr)) {
    echo json_encode(['success' => false, 'message' => '유효한 보고서 ID가 없습니다.']);
    exit();
}

// 데이터 조회: 선택한 ID들에 대해
$placeholders = implode(',', array_fill(0, count($idArr), '?'));
$stmt = $conn->prepare("SELECT * FROM uz_srvdata WHERE id IN ($placeholders)");
$stmt->execute($idArr);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$reports || count($reports) === 0) {
    echo json_encode(['success' => false, 'message' => '선택한 보고서 데이터를 찾을 수 없습니다.']);
    exit();
}
$client = $reports[0]['client'];
//$server = $reports[0]['server_name'];


// 보고일자(생성일) 범위 계산: 각 보고서의 created_at에서 최소, 최대 날짜 구하기
$dates = array_map(function($r){ return strtotime($r['created_at']); }, $reports);
$minDate = date("Y-m-d", min($dates));
$maxDate = date("Y-m-d", max($dates));
//$reportDate = ($minDate === $maxDate) ? $minDate : "$minDate ~ $maxDate";
$reportDate = date("Y-m-d");

// 추가 입력값: 시스템 로그, 서비스 로그, 기술지원 및 특이사항 (팝업에서 입력된 값, 없으면 빈 문자열)
$netErr   = $_GET['net_err']   ?? "이상 없음";
$tmpLog   = $_GET['tmp_log']   ?? "이상 없음";
$webLog   = $_GET['web_log']   ?? "해당사항 없음";
$wasLog   = $_GET['was_log']   ?? "해당사함 없음";
$dbLog   = $_GET['db_log']   ?? "해당사항 없음";
$sysLog   = $_GET['sys_log']   ?? "이상 없음";
$comments = $_GET['comments']  ?? "- 특이사항 없음";

$logoPath  = '../assets/company.jpg';

unset($report);

// PDF 생성을 위한 tFPDF 사용
$pdf = new tFPDF('P', 'mm', array(200, 300));
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 10);

// 폰트 설정 (NanumGothic 폰트를 사전에 등록해두었음)
$pdf->AddFont('NanumGothic','','Nanum/NanumGothic.ttf',true);
$pdf->AddFont('NanumGothic','B','Nanum/NanumGothicBold.ttf',true);

function printTitle($pdf, $text) {
	$pdf->SetFont('NanumGothic','B',12);
	$pdf->Cell(0,8,$text,0,1);
}

function printContent($pdf, $text) {
	$pdf->SetFont('NanumGothic','',10);
	$pdf->Cell(0,8,$text,0,1);
	$pdf->Ln(2);
}

function printContent2($pdf, $text) {
	$pdf->SetFont('NanumGothic','',10);
	$pdf->Cell(0,8,$text,0,1);
}

foreach ($reports as $index => $rep) {
    $number = $index + 1;
    $pdf->AddPage();
    
    $pdf->SetFont('NanumGothic','B',14);
    $pdf->Cell(0,10, $number . ". " . $rep['sc_hostname'] . " 서버 점검",0,1);
    $pdf->Ln(5);

    // 현재 좌표 저장
    // 행 1: OS
    printTitle($pdf, 'OS');
    printContent($pdf, $rep['sc_os']);

    // 행 2: Hostname
    printTitle($pdf, 'Kernel');
    printContent($pdf, $rep['sc_kernel']);

    // 행 3: CPU
    printTitle($pdf, 'CPU');
    printContent($pdf, $rep['sc_cpuinfo']);

    // 행 4: Memory
    printTitle($pdf, 'MEMORY');
    printContent($pdf, $rep['sc_memdiv'] . "GB");

    // 행 5: Network
    printTitle($pdf, 'Network');
    $sc_srvip = json_decode($rep['sc_srvip'], true);
    foreach ($sc_srvip as $ip) {
        printContent2($pdf, " Interface: " .  $ip['sc_srvipId'] . " / IP: " . $ip['sc_srvip']);
    }
    $pdf->Ln(2);

    // 행 6: Hostname
    printTitle($pdf, 'Hostname');
    printContent($pdf, $rep['sc_hostname']);

    $pdf->AddPage();

    // 행 1: Uptime
    printTitle($pdf, 'Uptime');
    printContent($pdf, $rep['sc_uptime']);

    // 행 2: LoadAverage
    printTitle($pdf, 'Load Average (15min)');
    printContent($pdf, $rep['sc_avg']);

    // 행 3: Process Count
    printTitle($pdf, 'Process Count');
    printContent($pdf, $rep['sc_pscnt']);

    // 행 3: Cpu Usage
    printTitle($pdf, 'Cpu Usage');
    printContent($pdf, $rep['sc_cpuusage'] . "%");

}

// "기술지원 및 특이사항" 영역 – GET 파라미터 comments
$number = $number + 1;
$pdf->SetFont('NanumGothic','B',14);
$pdf->Cell(0,10,$number . ". 기술지원 및 특이사항",0,1);
$pdf->SetFont('NanumGothic','',12);
$pdf->MultiCell(0,10, $comments, 1, 'L');
$pdf->Ln(5);
$pdf->AddPage();

// 파일명 생성: {Client}_{Server_Name}_{생성년-월-일}.pdf
$clientName = preg_replace("/\s+/", "_", $client);
$fileName = "{$clientName}_{$reportDate}.pdf";
$filePath = "/data/report/uzart/www/tmp/" . "{$clientName}_{$reportDate}.pdf";
    
if ($_GET['download'] == '1') {
    header('Content-Type: application/pdf');
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    $pdf->Output("D", $fileName);
    log_message("INFO", "$userId is download $fileName", "$client", "", $remote_ip, $conn); // 로깅
} elseif ($_GET['download'] == '2') {
    $pdf->Output($filePath, "F");
    header('Content-Type: application/pdf');
    header("Content-Disposition: inline; filename=\"" . basename($filePath) . "\"");
    header("Content-Length: " . filesize($filePath));
    readfile($filePath);
    log_message("INFO", "$userId is view $fileName", "$client", "", $remote_ip, $conn); // 로깅
} elseif ($_GET['download'] == '3') {
    if (file_exists($filePath)) {
        unlink($filePath); // 파일 삭제
        die(json_encode(["success" => true, "message" => "PDF 파일이 삭제되었습니다."]));
    } else {
        die(json_encode(["error" => "파일이 존재하지 않습니다."]));
    }
}

?>
