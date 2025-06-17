<?php
/**
 * secure config.inc.php  (2025-06-16 ENV 버전)
 * -------------------------------------------
 * 1) .env 파일을 자동 로드해 환경 변수 세팅
 * 2) getenv() 기반으로 DB DSN 구성
 * 3) SSL 연결·예외 로깅 강화
 */

/*────────────  PHP-7 폴리필 ────────────*/
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
/*──────────────────────────────────────*/


/*───────────────────────────────────────────*
 *  ENV LOADER
 *───────────────────────────────────────────*/
if (!function_exists('loadEnvFile')) {
    /**
     * 간단한 .env 파서 (외부 패키지 불필요)
     * 기존 환경 변수는 덮어쓰지 않는다.
     *
     * @param string $file  절대경로
     */
    function loadEnvFile(string $file): void
    {
        if (!is_readable($file)) {
	    error_log("[DEBUG] .env not readable: $file");
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            // 주석 또는 빈 줄 무시
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $val] = array_map('trim', explode('=', $line, 2));

            // 따옴표 제거
            $val = trim($val, "\"'");

            if ($key !== '' && getenv($key) === false) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
            }
        }
    }
}

/* .env 자동 탐색: 프로젝트 루트 기준 */
$rootDir = dirname(__DIR__, 2);          // report/uzart/include → ../../
$envPath = $rootDir . DIRECTORY_SEPARATOR . '.env';
loadEnvFile($envPath);                   // 있으면 로드, 없으면 무시 

/*───────────────────────────────────────────*
 *  ENV HELPER
 *───────────────────────────────────────────*/
function envOr(string $key, $default = '')
{
    $val = getenv($key);
    return ($val === false || $val === '') ? $default : $val;
}

/*───────────────────────────────────────────*
 *  DATABASE CONFIG
 *───────────────────────────────────────────*/
$DB_HOST    = envOr('UZART_DB_HOST');
$DB_PORT    = envOr('UZART_DB_PORT');
$DB_NAME    = envOr('UZART_DB_NAME');
$DB_USER    = envOr('UZART_DB_USER');
$DB_PASS    = envOr('UZART_DB_PASS');                // 필수
$DB_SSLMODE = envOr('UZART_DB_SSLMODE');

if ($DB_PASS === '') {
    error_log('[Uzart] DB password not provided (UZART_DB_PASS).');
    http_response_code(500);
    die('Database configuration error.');
}

/*───────────────────────────────────────────*
 *  PDO 연결
 *───────────────────────────────────────────*/
try {
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
        $DB_HOST,
        $DB_PORT,
        $DB_NAME,
        $DB_SSLMODE
    );

    $conn = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        //production: PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    error_log('[Uzart] DB Connection Error: ' . $e->getMessage());
    http_response_code(500);
    die('Unable to connect to database.');
}

