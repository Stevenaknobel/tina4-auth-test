<?php

\Tina4\Get::add("/logout", function (\Tina4\Response $response) {
    // Clear session and logout
    session_unset(); // Removes all session variables
    session_destroy(); // Destroys the session

    // Redirect to login page
    return \Tina4\redirect("/login");
});
