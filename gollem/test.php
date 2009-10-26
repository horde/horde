<?php
/**
 * Gollem test script.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

/* Include Horde's core.php file. */
require_once dirname(__FILE__) . '/lib/base.load.php';
require_once '../lib/core.php';

/* We should have loaded the String class, from the Horde_Util
   package, in core.php. If Horde_String:: isn't defined, then we're not
   finding some critical libraries. */
if (!class_exists('String')) {
    echo '<br /><h2 style="color:red">The Horde_Util package was not found. If PHP\'s error_reporting setting is high enough and display_errors is on, there should be error messages printed above that may help you in debugging the problem. If you are simply missing these files, then you need to get the <a href="http://cvs.horde.org/cvs.php/framework">framework</a> module from <a href="http://www.horde.org/source/">Horde CVS</a>, and install the packages in it with the install-packages.php script.</h2>';
    exit;
}

/* Initialize the Horde_Test:: class. */
if (!is_readable(HORDE_BASE . '/lib/Test.php')) {
    echo 'ERROR: You must install Horde before running this script.';
    exit;
}

require_once HORDE_BASE . '/lib/Test.php';
$horde_test = new Horde_Test();

/* Gollem version. */
$module = 'Gollem';
require_once dirname(__FILE__) . '/lib/Application.php';
$app = new Gollem_Application();
$module_version = $app->version;

/* Gollem configuration files. */
$file_list = array(
    'config/backends.php' => 'The file <code>./config/backends.php</code> appears to be missing. You probably just forgot to copy <code>./config/backends.php.dist</code> over. While you do that, take a look at the settings and make sure they are appropriate for your site.',
    'config/credentials.php' => 'The file <code>./config/credentials.php</code> appears to be missing. You probably just forgot to copy <code>./config/credentials.php.dist</code> over. While you do that, take a look at the settings and make sure they are appropriate for your site.',
    'config/conf.php' => 'The file <code>./config/conf.php</code> appears to be missing. You probably just forgot to generate it using the Horde config system - see docs/INSTALL for details. While you do that, take a look at the settings and make sure they are appropriate for your site.',
    'config/mime_drivers.php' => 'The file <code>./config/mime_drivers.php</code> appears to be missing. You probably just forgot to copy <code>./config/mime_drivers.php.dist</code> over. While you do that, take a look at the settings and make sure they are appropriate for your site.',
    'config/prefs.php' => 'The file <code>./config/prefs.php</code> appears to be missing. You probably just forgot to copy <code>./config/prefs.php.dist</code> over. While you do that, take a look at the settings and make sure they are appropriate for your site.'
);

require TEST_TEMPLATES . 'header.inc';
require TEST_TEMPLATES . 'version.inc';

/* PHP module capabilities. */
$module_list = array(
    'ftp' => array(
        'descrip' => 'FTP Support',
        'error' => 'You need FTP support compiled into PHP if you plan to use the FTP VFS driver (see config/backends.php).'
    ),
    'ssh2' => array(
        'descrip' => 'SSH2 Support',
        'error' => 'You need the SSH2 PECL module if you plan to use the SSH2 VFS driver (see config/backends.php).'
    ),
);

/* Display versions of other Horde applications. */
$app_list = array(
    'horde' => array(
        'error' => 'Gollem requires Horde 4.0 or greater to operate.',
        'version' => '4.0'
    ),
);

?>
<h1>Other Horde Applications</h1>
<ul>
    <?php echo $horde_test->requiredAppCheck($app_list) ?>
</ul>
<?php

/* Display PHP Version information. */
$php_info = $horde_test->getPhpVersionInformation();
require TEST_TEMPLATES . 'php_version.inc';

/* PEAR */
$pear_list = array(
    'HTTP_WebDAV_Server' => array(
        'path' => 'HTTP/WebDAV/Server.php',
        'error' => 'You do not have the HTTP_WebDAV_Server package installed on your system. This module is required to use browse the VFS using WebDAV.  See the INSTALL file for instructions on how to install the package.'    ),
);

/* Get the status output now. */
$module_output = $horde_test->phpModuleCheck($module_list);

/* Check for VFS Quota support. */
$quota_check = class_exists('VFS');
if ($quota_check === false) {
    $quota_output = '<font color="orange"><strong>Could not load VFS library to check for VFS Quota support.</strong></font>';
} else {
    $quota_output = '<font color="green"><strong>VFS library supports quota.</strong></font>';
}

?>
<h1>PHP Module Capabilities</h1>
<ul>
    <?php echo $module_output ?>
</ul>

<h1>Gollem Configuration Files</h1>
<ul>
    <?php echo $horde_test->requiredFileCheck($file_list) ?>
</ul>

<h1>Gollem VFS Support</h1>
<ul>
    <li><?php echo $quota_output ?></li>
</ul>

<h1>PEAR Modules</h1>
<ul>
    <?php echo $horde_test->PEARModuleCheck($pear_list) ?>
</ul>

<?php
require TEST_TEMPLATES . 'footer.inc';
