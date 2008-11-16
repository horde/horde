<?php
/**
 * @package Horde_Crypt
 */

require 'PEAR.php';
require 'Horde/Util.php';
require 'Horde/Browser.php';
require dirname(__FILE__) . '/../Crypt/pgp.php';

$_SERVER['HTTPS'] = 'on';
$browser = &Browser::singleton();

$pgp = new Horde_Crypt_pgp(array('program' => '/usr/bin/gpg',
                                 'temp' => Util::getTempDir()));

$pubkey = file_get_contents(dirname(__FILE__) . '/fixtures/pgp_public.asc');
$privkey = file_get_contents(dirname(__FILE__) . '/fixtures/pgp_private.asc');
