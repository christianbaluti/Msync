<?php 
require_once dirname(__DIR__, 2) . '/partials/header.php'; 
require_once dirname(__DIR__, 2) . '/partials/sidebar.php'; 

// Get the schedule ID from the URL
$schedule_id = $_GET['id'] ?? 0;
$schedule = null;
$error_message = '';

if ($schedule_id) {
    try {
        // 1. Fetch the schedule type to determine which view to load
        $db = (new Database())->connect();
        $stmt = $db->prepare("SELECT id, event_id, type FROM event_schedules WHERE id = :id");
        $stmt->execute([':id' => $schedule_id]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            $error_message = 'Schedule not found.';
        }
    } catch (Exception $e) {
        $error_message = 'A database error occurred.';
    }
} else {
    $error_message = 'No Schedule ID was provided.';
}
?>

<div class="md:pl-72">
    <?php require_once dirname(__DIR__, 2) . '/partials/menubar.php'; ?>
    <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8">
            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php elseif ($schedule): ?>
                <?php
                // 2. Use a switch statement to load the correct management interface (the "partial" view)
                switch ($schedule['type']) {
                    case 'training':
                        // If the type is 'training', load the specific partial for it.
                        require_once '_manage_training_partial.php';
                        break;
                    // ADDED: This new case for 'meal'
                    case 'meal':
                        require_once '_manage_meal_partial.php';
                        break;

                    case 'awards':
                        require_once '_manage_awards_partial.php';
                        break;
                    case 'voting':
                        require_once '_manage_voting_partial.php';
                        break;

                    default:
                        // A fallback for 'general' or any other type.
                        echo "<h2>General schedule management interface.</h2>";
                        break;
                }
                ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once dirname(__DIR__, 2) . '/partials/footer.php'; ?>