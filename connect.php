<?php 
// DB credentials.
$localhost = "mysql.sqlpub.com";
$username = "rvdluser";
$password = "267fada409e354c4";
$dbname = "RVDL";
// db connection
$connect = new mysqli($localhost, $username, $password, $dbname);
// check connection
if($connect->connect_error) {
  die("Connection Failed : " . $connect->connect_error);
} else {
  //echo "Successfully connected";
}
?>

