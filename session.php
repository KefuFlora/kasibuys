<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', '0');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.gc_maxlifetime', '86400');
    session_start();
}
