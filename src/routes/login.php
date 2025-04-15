<?php


\Tina4\Get::add("/login", function (\Tina4\Response $response) {
    // Render the login page
    return $response(\Tina4\renderTemplate("auth/login.twig"));
});

\Tina4\Post::add("/login", function (\Tina4\Response $response, \Tina4\Request $request ) {
    file_put_contents("debug-login-hit.txt", "Login POST route hit!" . PHP_EOL, FILE_APPEND);

    $username = $request->params['username'];
    $password = $request->params['password'];
\Tina4\Debug::message("Login attempt for user: " . $username,  "CRITICAL");
    // Fetch user from the database by username and password
    $users = (new Users($request->params));
    $user = $users->load("username = ? and password = ?", [$username, $password]);

    if(!$user){
        \Tina4\redirect(TINA4_SUB_FOLDER."/login?error=Invalid email address or password");
    }
    if ($user) {
        // If user is found, create a session token
        $_SESSION["user_id"] = $user->userId;  // Store user ID in session
        $_SESSION["username"] = $user->username; // Store username in session

        file_put_contents("debug-login-session.txt", print_r($_SESSION, true));

        // Redirect to the landing page
        \Tina4\redirect("/landing-page");
    }

})::noCache();
