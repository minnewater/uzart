<?php

//// 디버깅 로그 파일
//file_put_contents('/tmp/debug.log', json_encode([
//    'timestamp' => date('Y-m-d H:i:s'),
//    'method' => $_SERVER['REQUEST_METHOD'],
//    'uri' => $_SERVER['REQUEST_URI'],
//    'headers' => getallheaders(),
//    'authorization_header' => $_SERVER['AUTH_HEADER'] ?? null,
//    'api_key' => str_replace("Bearer ", "", $_SERVER['AUTH_HEADER'] ?? '')
//    //    'body' => file_get_contents('php://input')
//], JSON_PRETTY_PRINT), FILE_APPEND);

// 공통 설정 파일 포함
include_once(__DIR__ . "/_common.php");

// 데이터베이스 접속 설정 파일 로드
if (!file_exists($config_file)) {
    http_response_code(500);
    //echo json_encode(["error" => "Database configuration file not found"]);
    exit;
}
include_once($config_file);

/**
 * 로깅 함수
 * @param string $level 로그 수준 (INFO, ERROR 등)
 * @param string $message 로그 메시지
 * @param string $hn 요청한 서버의 Hostname
 * @param string $ip 요청한 서버의 IP 주소
 */
//function log_message($level, $message, $client_name, $hn, $ip, $conn) {
//    // 날짜 별 로그 파일
//    $log_table = 'uz_auditlog_' . date('Y_m_d'); //  테이블 이름을 YYYY-MM-DD.log 형식으로 설정
//    $check_log_table_query = "
//	SELECT EXISTS (
//	  SELECT FROM information_schema.tables
//	  WHERE table_name = :table_name
//	)
//    ";
//    $chkLog_stmt = $conn->prepare($check_log_table_query);
//    $chkLog_stmt->bindValue(':table_name', $log_table);
//    $chkLog_stmt->execute();
//    $log_table_exists = $chkLog_stmt->fetchColumn();
//
//    // 테이블 없으면 생성
//    if (!$log_table_exists) {
//	$create_log_table_query = "
//	  CREATE TABLE \"$log_table\" (
//	    id SERIAL PRIMARY KEY,
//	    log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//	    level TEXT NOT NULL,
//	    message TEXT NOT NULL,
//	    client TEXT,
//	    server_name TEXT NOT NULL,
//	    client_ip TEXT NOT NULL
//	  )
//	";
//	$conn->exec($create_log_table_query);
//    }
//
//    $inst_log_query = "
//	INSERT INTO \"$log_table\" (level, message, client, server_name, client_ip)
//	VALUES (:level, :message, :client_name, :server_name, :client_ip)
//    ";
//    $inst_log_stmt = $conn->prepare($inst_log_query);
//    $inst_log_stmt->bindValue(':level', $level, PDO::PARAM_STR);
//    $inst_log_stmt->bindValue(':message', $message, PDO::PARAM_STR);
//    $inst_log_stmt->bindValue(':client_name', $client_name, PDO::PARAM_STR);
//    $inst_log_stmt->bindValue(':server_name', $hn, PDO::PARAM_STR);
//    $inst_log_stmt->bindValue(':client_ip', $ip, PDO::PARAM_STR);
//    $inst_log_stmt->execute();
//}

