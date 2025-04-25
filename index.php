<?php


// Set the time zone to South Africa Standard Time (UTC+2)
date_default_timezone_set('Africa/Johannesburg');

require_once "./vendor/autoload.php";
require_once "./src/app/AuthHelper.php";


global $DBA;

$DBA = new \Tina4\DataMySQL($_ENV["DB_HOST"] . ":" . $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASS"], "d-m-Y");

$config = new \Tina4\Config(static function (\Tina4\Config $config){
  //Your own config initializations 
});

//$config->setAuthentication(new AuthHelper()); // this is where you register the custom Auth class

\tina4\Initialize();
echo new \Tina4\Tina4Php($config);