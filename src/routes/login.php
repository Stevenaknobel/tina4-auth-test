<?php


\Tina4\Get::add("/login", function (\Tina4\Response $response) {
    // Render the login page
    return $response(\Tina4\renderTemplate("auth/login.twig"));
});

\Tina4\Post::add("/login", function (\Tina4\Response $response, \Tina4\Request $request ) {
    $username = $request->params['username'];
    $password = $request->params['password'];
    \Tina4\Debug::message("Login attempt for user: " . $username,  "CRITICAL");

    // Fetch user by username only
    $users = new Users();
    $user = $users->load("username = ?", [$username]);

    if (!$user || !password_verify($password, $user->password)) {
        \Tina4\redirect(TINA4_SUB_FOLDER . "/login?error=Invalid email address or password");
    }

    // Check if 2FA is enabled for this user
    if ($user->twofaEnabled) {
        // Redirect to 2FA verification page
        \Tina4\redirect("/verify-2fa?user_id=" . $user->userId);
    } else {
        // Valid login without 2FA, set session
        $_SESSION["user_id"] = $user->userId;
        $_SESSION["username"] = $user->username;
        $_SESSION["company_id"] = $user->companyId;
        $_SESSION["role"] = $user->role ?? "user";

        \Tina4\redirect("/landing-page");
    }

})::noCache();
