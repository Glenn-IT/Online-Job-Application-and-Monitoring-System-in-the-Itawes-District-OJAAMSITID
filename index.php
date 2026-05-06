<?php

$configPath = __DIR__ . '/config/db.php';
require_once $configPath;

header("Location: login.php");
exit;
