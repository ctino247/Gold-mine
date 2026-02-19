<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php
        $site_name = isset($pdo) ? get_setting($pdo, 'site_name', 'RecruitPlatform') : 'RecruitPlatform';
        echo (isset($page_title) ? $page_title . ' - ' : '') . $site_name;
    ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            padding-bottom: 70px; /* Space for bottom nav */
        }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .bottom-nav a {
            color: #6c757d;
            text-decoration: none;
            text-align: center;
            font-size: 12px;
        }
        .bottom-nav a.active {
            color: #0d6efd;
        }
        .bottom-nav i {
            display: block;
            font-size: 20px;
            margin-bottom: 2px;
        }
    </style>
</head>
<body>
<div class="container-fluid pt-3">
    <?php display_flash_message(); ?>
