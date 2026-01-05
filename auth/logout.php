<?php
session_start();
session_destroy();
header("Location: /sirambo/auth/login.php");
exit();
