<?php
/**
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

/* Include Horde's core.php file. */
require_once dirname(__FILE__) . '/lib/base.load.php';
include_once HORDE_BASE . '/lib/core.php';

/* We should have loaded the Horde_String class, from the Horde_Util
   package, in core.php. If Horde_String:: isn't defined, then we're not
   finding some critical libraries. */
if (!class_exists('Horde_String')) {
    echo '<br /><h2 style="color:red">The Horde_Util package was not found. If PHP\'s error_reporting setting is high enough and display_errors is on, there should be error messages printed above that may help you in debugging the problem. If you are simply missing these files, then you need to get the <a href="http://cvs.horde.org/cvs.php/framework">framework</a> module from <a href="http://www.horde.org/source/">Horde CVS</a>, and install the packages in it with the install-packages.php script.</h2>';
    exit;
}

/* Initialize the Horde_Test:: class. */
if (!is_readable(HORDE_BASE . '/lib/Test.php')) {
    echo 'ERROR: You must install Horde before running this script.';
    exit;
}
require_once HORDE_BASE . '/lib/Test.php';
$horde_test = new Horde_Test;

/* Chora version. */
$module = 'Chora';
require_once dirname(__FILE__) . '/lib/Application.php';
$app = new Chora_Application();
$module_version = $app->version;

/* Chora configuration files. */
$file_list = array(
    'config/conf.php' => 'The file <code>./config/conf.php</code> appears to be missing. You probably just forgot to generate it using the Horde config system - see docs/INSTALL for details. While you do that, take a look at the settings and make sure they are appropriate for your site.',
    'config/mime_drivers.php' => 'The file <code>./config/mime_drivers.php</code> appears to be missing. You probably just forgot to copy <code>./config/mime_drivers.php.dist</code> over. While you do that, take a look at the settings and make sure they are appropriate for your site.',
    'config/prefs.php' => 'The file <code>./config/prefs.php</code> appears to be missing. You probably just forgot to copy <code>./config/prefs.php.dist</code> over. While you do that, take a look at the settings and make sure they are appropriate for your site.',
    'config/sourceroots.php' => 'The file <code>./config/sourceroots.php</code> appears to be missing. You probably just forgot to copy <code>./config/sourceroots.php.dist</code> over. While you do that, take a look at the settings and make sure they are appropriate for your site.'
);

require TEST_TEMPLATES . 'header.inc';
require TEST_TEMPLATES . 'version.inc';

/* PHP module capabilities. */
$module_list = array();

/* Display versions of other Horde applications. */
$app_list = array(
    'horde' => array(
        'error' => 'Chora requires Horde 4.0 or greater to operate.',
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
$pear_list = array();

/* Get the status output now. */
$module_output = $horde_test->phpModuleCheck($module_list);

?>

<h1>Chora Configuration Files</h1>
<ul>
    <?php echo $horde_test->requiredFileCheck($file_list) ?>
</ul>

<h1>PEAR Modules</h1>
<ul>
    <?php echo $horde_test->PEARModuleCheck($pear_list) ?>
</ul>

<?php
require TEST_TEMPLATES . 'footer.inc';
