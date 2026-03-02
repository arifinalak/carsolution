<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

unset($_SESSION['admin_id'], $_SESSION['admin_username']);
set_flash('You have been logged out.', 'success');
redirect('/admin/login.php');
