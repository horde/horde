<?php
/**
 * @package Horde_Crypt
 */

require 'PEAR.php';
require 'Horde/String.php';
require 'Horde/Util.php';
require dirname(__FILE__) . '/../Crypt/smime.php';

$smime = new Horde_Crypt_smime(array('temp' => Util::getTempDir()));
