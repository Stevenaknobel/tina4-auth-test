<?php

\Tina4\Post::add("/check-site", function (\Tina4\Response $response, \Tina4\Request $request) {
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    global $DBA;

    $siteId = (int)($request->params["site_id"] ?? 0);
    $typeId = (int)($request->params["type_id"] ?? 0);

    $user = (new Users())->load("user_id = ?", [$_SESSION["user_id"]]);
    $companyId = (int)$user->companyId;

    // Validate the site
    $siteResults = $DBA->fetch(
        "SELECT * FROM monitored_sites WHERE site_id = {$siteId} AND company_id = {$companyId}",
        1
    )->asArray();

    $site = $siteResults[0] ?? null;

    if (!$site || !isset($site["url"])) {
        return $response("Site not found or unauthorized", 403);
    }

    $url = $site["url"];
    $status = "unknown";
    $httpCode = 0;

    \Tina4\Debug::message("Checking site ID: {$siteId} for user: {$user->username}");

    // Monitoring Logic
    try {
        if ($typeId === 1) { // Ping
            $host = parse_url($url, PHP_URL_HOST);
            $pingResult = exec("ping -c 1 -W 1 " . escapeshellarg($host), $output, $returnVar);
            $status = ($returnVar === 0) ? "up" : "down";
        } else {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            if ($typeId === 3) { // POST
                curl_setopt($ch, CURLOPT_POST, true);
            } elseif ($typeId === 2) { // GET
                curl_setopt($ch, CURLOPT_HTTPGET, true);
            } else {
                throw new Exception("Unsupported type_id: {$typeId}");
            }

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $status = ($httpCode >= 200 && $httpCode < 400) ? "up" : "down";
            curl_close($ch);
        }
    } catch (Exception $e) {
        $status = "error";
        \Tina4\Debug::message("Monitoring check failed: " . $e->getMessage());
    }

    // Save the monitoring result
    $createdAt = date("Y-m-d H:i:s");
    $monitoringData = [
        $siteId,
        $typeId,
        $status,
        $createdAt
    ];

    \Tina4\Debug::message("Insert values: " . json_encode($monitoringData));
    \Tina4\Debug::message("siteId: {$siteId}");
    \Tina4\Debug::message("typeId: {$typeId}");
    \Tina4\Debug::message("status: {$status}");
    \Tina4\Debug::message("timestamp: {$createdAt}");
    \Tina4\Debug::message("Monitoring Data: " . var_export($monitoringData, true));

// Check if there's already a monitoring entry for this site and type
$existingMonitoring = $DBA->fetch(
    "SELECT monitoring_id FROM monitoring WHERE site_id = {$siteId} AND type_id = {$typeId} LIMIT 1",
    1
)->asArray();

if (!empty($existingMonitoring)) {
    // Update existing record, if one is already created
    $DBA->exec(
        "UPDATE monitoring SET status = ?, created_at = ? WHERE site_id = ? AND type_id = ?",
        $status, $createdAt, $siteId, $typeId
    );
} else {
    // Insert new record only if one doesn't exist
    $DBA->exec(
        "INSERT INTO monitoring (site_id, type_id, status, created_at) 
         VALUES (?, ?, ?, ?)",
        $siteId, $typeId, $status, $createdAt
    );
}

// Always insert into monitoring_history
$DBA->exec(
    "INSERT INTO monitoring_history (site_id, type_id, status, created_at)
     VALUES (?, ?, ?, ?)",
    $siteId, $typeId, $status, $createdAt
);

// Update the monitored_sites table
$DBA->exec(
    "UPDATE monitored_sites SET status = ? WHERE site_id = ?",
    $status, $siteId
);

    return \Tina4\redirect("/landing-page");
});
