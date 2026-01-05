<?php require_once dirname(__DIR__, 3) . '/api/core/auth.php'; // Include auth helper ?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf ?? $_SESSION['csrf_token'] ?? '') ?>">

    <title>MemberSync Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
      @keyframes wave {
        0%, 100% {
          transform: scaleY(0.5);
        }
        50% {
          transform: scaleY(1.2);
        }
      }
      .wave-bar {
        animation: wave 1.2s ease-in-out infinite;
      }
    </style>
    </head>
<body class="h-full bg-gradient-to-br from-white to-gray-100 text-gray-800">
<div class="min-h-full">