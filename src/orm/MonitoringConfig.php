<?php
class MonitoringConfig extends \Tina4\ORM
{
    public $tableName = "monitoring_config";
    public $primaryKey = "configId"; //set for primary key
    public $fieldMapping = [
        "configId" => "config_id",
        "siteId" => "site_id",
        "requestHeaders" => "request_headers",
        "requestBody" => "request_body",
        "expectedResponse" => "expected_response",
        "expectedStatusCode" => "expected_status_code"
    ];
    
    public $configId;
    public $siteId;
    public $requestHeaders;
    public $requestBody;
    public $expectedResponse;
    public $expectedStatusCode;
}