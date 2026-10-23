<?php
require_once __DIR__ . '/../../includes/access_control.php';
Auth::logout();
redirect_to(EPALM_ROLE_LOGINS['admin']);
