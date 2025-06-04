<?php

/**
 * Admin routes for global administrators
 */

/**
 * Displays the admin dashboard with list of companies
 * @param \Tina4\Response $response
 * @return string
 */
\Tina4\Get::add("/admin", function (\Tina4\Response $response) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a global admin
    $authService = new \App\AuthService();
    if (!$authService->isGlobalAdmin()) {
        return $response("Unauthorized: Global admin access required", 403);
    }

    // Get all companies
    $companyService = new \App\CompanyService();
    $companies = $companyService->getAllCompanies();

    return $response(\Tina4\renderTemplate("admin/dashboard.twig", [
        "username" => $_SESSION["username"],
        "companies" => $companies
    ]));
});

/**
 * Shows form for adding a new company
 * @param \Tina4\Response $response
 * @return string
 */
\Tina4\Get::add("/admin/companies/add", function (\Tina4\Response $response) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a global admin
    $authService = new \App\AuthService();
    if (!$authService->isGlobalAdmin()) {
        return $response("Unauthorized: Global admin access required", 403);
    }

    return $response(\Tina4\renderTemplate("admin/add-company.twig"));
});

/**
 * Processes the add company form submission
 * @param \Tina4\Response $response
 * @param \Tina4\Request $request
 * @return string
 */
\Tina4\Post::add("/admin/companies/add", function (\Tina4\Response $response, \Tina4\Request $request) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a global admin
    $authService = new \App\AuthService();
    if (!$authService->isGlobalAdmin()) {
        return $response("Unauthorized: Global admin access required", 403);
    }

    $companyName = $request->params["company_name"] ?? "";

    $companyService = new \App\CompanyService();
    $result = $companyService->createCompany($companyName);

    if ($result["success"]) {
        return \Tina4\redirect("/admin?success=" . urlencode($result["message"]));
    } else {
        return \Tina4\redirect("/admin/companies/add?error=" . urlencode($result["message"]));
    }
});

// Edit company form
\Tina4\Get::add("/admin/companies/edit/{id}", function ($id, \Tina4\Response $response, \Tina4\Request $request) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a global admin
    $authService = new \App\AuthService();
    if (!$authService->isGlobalAdmin()) {
        return $response("Unauthorized: Global admin access required", 403);
    }

//    $companyService = new \App\CompanyService();
//    $company = $companyService->getCompanyById((int)$id);
    $company = (new Companies())->load("company_id = ?", [$id]);

    if (!$company) {
        return \Tina4\redirect("/admin?error=Company not found");
    }
    $companyArray = [
        'company_id' => $company->companyId,
        'company_name' => $company->companyName,
        'created_at' => $company->createdAt,
        // Add other fields as needed
    ];

    return $response(\Tina4\renderTemplate("admin/edit-company.twig", [
        "company" => $companyArray
    ]));
});

// Edit company action
\Tina4\Post::add("/admin/companies/edit/{id}", function ($id, \Tina4\Response $response, \Tina4\Request $request ) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a global admin
    $authService = new \App\AuthService();
    if (!$authService->isGlobalAdmin()) {
        return $response("Unauthorized: Global admin access required", 403);
    }

    $companyName = $request->params["company_name"] ?? "";

    $companyService = new \App\CompanyService();
    $result = $companyService->updateCompany((int)$id, $companyName);

    if ($result["success"]) {
        return \Tina4\redirect("/admin?success=" . urlencode($result["message"]));
    } else {
        return \Tina4\redirect("/admin/companies/edit/{$id}?error=" . urlencode($result["message"]));
    }
});

// Delete company action
\Tina4\Get::add("/admin/companies/delete/{id}", function ($id, \Tina4\Response $response, \Tina4\Request $request) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a global admin
    $authService = new \App\AuthService();
    if (!$authService->isGlobalAdmin()) {
        return $response("Unauthorized: Global admin access required", 403);
    }

    $companyService = new \App\CompanyService();
    $result = $companyService->deleteCompany((int)$id);

    if ($result["success"]) {
        return \Tina4\redirect("/admin?success=" . urlencode($result["message"]));
    } else {
        return \Tina4\redirect("/admin?error=" . urlencode($result["message"]));
    }
});


// Login as user (impersonation)
\Tina4\Get::add("/admin/login-as/{userId}", function ( $userId, \Tina4\Response $response, \Tina4\Request $request) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a global admin
    $authService = new \App\AuthService();
    if (!$authService->isGlobalAdmin()) {
        return $response("Unauthorized: Global admin access required", 403);
    }

    // Get the user to impersonate
    $user = (new Users())->load("user_id = ?", [$userId]);

    if (!$user) {
        return \Tina4\redirect("/admin?error=User not found");
    }

    // Debug user data
    \Tina4\Debug::message("Impersonating user: {$user->username} (ID: {$user->userId})", "INFO");

    // Store original admin info for switching back
    $_SESSION["admin_user_id"] = $_SESSION["user_id"];
    $_SESSION["admin_username"] = $_SESSION["username"];
    $_SESSION["admin_role"] = $_SESSION["role"];

    // Set session to impersonate the user
    $_SESSION["user_id"] = $user->userId;
    $_SESSION["username"] = $user->username;
    $_SESSION["company_id"] = $user->companyId;
    $_SESSION["role"] = $user->role;
    $_SESSION["impersonating"] = true;

    return \Tina4\redirect("/landing-page?notice=You are now viewing as " . $user->username);
});

