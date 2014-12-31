<?php
include("znamesilo.class.php");
znamesilo::init();
$znamesilo->KEY = 'Enter your API key';
$znamesilo->debug = false;
$domains = $znamesilo->listDomains();
echo "<pre>".print_r($domains,1)."</pre>";
