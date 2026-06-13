<?php
require_once __DIR__ . '/../lib/helpers.php';
session_destroy();
header("Location: ../index.php");
exit;
