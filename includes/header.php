<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

// Set default timezone ke Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

$userData = getUserData($db, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<!-- [Head] start -->

<head>
    <title>WEBSITE NARASA</title>
    <!-- [Meta] -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Mantis is made using Bootstrap 5 design framework. Download the free admin template & use it for your project.">
    <meta name="keywords" content="Mantis, Dashboard UI Kit, Bootstrap 5, Admin Template, Admin Dashboard, CRM, CMS, Bootstrap Admin Template">
    <meta name="author" content="CodedThemes">

    <!-- [Favicon] icon -->
    <!-- <link rel="icon" href="/narasa-cake/assets/images/favicon.svg" type="image/x-icon"> -->
    <link rel="icon" href="/narasa-cake/assets/images/Logo.png" type="image/x-icon">

    <!-- [Google Font] Family -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" id="main-font-link">

    <!-- [Tabler Icons] https://tablericons.com -->
    <link rel="stylesheet" href="/narasa-cake/assets/fonts/tabler-icons.min.css">

    <!-- [Feather Icons] https://feathericons.com -->
    <link rel="stylesheet" href="/narasa-cake/assets/fonts/feather.css">

    <!-- [Font Awesome Icons] https://fontawesome.com/icons -->
    <link rel="stylesheet" href="/narasa-cake/assets/fonts/fontawesome.css">

    <!-- [Material Icons] https://fonts.google.com/icons -->
    <link rel="stylesheet" href="/narasa-cake/assets/fonts/material.css">

    <!-- [Select2 CSS] -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <!-- [Template CSS Files] -->
    <link rel="stylesheet" href="/narasa-cake/assets/css/style.css" id="main-style-link">
    <link rel="stylesheet" href="/narasa-cake/assets/css/style-preset.css">

    <!-- [Inline CSS for Select2] -->
    <style>
        .select2-container--bootstrap-5 .select2-selection {
            min-height: calc(1.5em + 1rem + 2px);
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 0.5rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding-left: 0;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>