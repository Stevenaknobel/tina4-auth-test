<?php
class Tokens extends \Tina4\ORM
{
    public $tableName="tokens";
    public $primaryKey="tokenId"; //set for primary key
    //public $fieldMapping = ["tokenId" => "token_id","userId" => "user_id","token" => "token","createdAt" => "created_at"];
    //public $genPrimaryKey=false; //set to true if you want to set the primary key
    //public $ignoreFields = []; //fields to ignore in CRUD
    //public $softDelete=true; //uncomment for soft deletes in crud 
    
	public $tokenId;
	public $userId;
	public $token;
	public $createdAt;
}