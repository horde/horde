<?php
/**
 * Simple Scribe logging example
 *
 * @category Horde
 * @package  Scribe
 */

require 'Horde/Autoloader.php';

$scribe = new Horde_Scribe_Client();
$scribe->connect('localhost', 1463);
$scribe->log('keyword', 'This is a message for the keyword category');
