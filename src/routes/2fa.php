<?php

/**
 * 2FA routes for two-factor authentication
 */

// Setup 2FA form
\Tina4\Get::add("/setup-2fa", function (\Tina4\Response $response) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }
    
    // Generate a 2FA secret
    $authService = new \App\AuthService();
    $secret = $authService->generate2FASecret((int)$_SESSION["user_id"]);
    
    // Generate a QR code URL (in a real app, use a proper TOTP library)
    $username = $_SESSION["username"];
    $qrCodeUrl = "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth://totp/MonitoringSystem:" . urlencode($username) . "?secret=" . $secret . "&issuer=MonitoringSystem";
    
    return $response(\Tina4\renderTemplate("auth/setup-2fa.twig", [
        "secret" => $secret,
        "qr_code_url" => $qrCodeUrl
    ]));
});

// Setup 2FA action
\Tina4\Post::add("/setup-2fa", function (\Tina4\Response $response, \Tina4\Request $request) {
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }
    
    $secret = $request->params["secret"] ?? "";
    $code = $request->params["code"] ?? "";
    
    // Verify the code
    $authService = new \App\AuthService();
    if ($authService->verify2FACode($secret, $code)) {
        // Enable 2FA for the user
        $userService = new \App\UserService();
        $userService->enable2FA((int)$_SESSION["user_id"], $secret);
        
        return \Tina4\redirect("/landing-page?success=Two-factor authentication enabled successfully");
    } else {
        // Generate a QR code URL again
        $username = $_SESSION["username"];
        $qrCodeUrl = "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth://totp/MonitoringSystem:" . urlencode($username) . "?secret=" . $secret . "&issuer=MonitoringSystem";
        
        return $response(\Tina4\renderTemplate("auth/setup-2fa.twig", [
            "secret" => $secret,
            "qr_code_url" => $qrCodeUrl,
            "error" => "Invalid verification code. Please try again."
        ]));
    }
});

// Verify 2FA form
\Tina4\Get::add("/verify-2fa", function (\Tina4\Response $response, \Tina4\Request $request) {
    $userId = $request->params["user_id"] ?? "";
    
    if (empty($userId)) {
        return \Tina4\redirect("/login?error=Invalid request");
    }
    
    return $response(\Tina4\renderTemplate("auth/verify-2fa.twig", [
        "user_id" => $userId
    ]));
});

// Verify 2FA action
\Tina4\Post::add("/verify-2fa", function (\Tina4\Response $response, \Tina4\Request $request) {
    $userId = $request->params["user_id"] ?? "";
    $code = $request->params["code"] ?? "";
    
    if (empty($userId) || empty($code)) {
        return \Tina4\redirect("/login?error=Invalid request");
    }
    
    // Get the user
    $user = (new Users())->load("userId = ?", [$userId]);
    
    if (!$user) {
        return \Tina4\redirect("/login?error=User not found");
    }
    
    // Verify the code
    $authService = new \App\AuthService();
    if ($authService->verify2FACode($user->twofaSecret, $code)) {
        // Valid 2FA, set session
        $_SESSION["user_id"] = $user->userId;
        $_SESSION["username"] = $user->username;
        $_SESSION["company_id"] = $user->companyId;
        $_SESSION["role"] = $user->role ?? "user";
        
        return \Tina4\redirect("/landing-page");
    } else {
        return $response(\Tina4\renderTemplate("auth/verify-2fa.twig", [
            "user_id" => $userId,
            "error" => "Invalid verification code. Please try again."
        ]));
    }
});