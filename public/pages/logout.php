<?php
require_once(__DIR__ . '/../api/_auth.php');

unset($_SESSION['patient_user_id']);

header('Location: login.php?logged_out=1');
exit();
?>
