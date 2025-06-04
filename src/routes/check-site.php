<?php

/**
 * Check site monitoring status
 * This route checks the status of a monitored site based on the site ID and type ID.
 * It performs a ping or HTTP request to determine if the site is up or down,
 * and updates the monitoring status in the database.
 */
\Tina4\Post::add("/check-site", function (\Tina4\Response $response, \Tina4\Request $request) {
    \Tina4\Debug::message("Received form token: " . ($request->params["formToken"] ?? "none"));

    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    $siteId = (int)($request->params["site_id"] ?? 0);
    $typeId = (int)($request->params["type_id"] ?? 0);

    // Load the current user
    $user = (new Users())->load("user_id = ?", [$_SESSION["user_id"]]);
    $companyId = (int)$user->companyId;

    // Load the site using ORM
    $site = (new MonitoredSites())->load("site_id = ? AND company_id = ?", [$siteId, $companyId]);

    // Check if site exists and belongs to the user's company
    if (empty($site->siteId) || empty($site->url)) {
        return $response("Site not found or unauthorized", 403);
    }

    $url = $site->url;
    $status = "unknown";
    $httpCode = 0;

    \Tina4\Debug::message("Checking site ID: {$siteId} for user: {$user->username}");

    // Monitoring Logic
    try {
        if ($typeId === 1) { // Ping
            $host = parse_url($url, PHP_URL_HOST);
            $host = preg_replace('#^https?://#', '', $host);

            //$pingResult = exec("ping -n 1 -w 1000 " . escapeshellarg($host), $output, $returnVar);
            $pingCommand = PHP_OS_FAMILY === 'Windows'
                ? "ping -n 1 -w 1000 " . escapeshellarg($host)  // Windows format
                : "ping -c 2 -W 1 " . escapeshellarg($host);    // Linux format

            $pingResult = exec($pingCommand, $output, $returnVar);


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

    \Tina4\Debug::message("siteId: {$siteId}");
    \Tina4\Debug::message("typeId: {$typeId}");
    \Tina4\Debug::message("status: {$status}");
    \Tina4\Debug::message("timestamp: {$createdAt}");

    // Check if there's already a monitoring entry for this site and type
    $existingMonitoring = (new Monitoring())->load("site_id = ? AND type_id = ?", [$siteId, $typeId]);

    if (!empty($existingMonitoring->monitoringId)) {
        // Update existing record using ORM
        $existingMonitoring->status = $status;
        $existingMonitoring->createdAt = $createdAt;
        $existingMonitoring->save();
    } else {
        // Insert new record using ORM
        $monitoring = new Monitoring();
        $monitoring->siteId = $siteId;
        $monitoring->typeId = $typeId;
        $monitoring->status = $status;
        $monitoring->createdAt = $createdAt;
        $monitoring->save();
    }

    // Always insert into monitoring_history using ORM
    $history = new MonitoringHistory();
    $history->siteId = $siteId;
    $history->typeId = $typeId;
    $history->status = $status;
    $history->createdAt = $createdAt;
    $history->save();

    // Update the monitored_sites table using ORM
    $site->status = $status;
    $site->save();

    return \Tina4\redirect("/landing-page");
});
