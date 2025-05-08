<?php
session_start();
include '../includes/config.php';
include '../includes/admin-functions.php';

// Log out the admin
adminLogout();

// Redirect to login page
header('Location: login.php');
exit;
