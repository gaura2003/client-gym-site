<?php
session_start();
session_destroy();
header('Location: /gym/views/auth/login.php');
exit();
