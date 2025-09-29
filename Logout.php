<?php
session_start();
session_destroy();
header("Location: LoginPerodua.php");
exit();
?>
