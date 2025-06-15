<?php 
require_once 'config.php';

session_unset();
session_destroy();

redirect('index.php');
?>