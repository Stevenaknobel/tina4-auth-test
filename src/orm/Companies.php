<?php
class Companies extends \Tina4\ORM
{
    public $tableName="companies";
    public $primaryKey="companyId"; //set for primary key
    public $fieldMapping = [
        "companyId" => "company_id",
        "companyName" => "company_name",
        "createdAt" => "created_at",
        "slackWebhookUrl" => "slack_webhook_url",
        "notificationEmail" => "notification_email",
        "notificationsEnabled" => "notifications_enabled",
        "domains" => "domains",
        "logoUrl" => "logo_url",
        "primaryColor" => "primary_color",
        "secondaryColor" => "secondary_color",
        "customCss" => "custom_css"
    ];
    //public $genPrimaryKey=false; //set to true if you want to set the primary key
    //public $ignoreFields = []; //fields to ignore in CRUD
    //public $softDelete=true; //uncomment for soft deletes in crud 

	public $companyId;
	public $companyName;
	public $createdAt;
	public $slackWebhookUrl;
	public $notificationEmail;
	public $notificationsEnabled;
	public $domains;
	public $logoUrl;
	public $primaryColor;
	public $secondaryColor;
	public $customCss;
}
