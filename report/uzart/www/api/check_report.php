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

/* ── CSRF (POST 전용) ──────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!verify_csrf_token($csrf)) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Invalid CSRF token"]);
        exit();
    }
}

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
$pdf->AddFont('NanumGothic','', 'unifont/Nanum/NanumGothic.ttf', true);
$pdf->AddFont('NanumGothic','B', 'unifont/Nanum/NanumGothicBold.ttf', true);

//$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Linux Server Audit');
$pdf->SetTitle('Linux Server Monthly Report');
$pdf->SetSubject('Server Inspection Report');
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();
$pdf->SetFont('NanumGothic', '', 10);

// 제목 출력
$pdf->SetFont('NanumGothic', 'B', 14);
$pdf->Cell(0, 10, '서버 점검 리포트', 0, 1, 'C');
$pdf->Ln(5);
$pdf->SetFont('NanumGothic', '', 10);

foreach ($reports as $row) {
    $pdf->SetFont('NanumGothic', 'B', 12);
    $pdf->Cell(0, 10, "서버 정보", 0, 1, 'L');
    $pdf->SetFont('NanumGothic', '', 10);

    $pdf->MultiCell(0, 6, "서버명: " . $row['server_name'], 0, 'L');

    // 서버 IP 주소 디코드 및 출력
    $pdf->MultiCell(0, 6, "서버 IP:", 0, 'L');
    $serverIPs = json_decode($row['sc_srvip'], true);
    foreach ($serverIPs as $ipInfo) {
        $pdf->MultiCell(0, 6, "{$ipInfo['sc_srvipId']} - {$ipInfo['sc_srvip']}", 0, 'L');
    }
    $pdf->Ln(5);

    $pdf->MultiCell(0, 6, "운영체제: " . $row['sc_os'], 0, 'L');
    $pdf->MultiCell(0, 6, "커널 버전: " . $row['sc_kernel'], 0, 'L');
    $pdf->Ln(5);

    $pdf->SetFont('NanumGothic', 'B', 12);
    $pdf->Cell(0, 10, "네트워크 상태", 0, 1, 'L');
    $pdf->SetFont('NanumGothic', '', 10);

    // 네트워크 인터페이스 디코드 및 출력
    $pdf->MultiCell(0, 6, "네트워크 인터페이스:", 0, 'L');
    $networkInterfaces = json_decode($row['sc_net'], true);
    foreach ($networkInterfaces as $netInfo) {
        $pdf->MultiCell(0, 6, "{$netInfo['sc_netId']} - {$netInfo['sc_netRe']}", 0, 'L');
    }
    $pdf->Ln(5);

    $pdf->SetFont('NanumGothic', 'B', 12);
    $pdf->Cell(0, 10, "시스템 리소스", 0, 1, 'L');
    $pdf->SetFont('NanumGothic', '', 10);

    $pdf->MultiCell(0, 6, "로드 평균 (Load Average): " . $row['sc_avg'], 0, 'L');
    $pdf->MultiCell(0, 6, "Uptime: " . $row['sc_uptime'], 0, 'L');
    $pdf->MultiCell(0, 6, "CPU 정보: " . $row['sc_cpuinfo'], 0, 'L');
    $pdf->MultiCell(0, 6, "CPU 코어 수: " . $row['sc_cpucores'], 0, 'L');
    $pdf->MultiCell(0, 6, "CPU 사용률: " . $row['sc_cpuusage'] . " %", 0, 'L');
    $pdf->MultiCell(0, 6, "메모리 사용량: " . $row['sc_memusage'] . " %", 0, 'L');
    $pdf->Ln(5);

    // 웹 서버 정보 (기동 중인 경우만)
    if (!empty($row['sc_apache'])) {
        $pdf->SetFont('NanumGothic', 'B', 12);
        $pdf->Cell(0, 10, "Apache 웹 서버", 0, 1, 'L');
        $pdf->SetFont('NanumGothic', '', 10);
        $pdf->MultiCell(0, 6, "Apache 버전: " . $row['sc_apache'], 0, 'L');
        $pdf->Ln(5);
    }

    // WAS (Tomcat) 정보 (기동 중인 경우만)
    if (!empty($row['sc_tomcat'])) {
        $pdf->SetFont('NanumGothic', 'B', 12);
        $pdf->Cell(0, 10, "Tomcat WAS 서버", 0, 1, 'L');
        $pdf->SetFont('NanumGothic', '', 10);
        $pdf->MultiCell(0, 6, "Tomcat 정보:", 0, 'L');
        $tomcats = json_decode($row['sc_tomcat'], true);
        foreach ($tomcats as $tomcat) {
            $pdf->MultiCell(0, 6, "버전: {$tomcat['sc_tomVer']}, AJP 포트: {$tomcat['sc_tomAjpPort']}, HTTP 포트: {$tomcat['sc_tomHttpPort']}", 0, 'L');
        }
        $pdf->Ln(5);
    }

    // DB 정보 (기동 중인 경우만)
    if (!empty($row['sc_db'])) {
        $pdf->SetFont('NanumGothic', 'B', 12);
        $pdf->Cell(0, 10, "데이터베이스 (DB) 서버", 0, 1, 'L');
        $pdf->SetFont('NanumGothic', '', 10);
        $pdf->MultiCell(0, 6, "DB 정보:", 0, 'L');
        $databases = json_decode($row['sc_db'], true);
        foreach ($databases as $db) {
            $pdf->MultiCell(0, 6, "버전: {$db['sc_dbVer']}, 인스턴스 타입: {$db['sc_dbInstType']}, 데이터 경로: {$db['sc_dbData']}", 0, 'L');
        }
        $pdf->Ln(5);
    }

    $pdf->SetFont('NanumGothic', 'B', 12);
    $pdf->Cell(0, 10, "로그 및 보안", 0, 1, 'L');
    $pdf->SetFont('NanumGothic', '', 10);

    $pdf->MultiCell(0, 6, "시스템 로그 (messages): " . implode("\n", json_decode($row['sc_meslog'], true)), 0, 'L');
    $pdf->Ln(5);
    $pdf->MultiCell(0, 6, "보안 로그 (secure): " . implode("\n", json_decode($row['sc_seclog'], true)), 0, 'L');
    $pdf->Ln(5);
    $pdf->MultiCell(0, 6, "dmesg 로그: " . implode("\n", json_decode($row['sc_dmeslog'], true)), 0, 'L');
    $pdf->Ln(10);
}

$pdf->AddPage();

// 파일명 생성: {Client}_{Server_Name}_{생성년-월-일}.pdf
$clientName = preg_replace("/\s+/", "_", $client);
$fileName = "{$clientName}_{$reportDate}.pdf";
$filePath = "/data/report/uzart/www/tmp/" . "{$clientName}_{$reportDate}.pdf";

if ($_GET['download'] == '1') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename=' . iconv('UTF-8', 'CP949//IGNORE', $fileName));
    //header("Content-Disposition: attachment; filename=\"$fileName\"");
    $pdf->Output("D", $fileName);
    log_message("INFO", "$userId is download $fileName", "$client", "", $remote_ip, $conn); // 로깅
} elseif ($_GET['download'] == '2') {
    $pdf->Output($filePath, "F");
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename=' . iconv('UTF-8', 'CP949//IGNORE', $fileName));
    //header("Content-Disposition: inline; filename=\"" . basename($filePath) . "\"");
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
