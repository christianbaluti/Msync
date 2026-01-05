<?php
// Ensure a session is active to check login status.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Determine the correct "home" link based on the user's login status.
// Logged-in users go to the dashboard, others go to the main home page.
$home_link = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true ? '/dashboard' : '/home';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white flex min-h-full flex-col items-center justify-center px-6 py-24 sm:py-32 lg:px-8">
    <main class="text-center">
        <p class="text-base font-semibold text-red-600">403</p>
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 sm:text-5xl">Access denied</h1>
        <p class="mt-6 text-base leading-7 text-gray-600">Sorry, you don’t have permission to access this page.</p>
        <div class="mt-10 flex items-center justify-center gap-x-6">
            <a href="<?php echo $home_link; ?>" class="rounded-md bg-red-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                Go back home
            </a>
            <a href="/contact" class="text-sm font-semibold text-gray-900">Contact support <span aria-hidden="true">&rarr;</span></a>
        </div>
    </main>
</body>
</html>