<?php
require_once(__DIR__ . '/../api/_auth.php');

unset($_SESSION['admin_user_id']);
session_regenerate_id(true);

header('Location: login.php');
exit();
?>
