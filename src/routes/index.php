<?php

/**
 * Landing page route
 * This is the entry point to the application
 */

\Tina4\Get::add("/", function (\Tina4\Response $response) {
    // If user is logged in, redirect to the dashboard
    if (isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/landing-page");
    }
    
    // Otherwise, redirect to the login page
    return \Tina4\redirect("/login");
});