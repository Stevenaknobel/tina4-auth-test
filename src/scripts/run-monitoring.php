<?php

// Set timezone
date_default_timezone_set('Africa/Johannesburg');

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

global $DBA;
$DBA = new \Tina4\DataMySQL(
    $_ENV["DB_HOST"] . ":" . $_ENV["DB_NAME"],
    $_ENV["DB_USER"],
    $_ENV["DB_PASS"],
    "d-m-Y"
);

// Run the scheduled monitoring
runScheduledMonitoring();

// Clean history older than 30 days
$DBA->exec("DELETE FROM monitoring_history WHERE created_at < NOW() - INTERVAL 30 DAY");

/**
 * Send a notification to Slack
 * 
 * @param string $webhookUrl The Slack webhook URL
 * @param string $message The message to send
 * @return bool True if successful, false otherwise
 */
function sendSlackNotification(string $webhookUrl, string $message): bool
{
    if (empty($webhookUrl)) {
        return false;
    }

    $data = json_encode([
        'text' => $message
    ]);

    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300;
}

/**
 * Send an email notification
 * 
 * @param string $to The recipient email address
 * @param string $subject The email subject
 * @param string $message The email message
 * @return bool True if successful, false otherwise
 */
function sendEmailNotification(string $to, string $subject, string $message): bool
{
    if (empty($to)) {
        return false;
    }

    // Use Tina4's built-in email functionality
    $email = new \Tina4\Email();
    $email->to = $to;
    $email->subject = $subject;
    $email->message = $message;
    $email->isHtml = true;

    return $email->send();
}

/**
 * Send notifications for a site status change
 * 
 * @param array $site The site data
 * @param string $status The new status
 * @param string $previousStatus The previous status
 * @return void
 */
function sendNotifications(array $site, string $status, string $previousStatus): void
{
    global $DBA;

    // Only send notifications if status changed to down
    if ($status !== 'down' || $previousStatus === 'down') {
        return;
    }

    // Get company information
    $companyId = (int)$site['company_id'];
    $company = $DBA->fetch(
        "SELECT * FROM companies WHERE company_id = ?",
        [$companyId],
        1
    )->asArray();

    if (empty($company) || !$company[0]['notifications_enabled']) {
        return;
    }

    $company = $company[0];
    $siteName = $site['site_name'];
    $url = $site['url'];
    $timestamp = date('Y-m-d H:i:s');

    // Prepare notification message
    $message = "🔴 ALERT: {$siteName} is DOWN!\n";
    $message .= "URL: {$url}\n";
    $message .= "Time: {$timestamp}\n";
    $message .= "Company: {$company['company_name']}\n";

    // Send Slack notification
    if (!empty($company['slack_webhook_url'])) {
        sendSlackNotification($company['slack_webhook_url'], $message);
    }

    // Send email notification
    if (!empty($company['notification_email'])) {
        $subject = "🔴 ALERT: {$siteName} is DOWN!";
        $htmlMessage = "<h2>🔴 ALERT: Site is DOWN!</h2>";
        $htmlMessage .= "<p><strong>Site:</strong> {$siteName}</p>";
        $htmlMessage .= "<p><strong>URL:</strong> <a href='{$url}'>{$url}</a></p>";
        $htmlMessage .= "<p><strong>Time:</strong> {$timestamp}</p>";
        $htmlMessage .= "<p><strong>Company:</strong> {$company['company_name']}</p>";

        sendEmailNotification($company['notification_email'], $subject, $htmlMessage);
    }
}

