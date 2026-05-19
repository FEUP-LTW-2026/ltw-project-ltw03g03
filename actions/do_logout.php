<?php
declare(strict_types=1);

require_once('../config/session.php');
session_destroy();

header('Location: ../pages/sign_in.php');
