<?php

/**
 * Routes for managing user roles
 */

// Display roles management screen
\Tina4\Get::add("/admin/roles", function (\Tina4\Response $response) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a global admin
    $authService = new \App\AuthService();
    if (!$authService->isGlobalAdmin()) {
        return $response("Unauthorized: Global admin access required", 403);
    }

    // Get all users with their company names
    global $DBA;
    $users = $DBA->fetch(
        "SELECT u.*, c.company_name 
        FROM users u 
        JOIN companies c ON u.company_id = c.company_id 
        ORDER BY c.company_name, u.username",
        1000
    )->asArray();

    return $response(\Tina4\renderTemplate("admin/roles.twig", [
        "users" => $users
    ]));
});

// Handle role change
\Tina4\Post::add("/admin/roles/change", function (\Tina4\Response $response, \Tina4\Request $request) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a global admin
    $authService = new \App\AuthService();
    if (!$authService->isGlobalAdmin()) {
        return $response("Unauthorized: Global admin access required", 403);
    }

    $userId = (int)$request->params["user_id"];
    $newRole = $request->params["role"];

    // Validate role
    $validRoles = ["user", "company_admin", "global_admin"];
    if (!in_array($newRole, $validRoles)) {
        return \Tina4\redirect("/admin/roles?error=Invalid role specified");
    }

    // Get the user
    $user = (new Users())->load("user_id = ?", [$userId]);
    if (!$user) {
        return \Tina4\redirect("/admin/roles?error=User not found");
    }

    // Prevent changing own role (security measure)
    if ($userId == $_SESSION["user_id"]) {
        return \Tina4\redirect("/admin/roles?error=You cannot change your own role");
    }

    // Update the user's role
    $user->role = $newRole;
    if ($user->save()) {
        return \Tina4\redirect("/admin/roles?success=Role updated successfully");
    } else {
        return \Tina4\redirect("/admin/roles?error=Failed to update role");
    }
});