function runScheduledMonitoring(): void
{
    global $DBA;

    // Get all monitored sites
    $monitoredSites = $DBA->fetch("SELECT * FROM monitored_sites")->asArray();

    foreach ($monitoredSites as $site) {
        $siteId = (int)$site["site_id"];
        $typeId = (int)$site["type_id"];
        $url = $site["url"];
        $previousStatus = $site["status"];

        $status = "unknown";
        $httpCode = 0;
        $responseBody = "";
        $createdAt = date("Y-m-d H:i:s");

        // Get monitoring configuration if it exists
        $config = $DBA->fetch(
            "SELECT * FROM monitoring_config WHERE site_id = ?",
            [$siteId],
            1
        )->asArray();

        $config = !empty($config) ? $config[0] : null;

        try {
            if ($typeId === 1) { // Ping
                $host = parse_url($url, PHP_URL_HOST);
                $pingResult = exec("ping -n 1 -w 1000 " . escapeshellarg($host), $output, $returnVar);
                $status = ($returnVar === 0) ? "up" : "down";
            } else {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                // Set request headers if provided
                if ($config && !empty($config["request_headers"])) {
                    $headers = json_decode($config["request_headers"], true);
                    if (is_array($headers)) {
                        $headerArray = [];
                        foreach ($headers as $key => $value) {
                            $headerArray[] = "{$key}: {$value}";
                        }
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
                    }
                }

                if ($typeId === 3) { // POST
                    curl_setopt($ch, CURLOPT_POST, true);

                    // Set request body if provided
                    if ($config && !empty($config["request_body"])) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $config["request_body"]);
                    }
                } elseif ($typeId === 2) { // GET
                    curl_setopt($ch, CURLOPT_HTTPGET, true);
                } elseif ($typeId === 4) { // HTTP
                    curl_setopt($ch, CURLOPT_HTTPGET, true);
                } else {
                    throw new Exception("Unsupported type_id: {$typeId}");
                }

                $responseBody = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                // Check if we have specific expectations for this request
                if ($typeId === 4) { // HTTP type - specifically check for 200 status code
                    $status = ($httpCode === 200) ? "up" : "down";
                } else if ($config) {
                    // Check expected status code if provided
                    if (!empty($config["expected_status_code"])) {
                        $expectedCode = (int)$config["expected_status_code"];
                        $status = ($httpCode === $expectedCode) ? "up" : "down";
                    } else {
                        // Default check for 2xx or 3xx status
                        $status = ($httpCode >= 200 && $httpCode < 400) ? "up" : "down";
                    }

                    // Check expected response content if provided
                    if (!empty($config["expected_response"]) && $status === "up") {
                        $expectedContent = $config["expected_response"];
                        if (strpos($responseBody, $expectedContent) === false) {
                            $status = "down"; // Expected content not found
                        }
                    }
                } else {
                    // Default check for 2xx or 3xx status
                    $status = ($httpCode >= 200 && $httpCode < 400) ? "up" : "down";
                }

                curl_close($ch);
            }
        } catch (Exception $e) {
            $status = "error";
        }

        // Check if there's an existing entry in monitoring
        $existingMonitoring = $DBA->fetch(
            "SELECT monitoring_id FROM monitoring WHERE site_id = ? AND type_id = ? LIMIT 1",
            [$siteId, $typeId],
            1
        )->asArray();

        if (!empty($existingMonitoring)) {
            // Update existing
            $DBA->exec(
                "UPDATE monitoring SET status = ?, created_at = ? WHERE site_id = ? AND type_id = ?",
                $status, $createdAt, $siteId, $typeId
            );
        } else {
            // Insert new
            $DBA->exec(
                "INSERT INTO monitoring (site_id, type_id, status, created_at) VALUES (?, ?, ?, ?)",
                $siteId, $typeId, $status, $createdAt
            );
        }

        // Always insert into monitoring_history
        $DBA->exec(
            "INSERT INTO monitoring_history (site_id, type_id, status, created_at) VALUES (?, ?, ?, ?)",
            $siteId, $typeId, $status, $createdAt
        );

        // Update monitored_sites table
        $DBA->exec(
            "UPDATE monitored_sites SET status = ? WHERE site_id = ?",
            $status, $siteId
        );

        // Send notifications if status changed
        if ($status !== $previousStatus) {
            sendNotifications($site, $status, $previousStatus);
        }
    }

    echo "Monitoring complete at " . date("Y-m-d H:i:s") . "\n";
}
