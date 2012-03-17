<?php
/** 
 * Most basic usage example for Horde_Autoloader
 * Load the default Horde Autoloader without any additional parameters
 * In default configuration, the Autoloader tries to load 
 * My/Class/Name.php when My_Class_Name is first accessed
 * No require_once statement is needed.
 * */
require_once 'Horde/Autoloader/Default.php';

/**
 * Now you can use any class from source files
 * Example: Use Horde_Date without first loading the class file manually
 */

$dt = new Horde_Date(time());
print $dt->toIcalendar();
