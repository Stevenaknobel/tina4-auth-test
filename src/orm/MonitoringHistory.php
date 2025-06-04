<?php
class MonitoringHistory extends \Tina4\ORM
{
    public $tableName="monitoring_history";
    public $primaryKey="historyId"; //set for primary key
    public $fieldMapping = ["historyId" => "history_id","siteId" => "site_id","typeId" => "type_id","status" => "status","createdAt" => "created_at"];
    //public $genPrimaryKey=false; //set to true if you want to set the primary key
    //public $ignoreFields = []; //fields to ignore in CRUD
    //public $softDelete=true; //uncomment for soft deletes in crud 

	public $historyId;
	public $siteId;
	public $typeId;
	public $status;
	public $createdAt;
}
