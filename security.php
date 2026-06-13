<?php
// Prevent direct access to config files
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

// Sanitize input
function sanitize(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Generate CSRF token
function generateCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCSRF(string $token): void {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Please go back and try again.');
    }
}

// Rate limiting
function rateLimit(string $key, int $max_attempts = 5, int $window = 300): bool {
    $file = sys_get_temp_dir() . '/rate_' . md5($key);
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : ['attempts' => 0, 'time' => time()];

    if (time() - $data['time'] > $window) {
        $data = ['attempts' => 0, 'time' => time()];
    }

    $data['attempts']++;
    file_put_contents($file, json_encode($data));

    if ($data['attempts'] > $max_attempts) {
        return false;
    }
    return true;
}

// Validate South African phone number
function validateSAPhone(string $phone): bool {
    return preg_match('/^(\+27|0)[6-8][0-9]{8}$/', $phone);
}

// Validate South African ID number
function validateSAID(string $id): bool {
    if (strlen($id) !== 13 || !ctype_digit($id)) return false;
    $digits = str_split($id);
    $odd_sum = 0;
    $even_digits = '';
    for ($i = 0; $i < 12; $i++) {
        if ($i % 2 === 0) {
            $odd_sum += $digits[$i];
        } else {
            $even_digits .= $digits[$i];
        }
    }
    $even_sum = 0;
    $doubled = (string)((int)$even_digits * 2);
    for ($i = 0; $i < strlen($doubled); $i++) {
        $even_sum += $doubled[$i];
    }
    $total = $odd_sum + $even_sum;
    $check = (10 - ($total % 10)) % 10;
    return $check == $digits[12];
}

// Prevent clickjacking
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
