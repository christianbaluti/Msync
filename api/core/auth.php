<?php
/**
 * Checks if the currently logged-in user has a specific permission.
 *
 * @param string $permission The name of the permission to check (e.g., 'users_create').
 * @return bool True if the user has the permission, false otherwise.
 */
function has_permission(string $permission): bool {
    // Check if permissions are set in the session and if the specific permission exists.
    return isset($_SESSION['permissions']) && in_array($permission, $_SESSION['permissions']);
}
?>