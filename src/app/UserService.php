<?php

namespace App;

use Tina4\Debug;
use Users;

//global_admin
//company_admin
//user

/**
 * UserService handles user-related functionality
 */
class UserService
{
    /**
     * Gets all users for a company
     *
     * @param int $companyId The company ID
     * @return array The users
     */
    public function getUsersByCompany(int $companyId): array
    {
     $users = (new Users())->select()->where("company_id = ?", [$companyId])->asArray();

        return $users;
    }

    /**
     * Gets a user by ID
     *
     * @param int $userId The user ID
     * @return array|null The user data or null if not found
     */
    public function getUserById(int $userId): ?array
    {
        global $DBA;

        $result = $DBA->fetch(
            "SELECT u.*, c.company_name 
            FROM users u 
            JOIN companies c ON u.company_id = c.company_id 
            WHERE u.user_id = ?",
            [$userId],
            1
        )->asArray();

        return !empty($result) ? $result[0] : null;
    }

    /**
     * Creates a new user
     *
     * @param string $username The username
     * @param string $password The password
     * @param int $companyId The company ID
     * @return array The result
     */
    public function createUser(string $username, string $password, int $companyId): array
    {
        global $DBA;

        // Validate input
        if (empty($username) || empty($password) || empty($companyId)) {
            return [
                'success' => false,
                'message' => 'Username, password, and company ID are required'
            ];
        }

        // Check if username already exists
        $existing = $DBA->fetch(
            "SELECT * FROM users WHERE username = {$username}")->asArray();

        if (!empty($existing)) {
            return [
                'success' => false,
                'message' => 'Username already exists'
            ];
        }

        // Check if company exists
        $company = $DBA->fetch(
            "SELECT * FROM companies WHERE company_id = {$companyId}")->asArray();

        if (empty($company)) {
            return [
                'success' => false,
                'message' => 'Company not found'
            ];
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Create the user
        $DBA->exec(
            "INSERT INTO users (username, password, company_id) VALUES (?, ?, ?)",
            [$username, $hashedPassword, $companyId]
        );

        // Get the new user ID
        $newUser = $DBA->fetch(
            "SELECT * FROM users WHERE username = {$username}")->asArray();

        if (empty($newUser)) {
            return [
                'success' => false,
                'message' => 'Failed to create user'
            ];
        }

        Debug::message("User created: {$username} (ID: {$newUser[0]['user_id']})", "CRITICAL");

        return [
            'success' => true,
            'message' => 'User created successfully',
            'user' => $newUser[0]
        ];
    }

    /**
     * Updates a user
     *
     * @param int $userId The user ID
     * @param string $username The username
     * @param string|null $password The password (null to keep current)
     * @param int $companyId The company ID
     * @return array The result
     */
    public function updateUser(int $userId, string $username, ?string $password, int $companyId): array
    {
        global $DBA;

        // Validate input
        if (empty($username) || empty($companyId)) {
            return [
                'success' => false,
                'message' => 'Username and company ID are required'
            ];
        }

        // Check if user exists
        $existing = $DBA->fetch(
            "SELECT * FROM users WHERE user_id = {$userId}")->asArray();

        if (empty($existing)) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }

        // Check if username is already used by another user
        $nameExists = $DBA->fetch(
            "SELECT * FROM users WHERE username = {$username} AND user_id != {$userId}")->asArray();

        if (!empty($nameExists)) {
            return [
                'success' => false,
                'message' => 'Username already exists'
            ];
        }

        // Check if company exists
        $company = $DBA->fetch(
            "SELECT * FROM companies WHERE company_id = {$companyId}")->asArray();

        if (empty($company)) {
            return [
                'success' => false,
                'message' => 'Company not found'
            ];
        }

        // Update the user
        if ($password !== null) {
            // Hash the new password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $DBA->exec(
                "UPDATE users SET username = {$username}, password = {$hashedPassword}, company_id = {$companyId} WHERE user_id = {$userId}");
        } else {
            $DBA->exec(
                "UPDATE users SET username = {$username}, company_id = {$companyId} WHERE user_id = {$userId}"
            );
        }

        Debug::message("User updated: {$username} (ID: {$userId})", "CRITICAL");

        return [
            'success' => true,
            'message' => 'User updated successfully'
        ];
    }

    /**
     * Deletes a user
     *
     * @param int $userId The user ID
     * @return array The result
     */
    public function deleteUser(int $userId): array
    {
        global $DBA;

        // Check if user exists
        $existing = $DBA->fetch(
            "SELECT * FROM users WHERE user_id = ?",
            [$userId],
            1
        )->asArray();

        if (empty($existing)) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }

        // Delete the user's tokens
        $DBA->exec(
            "DELETE FROM tokens WHERE user_id = ?",
            [$userId]
        );

        // Delete the user
        $DBA->exec(
            "DELETE FROM users WHERE user_id = ?",
            [$userId]
        );

        Debug::message("User deleted: ID {$userId}", "CRITICAL");

        return [
            'success' => true,
            'message' => 'User deleted successfully'
        ];
    }

    /**
     * Enables 2FA for a user
     *
     * @param int $userId The user ID
     * @param string $secret The 2FA secret
     * @return array The result
     */
    public function enable2FA(int $userId, string $secret): array
    {
        // Check if user exists
        $user = new \Users();
        $user = $user->load("userId = ?", [$userId]);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }

        // Update the user
        $user->twofaSecret = $secret;
        $user->twofaEnabled = 1;
        $user->save();

        Debug::message("2FA enabled for user ID {$userId}", "CRITICAL");

        return [
            'success' => true,
            'message' => '2FA enabled successfully'
        ];
    }

    /**
     * Disables 2FA for a user
     *
     * @param int $userId The user ID
     * @return array The result
     */
    public function disable2FA(int $userId): array
    {
        // Check if user exists
        $user = new \Users();
        $user = $user->load("userId = ?", [$userId]);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }

        // Update the user
        $user->twofaSecret = null;
        $user->twofaEnabled = 0;
        $user->save();

        Debug::message("2FA disabled for user ID {$userId}", "CRITICAL");

        return [
            'success' => true,
            'message' => '2FA disabled successfully'
        ];
    }
}