// Return to admin account
\Tina4\Get::add("/admin/return", function (\Tina4\Response $response) {
    // Check if user is impersonating
    if (!isset($_SESSION["impersonating"]) || !isset($_SESSION["admin_user_id"])) {
        return \Tina4\redirect("/landing-page");
    }

    // Restore admin session
    $_SESSION["user_id"] = $_SESSION["admin_user_id"];
    $_SESSION["username"] = $_SESSION["admin_username"];
    $_SESSION["role"] = $_SESSION["admin_role"];

    // Clean up impersonation data
    unset($_SESSION["admin_user_id"]);
    unset($_SESSION["admin_username"]);
    unset($_SESSION["admin_role"]);
    unset($_SESSION["impersonating"]);

    return \Tina4\redirect("/admin?success=Returned to admin account");
});

// Manage all users across companies
\Tina4\Get::add("/admin/users", function (\Tina4\Response $response) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a global admin
    $authService = new \App\AuthService();
    if (!$authService->isGlobalAdmin()) {
        return $response("Unauthorized: Global admin access required", 403);
    }

    // Get all users from all companies
    global $DBA;
    $users = $DBA->fetch(
        "SELECT u.*, c.company_name 
        FROM users u 
        JOIN companies c ON u.company_id = c.company_id 
        ORDER BY c.company_name, u.username",
        1000
    )->asArray();

    return $response(\Tina4\renderTemplate("users/grid.twig", [
        "users" => $users,
        "isAdmin" => true
    ]));
});

// Manage monitoring types
\Tina4\Get::add("/admin/monitoring-types", function (\Tina4\Response $response, \Tina4\Request $request) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a global admin
    $authService = new \App\AuthService();
    if (!$authService->isGlobalAdmin()) {
        return $response("Unauthorized: Global admin access required", 403);
    }

    return $response(\Tina4\renderTemplate("monitoringtypes/grid.twig", [
        "isAdmin" => true
    ]));
});

// Show create user form

\Tina4\Get::add("/admin/users/create", function (\Tina4\Response $response, \Tina4\Request $request) {
    //print_r($request->params);die('get');
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a global admin
    $authService = new \App\AuthService();
    if (!$authService->isGlobalAdmin()) {
        return $response("Unauthorized: Global admin access required", 403);
    }


    // Get all companies for the dropdown
    $companies = (new Companies())->select("*")->orderBy("company_name")->asArray();

    // Get selected company ID from query parameter if available
    $selectedCompanyId = isset($request->params["company_id"]) ? (int)$request->params["company_id"] : null;

    return $response(\Tina4\renderTemplate("admin/create-user.twig", [
        "companies" => $companies,
        "selectedCompanyId" => $selectedCompanyId
    ]));
});

// Handle create user form submission
\Tina4\Post::add("/admin/users/create", function (\Tina4\Response $response, \Tina4\Request $request) {
    print_r($request->params);die('post');
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    // Check if user is a global admin
    $authService = new \App\AuthService();
    if (!$authService->isGlobalAdmin()) {
        return $response("Unauthorized: Global admin access required", 403);
    }


    $username = $request->params['username'];
    $email = $request->params['email'];
    $password = $request->params['password'];
    $companyId = (int)$request->params['company_id'];
    $role = $request->params['role'];

    // Validate inputs
    if (empty($username) || empty($password) || empty($companyId)) {
        return \Tina4\redirect("/admin/users/create?error=Username, password, and company are required");
    }

    // Validate role
    $validRoles = ["user", "company_admin", "global_admin"];
    if (!in_array($role, $validRoles)) {
        $role = "user"; // Default to user if invalid role
    }

    // Check if username already exists
    $existingUser = (new Users())->load("username = ?", [$username]);
    if ($existingUser) {
        return \Tina4\redirect("/admin/users/create?error=Username already exists");
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Create the user
    $user = new Users();
    $user->username = $username;
    $user->email = $email;
    $user->password = $hashedPassword;
    $user->companyId = $companyId;
    $user->role = $role;

    if ($user->save()) {
        return \Tina4\redirect("/admin/users?success=User created successfully");
    } else {
        return \Tina4\redirect("/admin/users/create?error=Failed to create user");
    }
});


// View company users
\Tina4\Get::add("/admin/users/{companyId}", function ($companyId, \Tina4\Response $response, \Tina4\Request $request) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }
    // Check if user is a global admin
    $authService = new \App\AuthService();
    if (!$authService->isGlobalAdmin()) {
        return $response("Unauthorized: Global admin access required", 403);
    }

    $company = (new Companies())->load("company_id = ?", [$companyId]);
    if (!$company){
        return \Tina4\redirect("/admin?error=Company not found");
    }

    // Convert company object to array for the template
    //$companyArray = $company->asArray()[0];

    // Debug company data
    //\Tina4\Debug::message("Company data: " . json_encode($companyArray), "INFO");

    $users = (new Users())->select("*")->where("company_id = ?", [$companyId])->asArray();

    // Debug the output
    \Tina4\Debug::message("Users found for company {$companyId}: " . count($users), "INFO");

    return $response(\Tina4\renderTemplate("admin/company-users.twig", [
        "company" => $company,
        "users" => $users
    ]));
});
