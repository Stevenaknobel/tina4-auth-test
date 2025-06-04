<?php

/**
 * Company Admin routes for company administrators
 */

// Company admin dashboard
\Tina4\Get::add("/company-admin", function (\Tina4\Response $response) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a company admin
    $authService = new \App\AuthService();
    if (!$authService->isCompanyAdmin()) {
        return $response("Unauthorized: Company admin access required", 403);
    }

    // Get company details
    $companyService = new \App\CompanyService();
    $company = $companyService->getCompanyById((int)$_SESSION["company_id"]);

    // Get users for this company
    $userService = new \App\UserService();
    $users = $userService->getUsersByCompany((int)$_SESSION["company_id"]);

    return $response(\Tina4\renderTemplate("company-admin/dashboard.twig", [
        "username" => $_SESSION["username"],
        "company" => $company,
        "users" => $users
    ]));
});

// Add user form
\Tina4\Get::add("/company-admin/users/add", function (\Tina4\Response $response) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a company admin
    $authService = new \App\AuthService();
    if (!$authService->isCompanyAdmin()) {
        return $response("Unauthorized: Company admin access required", 403);
    }

    // Get company details
    $companyService = new \App\CompanyService();
    $company = $companyService->getCompanyById((int)$_SESSION["company_id"]);

    return $response(\Tina4\renderTemplate("company-admin/add-user.twig", [
        "company" => $company
    ]));
});

// Add user action
\Tina4\Post::add("/company-admin/users/add", function (\Tina4\Response $response, \Tina4\Request $request) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a company admin
    $authService = new \App\AuthService();
    if (!$authService->isCompanyAdmin()) {
        return $response("Unauthorized: Company admin access required", 403);
    }

    $username = $request->params["username"] ?? "";
    $password = $request->params["password"] ?? "";
    $role = $request->params["role"] ?? "user"; // Default to regular user

    // Ensure role is valid (company admin can only create regular users)
    if ($role !== "user" && !$authService->isGlobalAdmin()) {
        $role = "user";
    }

    $userService = new \App\UserService();
    $result = $userService->createUser($username, $password, (int)$_SESSION["company_id"]);

    if ($result["success"]) {
        return \Tina4\redirect("/company-admin?success=" . urlencode($result["message"]));
    } else {
        return \Tina4\redirect("/company-admin/users/add?error=" . urlencode($result["message"]));
    }
});

// Edit user form
\Tina4\Get::add("/company-admin/users/edit/{id}", function (\Tina4\Response $response, $id) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a company admin
    $authService = new \App\AuthService();
    if (!$authService->isCompanyAdmin()) {
        return $response("Unauthorized: Company admin access required", 403);
    }

    $userService = new \App\UserService();
    $user = $userService->getUserById((int)$id);

    // Check if user exists and belongs to the admin's company
    if (!$user || $user["company_id"] != $_SESSION["company_id"]) {
        return \Tina4\redirect("/company-admin?error=User not found or unauthorized");
    }

    return $response(\Tina4\renderTemplate("company-admin/edit-user.twig", [
        "user" => $user
    ]));
});

// Edit user action
\Tina4\Post::add("/company-admin/users/edit/{id}", function (\Tina4\Response $response, \Tina4\Request $request, $id) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a company admin
    $authService = new \App\AuthService();
    if (!$authService->isCompanyAdmin()) {
        return $response("Unauthorized: Company admin access required", 403);
    }

    $userService = new \App\UserService();
    $user = $userService->getUserById((int)$id);

    // Check if user exists and belongs to the admin's company
    if (!$user || $user["company_id"] != $_SESSION["company_id"]) {
        return \Tina4\redirect("/company-admin?error=User not found or unauthorized");
    }

    $username = $request->params["username"] ?? "";
    $password = !empty($request->params["password"]) ? $request->params["password"] : null;
    $role = $request->params["role"] ?? "user"; // Default to regular user

    // Ensure role is valid (company admin can only create regular users)
    if ($role !== "user" && !$authService->isGlobalAdmin()) {
        $role = "user";
    }

    $result = $userService->updateUser((int)$id, $username, $password, (int)$_SESSION["company_id"]);

    if ($result["success"]) {
        return \Tina4\redirect("/company-admin?success=" . urlencode($result["message"]));
    } else {
        return \Tina4\redirect("/company-admin/users/edit/{$id}?error=" . urlencode($result["message"]));
    }
});

// Delete user action
\Tina4\Get::add("/company-admin/users/delete/{id}", function (\Tina4\Response $response, $id) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a company admin
    $authService = new \App\AuthService();
    if (!$authService->isCompanyAdmin()) {
        return $response("Unauthorized: Company admin access required", 403);
    }

    $userService = new \App\UserService();
    $user = $userService->getUserById((int)$id);

    // Check if user exists and belongs to the admin's company
    if (!$user || $user["company_id"] != $_SESSION["company_id"]) {
        return \Tina4\redirect("/company-admin?error=User not found or unauthorized");
    }

    // Prevent deleting yourself
    if ($user["user_id"] == $_SESSION["user_id"]) {
        return \Tina4\redirect("/company-admin?error=You cannot delete your own account");
    }

    $result = $userService->deleteUser((int)$id);

    if ($result["success"]) {
        return \Tina4\redirect("/company-admin?success=" . urlencode($result["message"]));
    } else {
        return \Tina4\redirect("/company-admin?error=" . urlencode($result["message"]));
    }
});

// Company branding settings form
\Tina4\Get::add("/company-admin/branding", function (\Tina4\Response $response) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a company admin
    $authService = new \App\AuthService();
    if (!$authService->isCompanyAdmin()) {
        return $response("Unauthorized: Company admin access required", 403);
    }

    // Get company details
    $companyService = new \App\CompanyService();
    $company = $companyService->getCompanyById((int)$_SESSION["company_id"]);

    return $response(\Tina4\renderTemplate("company-admin/branding.twig", [
        "company" => $company
    ]));
});

// Update company branding action
\Tina4\Post::add("/company-admin/branding", function (\Tina4\Response $response, \Tina4\Request $request) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a company admin
    $authService = new \App\AuthService();
    if (!$authService->isCompanyAdmin()) {
        return $response("Unauthorized: Company admin access required", 403);
    }

    // Get company details
    $companyId = (int)$_SESSION["company_id"];

    // Update company branding
    $companies = new Companies();
    $companies->load("company_id = ?", [$companyId]);

    // Update fields
    $companies->logoUrl = $request->params["logo_url"] ?? "";
    $companies->primaryColor = $request->params["primary_color"] ?? "";
    $companies->secondaryColor = $request->params["secondary_color"] ?? "";
    $companies->customCss = $request->params["custom_css"] ?? "";

    // Save changes
    $result = $companies->save();

    if ($result) {
        return \Tina4\redirect("/company-admin/branding?success=Branding settings updated successfully");
    } else {
        return \Tina4\redirect("/company-admin/branding?error=Failed to update branding settings");
    }
});
