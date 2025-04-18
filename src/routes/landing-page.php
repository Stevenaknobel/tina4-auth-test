<?php

/**
 * @description Landing page after login
 */

\Tina4\Get::add("/landing-page", function (\Tina4\Response $response, \Tina4\Request $request ) {

    \Tina4\Debug::message("Landing page route hit!", TINA4_LOG_CRITICAL);
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");  // Redirect to login if not logged in
    }

    // Get user information from session
    $username = $_SESSION["username"];

    // Render the landing page with the user's information
    return $response(\Tina4\renderTemplate("landing-page.twig", [
        "username" => $username // Pass the username to the template
    ]));
});
