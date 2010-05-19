<?php
/**
 * $Horde: passwd/test.php,v 1.2.2.7 2009/06/10 08:20:38 jan Exp $
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/gpl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

/* Include Horde's core.php file. */
include_once '../lib/core.php';

/* We should have loaded the String class, from the Horde_Util
 * package, in core.php. If String:: isn't defined, then we're not
 * finding some critical libraries. */
if (!class_exists('String')) {
    echo '<br /><h2 style="color:red">The Horde_Util package was not found. If PHP\'s error_reporting setting is high enough and display_errors is on, there should be error messages printed above that may help you in debugging the problem. If you are simply missing these files, then you need to get the <a href="http://cvs.horde.org/cvs.php/framework">framework</a> module from <a href="http://www.horde.org/source/">Horde CVS</a>, and install the packages in it with the install-packages.php script.</h2>';
    exit;
}

/* Initialize the Horde_Test:: class. */
if (!is_readable('../lib/Test.php')) {
    echo 'ERROR: You must install Horde before running this script.';
    exit;
}
require_once '../lib/Test.php';
$horde_test = new Horde_Test;

/* Accounts definitions. */
$module = 'Passwd';
require_once './lib/version.php';
$module_version = PASSWD_VERSION;

require TEST_TEMPLATES . 'header.inc';
require TEST_TEMPLATES . 'version.inc';

/* PHP module capabilities. */
$module_list = array(
    'ctype' => 'Ctype Support',
    'ldap' => array(
        'descrip' => 'LDAP Support',
        'error' => 'If you will be using the any of the LDAP drivers for password changes, PHP must have ldap support. Compile PHP <code>--with-ldap</code> before continuing.'
    ),
    'mcrypt' => array(
        'descrip' => 'Mcrypt Support',
        'error' => 'If you will be using the smbldap driver for password changes, PHP must have mcrypt support. Compile PHP <code>--with-mcrypt</code> before continuing.'
    ),
    'mhash' => array(
        'descrip' => 'Mhash Support',
        'error' => 'If you will be using the smbldap driver for password changes, PHP must have mhash support. Compile PHP <code>--with-mhash</code> before continuing.'
    ),
    'soap' => array(
        'descrip' => 'SOAP Support',
        'error' => 'If you will be using the SOAP driver for password changes, PHP must have soap support. Compile PHP with <code>--enable-soap</code> before continuing.'
    ),
);


/* PEAR */
$pear_list = array(
    'Crypt_CHAP' => array(
        'path' => 'Crypt/CHAP.php',
        'error' => 'If you will be using the smbldap driver for password changes, then you must install the PEAR Crypt_CHAP module.'
    ),
    'HTTP_Request' => array(
        'path' => 'HTTP/Request.php',
        'error' => 'If you will be using the http driver for password changes, then you must install the PEAR HTTP_Request module.'
    )
);

/* Get the status output now. */
$module_output = $horde_test->phpModuleCheck($module_list);

?>
<h1>PHP Module Capabilities</h1>
<ul>
    <?php echo $module_output ?>
</ul>

<h1>PEAR Modules</h1>
<ul>
    <?php echo $horde_test->PEARModuleCheck($pear_list) ?>
</ul>

<?php
require TEST_TEMPLATES . 'footer.inc';
