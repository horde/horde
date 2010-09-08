<?php
/**
 * @category Horde
 * @package  Crypt
 */

$filedir = dirname(__FILE__);

require 'PEAR.php';
require 'Horde/Util.php';
require 'Horde/Browser.php';
require $filedir . '/../../../lib/Horde/Crypt.php';

$_SERVER['HTTPS'] = 'on';
$browser = &Browser::singleton();

$pgp = Horde_Crypt::factory('Pgp', array(
    'program' => '/usr/bin/gpg',
    'temp' => Horde_Util::getTempDir()
));

$pubkey = file_get_contents($filedir . '/fixtures/pgp_public.asc');
$privkey = file_get_contents($filedir . '/fixtures/pgp_private.asc');
