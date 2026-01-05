<?php
// api/eligibility_helper.php

/**
 * Checks if a user meets the eligibility criteria defined in settings.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $user_id The ID of the user to check.
 * @param array|null $eligibilityRules The eligibility rules array.
 * Expected format:
 * {
 *   "all_users": bool | null,
 *   "all_members": bool | null,
 *   "member_types": [id1, id2, ...] | null
 * }
 * @return bool True if eligible, false otherwise.
 */
function check_user_eligibility(PDO $pdo, int $user_id, ?array $eligibilityRules): bool {
    // If no specific rules are set, default to not eligible (safer)
    if ($eligibilityRules === null || empty($eligibilityRules)) {
        return false;
    }

    // Rule: Allow all users? (Highest priority)
    if (!empty($eligibilityRules['all_users']) && $eligibilityRules['all_users'] === true) {
        return true;
    }

    // --- Membership Checks ---
    $allowed_member_types = $eligibilityRules['member_types'] ?? [];
    $check_all_members = !empty($eligibilityRules['all_members']) && $eligibilityRules['all_members'] === true;

    // Filter and convert member_types to integers for proper comparison
    $allowed_member_types = array_filter($allowed_member_types, function($v) {
        return $v !== null && $v !== '' && is_numeric($v);
    });
    $allowed_member_types = array_map('intval', $allowed_member_types);

    // If neither all_members nor specific member_types are set, user is not eligible via membership
    if (!$check_all_members && empty($allowed_member_types)) {
        return false;
    }

    // Fetch user's *active* membership types
    $active_memberships = [];
    try {
        $stmt_member = $pdo->prepare("
            SELECT membership_type_id
            FROM membership_subscriptions
            WHERE user_id = ?
              AND status = 'active'
              AND end_date >= CURDATE()
        ");
        $stmt_member->execute([$user_id]);
        // Fetch as integers for proper comparison
        $active_memberships = array_map('intval', $stmt_member->fetchAll(PDO::FETCH_COLUMN));
    } catch (PDOException $e) {
        error_log("Eligibility Check DB Error: " . $e->getMessage());
        return false; // Fail safe on DB error
    }

    $is_active_member = !empty($active_memberships);

    // Rule: Allow all *active* members?
    if ($check_all_members && $is_active_member) {
        return true;
    }

    // Rule: Allow specific *active* member types?
    if (!empty($allowed_member_types) && $is_active_member) {
        foreach ($active_memberships as $user_type_id) {
            if (in_array($user_type_id, $allowed_member_types, true)) {
                return true; // Found a matching active membership type
            }
        }
    }

    // If none of the applicable rules passed
    return false;
}

/**
 * Checks if a company meets the eligibility criteria defined in settings.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $company_id The ID of the company to check.
 * @param array|null $eligibilityRules The eligibility rules array.
 * @return bool True if eligible, false otherwise.
 */
function check_company_eligibility(PDO $pdo, int $company_id, ?array $eligibilityRules): bool {
    if ($eligibilityRules === null || empty($eligibilityRules)) {
        return false;
    }

    // Rule: Allow all companies?
    if (!empty($eligibilityRules['all_companies']) && $eligibilityRules['all_companies'] === true) {
        return true;
    }

    // --- Company Membership Checks ---
    $allowed_member_types = $eligibilityRules['member_types'] ?? [];
    $check_all_members = !empty($eligibilityRules['all_members']) && $eligibilityRules['all_members'] === true;

    // Filter and convert member_types to integers
    $allowed_member_types = array_filter($allowed_member_types, function($v) {
        return $v !== null && $v !== '' && is_numeric($v);
    });
    $allowed_member_types = array_map('intval', $allowed_member_types);

    if (!$check_all_members && empty($allowed_member_types)) {
        return false;
    }

    // Fetch company's *active* membership types
    $active_memberships = [];
    try {
        $stmt_member = $pdo->prepare("
            SELECT membership_type_id
            FROM membership_subscriptions
            WHERE company_id = ?
              AND status = 'active'
              AND end_date >= CURDATE()
        ");
        $stmt_member->execute([$company_id]);
        $active_memberships = array_map('intval', $stmt_member->fetchAll(PDO::FETCH_COLUMN));
    } catch (PDOException $e) {
        error_log("Company Eligibility Check DB Error: " . $e->getMessage());
        return false;
    }

    $is_active_member = !empty($active_memberships);

    if ($check_all_members && $is_active_member) {
        return true;
    }

    if (!empty($allowed_member_types) && $is_active_member) {
        foreach ($active_memberships as $company_type_id) {
            if (in_array($company_type_id, $allowed_member_types, true)) {
                return true;
            }
        }
    }

    return false;
}

?>
