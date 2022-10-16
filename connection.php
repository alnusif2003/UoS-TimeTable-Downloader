<?php


$dbhost = "DB_HOST";
$dbuser = "DB_USER";
$dbpass = "DB_PASS";
$dbname = "DB_NAME";

if (!$con = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname)) {

    die("<div class=\"alert alert-danger container\" role=\"alert\">
            Error. Connection to database failed. Reason: " . mysqli_connect_error() . "
        </div>");
}
