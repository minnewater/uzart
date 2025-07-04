<?php
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

// 점검 여부 조회
$checkStmt = $conn->prepare("SELECT id, check_status FROM uz_srvdata_check WHERE id IN (" . implode(',', array_fill(0, count($idArr), '?')) . ")");
$checkStmt->execute($idArr);
$checkStatuses = $checkStmt->fetchAll(PDO::FETCH_KEY_PAIR);
//error_log("Check statuses: " . print_r($checkStatuses, true));

foreach ($idArr as $id) {
    // PostgreSQL boolean은 't' 또는 'f' 문자열로 반환될 수 있으므로
    $status = $checkStatuses[$id] ?? null;
    $statusBool = filter_var($status, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($statusBool !== true) {
        echo json_encode([
            'success' => false,
            'message' => "ID $id 보고서는 점검필요 상태로 조회할 수 없습니다."
        ]);
        exit();
    }
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


// 사용자 정보
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, position, office_phone, mobile_phone, email FROM users where id = ( SELECT user_id FROM uz_srvdata_check WHERE id = :id )");
$stmt->bindParam(":id", $reports[0]['id']);
$stmt->execute();
$users = $stmt->fetch(PDO::FETCH_ASSOC);
$userId    = $_SESSION['username'] ?? "admin";
//$userId    = $users['username']       ?? "admin";
$userName  = $users['name']         ?? "관리자";
$userRole  = $users['position']     ?? "관리자";
$userOffice = $users['office_phone'] ?? "0000";
$userPhone = $users['mobile_phone'] ?? "000-0000-0000";
$userMail  = $users['email']        ?? "support@wellcloud.co.kr";


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
$pdf->AddPage();

// 폰트 설정 (NanumGothic 폰트를 사전에 등록해두었음)
$pdf->AddFont('NanumGothic','', 'Nanum/NanumGothic.ttf', true);
$pdf->AddFont('NanumGothic','B', 'Nanum/NanumGothicBold.ttf', true);

// 표지 페이지
$pdf->Image($logoPath, $pdf->GetPageWidth() - 40, 30, 30);
$pdf->SetY(50);
$pdf->SetFont('NanumGothic','B',40);
$pdf->Cell(0, 20,"월간 점검 보고서",1,1,"C");
$pdf->Ln(120);
$pdf->SetFont('NanumGothic','',14);
$pdf->Cell(40, 10,"고객명", 1, 0, 'C');
$pdf->Cell(0, 10, $client, 1, 1, 'C');
$pdf->Cell(40, 10, "사업명", 1, 0, 'C');
$pdf->Cell(0, 10, date("Y") . "년 " . date("m") . "월 점검 보고", 1, 1, 'C');
$pdf->Cell(40, 10,"보고일", 1, 0, 'C');
$pdf->Cell(0, 10, date("Y-m-d"), 1, 1, 'C');
$pdf->Ln(15);

// 제출자 정보
$startX = 10;
$boxWidth = $pdf->GetPageWidth() - 20;
$boxHeight = 6;
$pdf->SetFillColor(220,220,220);
$pdf->Rect($startX, $pdf->GetY(), $boxWidth, $boxHeight, "F");
$pdf->Ln(12);

$pdf->SetFont('NanumGothic','',10);
$submitInfo = "제출자: ㈜웰클라우드 " . $userName . " " . $userRole . "\n" .
              "부산광역시 남구 신선로 365, 한미르관 1410-C호 / 전화: (070)7931-$userOffice\n" .
              "휴대전화: " . $userPhone . "   전자메일: " . $userMail;
$pdf->MultiCell(0, 5, $submitInfo, 0);

// 각 보고서(서버별 정보)를 순차적으로 출력 – 각 서버별 블록을 출력
// 테이블의 각 블록은 3열짜리 표: "점검 사항", "점검 결과", "비고"
// 각 블록 상단에 서버명을 출력
$categoryWidth = 25; // 첫 열 폭 (분류)
$labelWidth = 35; // 두번째 열 폭 (점검 항목)
$dataWidth = 120; // 결과 열 폭 (점검 결과)

foreach ($reports as $index => $rep) {
    $number = $index + 1;
    $pdf->AddPage();

    $pdf->SetFont('NanumGothic','B',14);
    $pdf->Cell(0,10, $number . ". " . $rep['sc_hostname'] . " 서버 점검내역",0,1);
    $pdf->Ln(5);

    $pdf->SetFont('NanumGothic','B',12);
    // 행 0: 항목별 설명
    $pdf->Cell($categoryWidth,10, "분류",1,0,'C');
    $pdf->Cell($labelWidth,10, "점검 항목",1,0,'C');
    $pdf->Cell($dataWidth,10, "점검 결과",1,1,'C');
    $pdf->SetFont('NanumGothic','',10);
    // 행 1: LoadAvg
    $pdf->Cell($categoryWidth,60, "자원",1);
    // 현재 좌표 저장
    $startX = $pdf->GetX();
    $pdf->Cell($labelWidth,10,"부하 LoadAvg",1);
    $pdf->Cell($dataWidth,10, $rep['sc_avg'],1,1);
    // 행 2: Uptime
    $pdf->SetX($startX);
    $pdf->Cell($labelWidth,10,"Uptime",1);
    $pdf->Cell($dataWidth,10, $rep['sc_uptime'],1,1);
    // 행 3: CPU 사용량 (필드 sc_cpuusage 없으면 sc_cpuinfo 사용)
    $pdf->SetX($startX);
    $pdf->Cell($labelWidth,10,"CPU 사용량",1);
    $pdf->Cell($dataWidth,10, $rep['sc_cpuusage'] . "%",1,1);
    // 행 4: Memory 사용량 (예: sc_memUsed / sc_memTotal, 없으면 sc_memusage)
    $pdf->SetX($startX);
    $pdf->Cell($labelWidth,10,"Memory 사용량",1);
    $pdf->Cell($dataWidth,10, $rep['sc_memused'] . "G / " . $rep['sc_memtotal'] . "G (" . $rep['sc_memusage'] . "%)",1,1);
    // 행 5: SWAP Memory 사용량
    $pdf->SetX($startX);
    $pdf->Cell($labelWidth,10,"SWAP 사용량",1);
    $pdf->Cell($dataWidth,10, $rep['sc_swapused'] . "G / " . $rep['sc_swaptotal'] . "G (" . $rep['sc_swapusage'] . "%)",1,1);
    // 행 6: 총 Process 수
    $pdf->SetX($startX);
    $pdf->Cell($labelWidth,10,"총 Process",1);
    $pdf->Cell($dataWidth,10, $rep['sc_pscnt'],1,1);
    // 행 7: Network 상태
    $pdf->Cell($categoryWidth,20, "Network",1);
    // 현재 좌표 저장
    $startX = $pdf->GetX();
    $startY = $pdf->GetY();
    $pdf->Cell($labelWidth,10,"Packet collisions",1);
    $pdf->Cell($dataWidth,10, $netErr ,1,1);
    $pdf->SetX($startX);
    $pdf->Cell($labelWidth,10,"Packet error",1);
    $pdf->Cell($dataWidth,10, $netErr ,1,1);
    // 행 8: Disk
    $sc_disk = json_decode($rep['sc_disk'], true);
    if (is_array($sc_disk)) {
        // 배열을 줄바꿈 문자로 결합
        $diskText = implode("\n", $sc_disk);
        // 줄바꿈으로 나눈 배열로 몇 줄인지 계산 (각 줄 높이를 10으로 가정)
        $lines = explode("\n", $diskText);
        $nbLines = count($lines);
        $rowHeight = 8 * $nbLines;  // 해당 셀의 높이

        // 현재 좌표 저장
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        // 첫 번째 셀: "Disk" 카테고리 (고정 폭, 계산된 높이 사용)
        $pdf->SetFont('NanumGothic','',10);
        $pdf->Cell($categoryWidth, $rowHeight, "Disk", 1);

        // 두번째 번째 셀: "사용량" 라벨 (고정 폭, 계산된 높이 사용)
        $pdf->Cell($labelWidth, $rowHeight, "사용량", 1);

        // 마지막 셀: MultiCell로 디스크 정보를 출력 (자동 줄바꿈)
        $pdf->SetFont('NanumGothic','',8);
        $pdf->SetXY($x + $categoryWidth + $labelWidth, $y);  // X좌표를 라벨 다음으로 이동
        $pdf->MultiCell($dataWidth, 8, $diskText, 1,1);

        // MultiCell은 자동으로 줄바꿈하여 Y가 변경되었으므로, 다시 시작 Y로 설정
        $pdf->SetXY($x + $categoryWidth + $labelWidth + $dataWidth, $y);
    }
    $pdf->SetY($y + $rowHeight);
    // 행 10: WEB 서비스 로그
    $pdf->SetFont('NanumGothic','',10);
    $pdf->Cell($categoryWidth,40, "로그",1);
    $startX = $pdf->GetX();
    $startY = $pdf->GetY();
    $pdf->Cell($labelWidth,10,"WEB 서비스",1);
    $pdf->Cell($dataWidth,10, $webLog,1,1);
    // 행 10: WAS 서비스 로그
    $pdf->SetX($startX);
    $pdf->Cell($labelWidth,10,"WAS 서비스",1);
    $pdf->Cell($dataWidth,10, $wasLog,1,1);
    // 행 10: DB 서비스 로그
    $pdf->SetX($startX);
    $pdf->Cell($labelWidth,10,"DB 서비스",1);
    $pdf->Cell($dataWidth,10, $dbLog,1,1);
    // 행 10: 시스템 로그 – 보고서 버튼으로 팝업 시 입력받은 값 (여기서는 GET 파라미터 sys_log, 없으면 빈칸)
    $pdf->SetX($startX);
    $pdf->Cell($labelWidth,10,"시스템 로그",1);
    $pdf->Cell($dataWidth,10, $sysLog,1,1);
    // 행 9: tmp 폴더 확인
    $pdf->Cell($categoryWidth,10, "기타",1);
    $pdf->Cell($labelWidth,10,"TMP 폴더",1);
    $pdf->Cell($dataWidth,10, $tmpLog,1,1);
    $pdf->Ln(5);
}

// "기술지원 및 특이사항" 영역 – GET 파라미터 comments
$number = $number + 1;
$pdf->SetFont('NanumGothic','B',14);
$pdf->Cell(0,10,$number . ". 기술지원 및 특이사항",0,1);
$pdf->SetFont('NanumGothic','',12);
$pdf->MultiCell(0,10, $comments, 1, 'L');
$pdf->Ln(5);

// 파일명 생성: {Client}_{Server_Name}_{생성년-월-일}.pdf
$clientName = preg_replace("/\s+/", "_", $client);
$fileName = "{$clientName}_{$reportDate}.pdf";

// PDF 저장 디렉터리 (없으면 생성)
$outputDir = realpath(__DIR__ . '/../tmp');
if ($outputDir === false) {
    $outputDir = __DIR__ . '/../tmp';
}
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}
$filePath = $outputDir . '/' . $fileName;

// 디버깅 로그
error_log("Raw client: " . bin2hex($client));
error_log("Converted clientName: " . $clientName);
error_log("fileName: " . $fileName);

if ($_GET['download'] == '1') {
    header('Content-Type: application/pdf');
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    $pdf->Output("D", $fileName);
    log_message("INFO", "$userId is download $fileName", "$client", "", $remote_ip, $conn);
    exit();
} elseif ($_GET['download'] == '2') {
    $pdf->Output('F', $filePath);
    if (!file_exists($filePath)) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'PDF 생성 실패']);
        exit();
    }
    header('Content-Type: application/pdf');
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    header("Content-Length: " . filesize($filePath));
    readfile($filePath);
    log_message("INFO", "$userId is view $fileName", "$client", "", $remote_ip, $conn);
    exit();
} elseif ($_GET['download'] == '3') {
    if (file_exists($filePath)) {
        unlink($filePath); // 파일 삭제
        die(json_encode(["success" => true, "message" => "PDF 파일이 삭제되었습니다."]));
    } else {
        die(json_encode(["error" => "파일이 존재하지 않습니다."]));
    }
}

