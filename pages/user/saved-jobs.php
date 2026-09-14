<?php
require_once __DIR__ . "/../../config/auth.php";
requireUser();
header("Location: " . BASE_URL . "/pages/user/browse-jobs.php");
exit;
