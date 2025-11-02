<?php
require_once __DIR__ . '/../utils/session.php';

logoutUser();
header('Location: login.php');
exit;

?>
