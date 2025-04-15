<?php
class MonitoringTypes extends \Tina4\ORM
{
    public $tableName="monitoring_types";
    public $primaryKey="typeId"; //set for primary key
    //public $fieldMapping = ["typeId" => "type_id","typeName" => "type_name"];
    //public $genPrimaryKey=false; //set to true if you want to set the primary key
    //public $ignoreFields = []; //fields to ignore in CRUD
    //public $softDelete=true; //uncomment for soft deletes in crud 
    
	public $typeId;
	public $typeName;
}