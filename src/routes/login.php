<?php


\Tina4\Get::add("/login", function (\Tina4\Response $response) {
    // Render the login page
    return $response(\Tina4\renderTemplate("auth/login.twig"));
});

\Tina4\Post::add("/login", function (\Tina4\Request $request, \Tina4\Response $response) {
    file_put_contents("debug-login-hit.txt", "Login POST route hit!" . PHP_EOL, FILE_APPEND);
    global $DBA;

    // Get username and password from the form submission
    $username = $request->get("username");
    $password = $request->get("password");

    // Fetch user from the database by username and password
    $user = $DBA->fetch("SELECT * FROM users WHERE username = ? AND password = ?", [$username, $password]);

    if ($user) {
        // If user is found, create a session token
        $_SESSION["user_id"] = $user["user_id"];  // Store user ID in session
        $_SESSION["username"] = $user["username"]; // Store username in session

        file_put_contents("debug-login-session.txt", print_r($_SESSION, true));

        // Redirect to the landing page
        return \Tina4\redirect("/landing-page");
    }

    // If user not found or incorrect credentials
    return $response("Invalid username or password", 401);
});
