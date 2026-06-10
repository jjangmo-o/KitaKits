<?php
require_once(__DIR__ . '/../../app/config/admin.php');
require_once(__DIR__ . '/_response.php');

function require_admin()
{
    global $ADMIN_TOKEN;
    $token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? ($_GET['admin_token'] ?? '');
    if (!$token || $token !== $ADMIN_TOKEN) {
        json_error('Admin authentication required.', 401);
    }
}

?>
