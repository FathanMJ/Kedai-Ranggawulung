<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'projek');

// Konstanta Aplikasi
define('APP_NAME', 'Kedai Ranggawulung');
define('APP_URL', 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 3));
define('APP_TIMEZONE', 'Asia/Jakarta');
define('APP_CHARSET', 'UTF-8');
define('DB_CHARSET', 'utf8mb4');

// Pengaturan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Membuat direktori logs jika belum ada
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// Mengatur zona waktu
date_default_timezone_set(APP_TIMEZONE);

// Header keamanan
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Pengaturan keamanan session (hanya saat session belum aktif)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', 3600); // 1 jam
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Koneksi database dengan penanganan error
try {
    // Cek apakah database ada
    $temp_mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if (!$temp_mysqli->select_db(DB_NAME)) {
        throw new Exception("Database tidak ditemukan");
    }
    $temp_mysqli->close();
    
    // Buat koneksi utama
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_error) {
        throw new Exception("Koneksi gagal: " . $mysqli->connect_error);
    }
    
    // Set karakter encoding MySQL (harus format MySQL charset, bukan UTF-8)
    if (!$mysqli->set_charset(DB_CHARSET)) {
        throw new Exception("Error setting charset: " . $mysqli->error);
    }
    
    // Set mode SQL yang lebih ketat
    $mysqli->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    
} catch (Exception $e) {
    // Catat error dan tampilkan pesan yang ramah pengguna
    error_log("Error koneksi database: " . $e->getMessage());
    
    // Tentukan pesan error yang sesuai
    $error_message = "Maaf, terjadi kesalahan teknis. ";
    if (strpos($e->getMessage(), "Database tidak ditemukan") !== false) {
        $error_message .= "Database tidak ditemukan. Silakan periksa konfigurasi database Anda.";
    } elseif (strpos($e->getMessage(), "Access denied") !== false) {
        $error_message .= "Akses database ditolak. Silakan periksa username dan password database Anda.";
    } elseif (strpos($e->getMessage(), "Connection refused") !== false) {
        $error_message .= "Tidak dapat terhubung ke database. Silakan periksa apakah MySQL server sudah berjalan.";
    } else {
        $error_message .= "Silakan coba beberapa saat lagi atau hubungi administrator.";
    }
    
    die($error_message);
}

// Fungsi untuk membersihkan output
function bersihkan_output($nilai) {
    return htmlspecialchars($nilai, ENT_QUOTES, APP_CHARSET);
}

// Fungsi untuk menghasilkan token CSRF
function buat_token_csrf() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fungsi untuk memverifikasi token CSRF
function verifikasi_token_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Fungsi untuk mengecek apakah request adalah AJAX
function adalah_request_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Fungsi untuk mengecek koneksi database
function cek_koneksi_database() {
    global $mysqli;
    return ($mysqli && $mysqli->ping());
}

// Fungsi untuk membersihkan koneksi saat aplikasi selesai
register_shutdown_function(function() {
    global $mysqli;
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->close();
    }
});

// Cek ulang koneksi database setelah konfigurasi
if (!cek_koneksi_database()) {
    error_log("Koneksi database terputus setelah konfigurasi");
    die("Maaf, terjadi masalah dengan koneksi database. Silakan muat ulang halaman.");
}
?>