// 요청 메서드 확인
$request_method = $_SERVER['REQUEST_METHOD'];
if ($request_method === 'POST') {

    // 입력 데이터 읽기
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    // JSON 형식 확인 및 필수 데이터 존재 여부 확인
    if (json_last_error() !== JSON_ERROR_NONE || $data === null) {
        http_response_code(400);
        error_log("Invalid JSON data");
        exit;
    }

    // Client IP & Hostname
    $client_ip = $_SERVER['REMOTE_ADDR']; // 요청한 서버의 IP 주소
    $hostname = $_SERVER['HTTP_X_HOSTNAME'] ?? 'unknown'; // 호스트 이름 가져오기 or unknown

    try {
        // 데이터베이스 연결
        $conn = new PDO(
            "pgsql:host=" . $DB_HOST . ";dbname=" . $DB_NAME,
            $DB_USER,
            $DB_PASS
        );
        //$conn = new PDO("pgsql:host=$DB_HOST;dbname=$DB_NAME", $DB_USER, $DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Database connection failed: " . $e->getMessage());
        exit;
    }

    // API 요청 처리
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['AUTH_HEADER'] ?? ''));
    $api_key = str_replace('Bearer ', '', $auth_header);
    if (!$api_key) {
        http_response_code(401);
        log_message("ERROR", "Missing or invalid API Key in Authorization header", 'unknown', $hostname, $client_ip, $conn);
        exit;
    }

    // API 키 인증
    $chkAPI_stmt = $conn->prepare("SELECT server_name, client FROM api_keys WHERE api_key = :api_key");
    $chkAPI_stmt->bindParam(':api_key', $api_key);
    $chkAPI_stmt->execute();
    $result = $chkAPI_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        http_response_code(401);
        log_message("ERROR", "Invalid API Key: $api_key", 'unknown', $hostname, $client_ip, $conn);
        exit;
    }

    $server_name = $result['server_name'];
    $client = $result['client'];

    // 허용된 컬럼 화이트리스트
    $allowed_columns = [
        'sc_avg','sc_memUsage','sc_raid','sc_meslog','sc_dmeslog','sc_seclog',
        'sc_devs','sc_last','sc_tmps','sc_vtmps','sc_disk','sc_srvip','sc_net',
        'sc_user','sc_apache','sc_tomcat','sc_db','sc_timevers','sc_uptime',
        'sc_cpucores','sc_cpuinfo','sc_cpunum','sc_hostname','sc_idcheck',
        'sc_ipmichk','sc_kernel','sc_memdiv','sc_memnum','sc_memFree',
        'sc_memTotal','sc_memUsed','sc_swapFree','sc_swapTotal','sc_swapUsage',
        'sc_swapUsed','sc_vmcpu','sc_os','sc_pscnt','sc_time','sc_timeconf',
        'sc_timeexe','sc_timepool'
    ];

    $unexpected = array_diff(array_keys($data), $allowed_columns);
    if (!empty($unexpected)) {
        http_response_code(400);
        log_message("ERROR", "Unexpected columns: " . implode(', ', $unexpected), $client, $hostname, $client_ip, $conn);
        exit;
    }

    $filtered_data = array_intersect_key($data, array_flip($allowed_columns));

    // JSON 데이터에서 허용된 컬럼과 값을 동적으로 생성
    $columns = implode(", ", array_keys($filtered_data));
    $placeholders = ":" . implode(", :", array_keys($filtered_data));

    $inst_stmt = $conn->prepare("INSERT INTO uz_srvdata (client, server_name, $columns) VALUES (:client, :server_name, $placeholders)");

    // 데이터 바인딩
    foreach ($filtered_data as $key => $value) {
        if (is_numeric($value)) {
          $value = is_float($value + 0) ? (float)$value : (int)$value;
        }
        if (is_array($value) || is_object($value)) {
          $value = json_encode($value);
        }
        $inst_stmt->bindValue(":$key", $value);
    }

    $inst_stmt->bindValue(':client', $client);
    $inst_stmt->bindValue(':server_name', $server_name);

    // 쿼리 실행
    if ($inst_stmt->execute()) {
	// 마지막 insert id 가져오기
	$last_insert_id = $conn->lastInsertId();

	// uz_srvdata_check 테이블에 upsert 실행
	$check_stmt = $conn->prepare("
	INSERT INTO uz_srvdata_check (id, check_status, net_err, tmp_log, web_log, was_log, db_log, sys_log, comments, user_id)
	VALUES (:id, 'f', '점검필요', '점검필요', '점검필요', '점검필요', '점검필요', '점검필요', '점검필요', '1')
	ON CONFLICT (id) DO UPDATE SET
	check_status = EXCLUDED.check_status,
	net_err = EXCLUDED.net_err,
	tmp_log = EXCLUDED.tmp_log,
	web_log = EXCLUDED.web_log,
	was_log = EXCLUDED.was_log,
	db_log = EXCLUDED.db_log,
	sys_log = EXCLUDED.sys_log,
	comments = EXCLUDED.comments
	");
	$check_stmt->bindValue(":id", $last_insert_id, PDO::PARAM_INT);
	$check_stmt->execute();

        http_response_code(201); // HTTP 201: Created
        log_message("INFO", "Data saved successfully: $client / $hostname", $client, $hostname, $client_ip, $conn);
        //echo json_encode(["message" => "Data inserted successfully"]);
    } else {
        http_response_code(500); // HTTP 500: Internal Server Error
        log_message("ERROR", "Data save failed: $client / $hostname", $client, $hostname, $client_ip, $conn);
        //echo json_encode(["error" => "Failed to insert data"]);
    }

    // 업로드 디렉토리 설정
//    $upload_dir = "/data/report/uzart/srv/$server_name/";
//
//    // 업로드 디렉토리 및 파일 경로 설정
//    $file_name = sprintf("%s-%s.json", $hostname, date('Y-m-d')); // 파일 이름을 YYYY-MM-DD.json 형식으로 설정
//    $file_path = $upload_dir . $file_name;
//    
//    // 파일 저장 시도
//    if (file_put_contents($file_path, $input) !== false) {
//        http_response_code(201);
//        log_message("INFO", "Data saved successfully: $client ", $client, $hostname, $client_ip, $conn);
//    } else {
//        http_response_code(500);
//        log_message("ERROR", "Data save failed: $file_path", $client, $hostname, $client_ip, $conn);
//    }

} else {
    // POST 외 요청 처리: index.php 호출
    include(__DIR__ . "/uzart/index.php");
    exit;
}
?>
