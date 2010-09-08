<?php
/**
 * @category Horde
 * @package  Crypt
 */

require 'PEAR.php';
require 'Horde/String.php';
require 'Horde/Util.php';
require dirname(__FILE__) . '/../../../lib/Horde/Crypt.php';

$smime = Horde_Crypt::factory('Smime', array('temp' => Horde_Util::getTempDir()));
