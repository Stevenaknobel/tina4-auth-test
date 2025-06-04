<?php

namespace App;

use Tina4\Debug;

/**
 * AuthService handles authentication-related functionality
 */
class AuthService
{
    /**
     * Validates user credentials
     *
     * @param string $username The username
     * @param string $password The password
     * @return array|null User data if valid, null if invalid
     */
    public function validateCredentials(string $username, string $password): ?array
    {
        global $DBA;

        // Get user by username
        $result = $DBA->fetch(
            "SELECT * FROM users WHERE username = ?",
            [$username],
            1
        )->asArray();

        if (empty($result)) {
            Debug::message("Login failed: User not found - {$username}", "CRITICAL");
            return null;
        }

        $user = $result[0];

        // Verify password
        if (!password_verify($password, $user['password'])) {
            Debug::message("Login failed: Invalid password for user - {$username}", "CRITICAL");
            return null;
        }

        Debug::message("Login successful for user - {$username}", "CRITICAL");
        return $user;
    }

    /**
     * Generates a 2FA secret for a user
     *
     * @param int $userId The user ID
     * @return string The 2FA secret
     */
    public function generate2FASecret(int $userId): string
    {
        // Generate a random secret
        $secret = bin2hex(random_bytes(16));

        global $DBA;

        // Store the secret in the database
        $DBA->exec(
            "UPDATE users SET twofa_secret = ? WHERE user_id = ?",
            [$secret, $userId]
        );

        return $secret;
    }

    /**
     * Verifies a 2FA code
     *
     * @param string $secret The 2FA secret
     * @param string $code The code to verify
     * @return bool True if valid, false otherwise
     */
    public function verify2FACode(string $secret, string $code): bool
    {
        // For simplicity, we'll use a basic implementation
        // In production, use a proper TOTP library

        // Generate the expected code based on the secret and current time
        $expectedCode = substr(hash('sha256', $secret . floor(time() / 30)), 0, 6);

        // Compare the codes
        return $code === $expectedCode;
    }

    /**
     * Creates a session for a user
     *
     * @param array $user The user data
     * @return void
     */
    public function createSession(array $user): void
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Set session variables
        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["company_id"] = $user["company_id"];
        $_SESSION["role"] = $user["role"] ?? "user"; // Default to 'user' if role is not set
        $_SESSION["last_activity"] = time();

        Debug::message("Session created for user - {$user['username']} with role {$_SESSION['role']}", "CRITICAL");
    }

    /**
     * Checks if the current user has a specific role
     *
     * @param string|array $roles The role(s) to check
     * @return bool True if the user has the role, false otherwise
     */
    public function hasRole($roles): bool
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // If no user is logged in, return false
        if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {
            return false;
        }

        // Convert single role to array
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        // Check if the user's role is in the list of allowed roles
        return in_array($_SESSION["role"], $roles);
    }

    /**
     * Checks if the current user is a global admin
     *
     * @return bool True if the user is a global admin, false otherwise
     */
    public function isGlobalAdmin(): bool
    {
        return $this->hasRole("global_admin");
    }

    /**
     * Checks if the current user is a company admin
     *
     * @return bool True if the user is a company admin, false otherwise
     */
    public function isCompanyAdmin(): bool
    {
        return $this->hasRole(["global_admin", "company_admin"]);
    }

    /**
     * Destroys the current session
     *
     * @return void
     */
    public function destroySession(): void
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear session variables
        session_unset();

        // Destroy the session
        session_destroy();

        Debug::message("Session destroyed", "CRITICAL");
    }
}
