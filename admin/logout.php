<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use MicroCMS\Auth;

Auth::logout();
header('Location: login.php');
exit;
