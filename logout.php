<?php
require_once 'config.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';

$auth = new Auth();
$auth->logout();
header('Location: index.php');
exit;
