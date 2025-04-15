<?php

use Tina4\Auth;

class AuthHelper extends Auth
{
    public function validToken(string $token, string $publicKey = "", string $encryption = ""): bool
    {
        // Instead of using JWT, check session data
        if (isset($_SESSION["user_id"])) {
            return true;
        }

        return false;
    }
}