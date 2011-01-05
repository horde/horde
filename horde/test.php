<?php
/**
 * Horde test script.
 *
 * Parameters:
 * -----------
 * 'app' - (string) The app to test.
 *         DEFAULT: horde
 * 'mode' - (string) TODO
 * 'url' - (string) TODO
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Brent J. Nordquist <bjn@horde.org>
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael Slusarz <slusarz@horde.org>
 */

/* Function to output fatal error message. */
function _hordeTestError($msg)
{
    exit('<html><head><title>ERROR</title></head><body><h3 style="color:red">' . htmlspecialchars($msg) . '</h3></body></html>');
}

/* If we can't find the Autoloader, then the framework is not setup. A user
 * must at least correctly install the framework. */
ini_set('include_path', dirname(__FILE__) . '/lib' . PATH_SEPARATOR . ini_get('include_path'));
if (file_exists(dirname(__FILE__) . '/config/horde.local.php')) {
    include dirname(__FILE__) . '/config/horde.local.php';
}
if (!@include_once 'Horde/Autoloader.php') {
    _hordeTestError(sprintf('Could not find Horde\'s framework libraries in the following path(s): %s. Please read horde/docs/INSTALL for information on how to install these libraries.', get_include_path()));
}

/* Similarly, registry.php needs to exist. */
if (!file_exists(dirname(__FILE__) . '/config/registry.php')) {
    _hordeTestError('Could not find horde/config/registry.php. Please make sure this file exists. Read horde/docs/INSTALL for further information.');
}

require_once dirname(__FILE__) . '/lib/Application.php';
try {
    Horde_Registry::appInit('horde', array(
        'authentication' => 'none',
        'test' => true
    ));
    $init_exception = null;
} catch (Exception $e) {
    define('HORDE_TEMPLATES', dirname(__FILE__) . '/templates');
    $init_exception = $e;
}

if (!empty($conf['testdisable'])) {
    _hordeTestError('Horde test scripts have been disabled in the local configuration. To enable, change the \'testdisable\' setting in horde/config/conf.php to false.');
}

/* We should have loaded the String class, from the Horde_Util package. If it
 * isn't defined, then we're not finding some critical libraries. */
if (!class_exists('Horde_String')) {
    _hordeTestError('Required Horde libraries were not found. If PHP\'s error_reporting setting is high enough and display_errors is on, there should be error messages printed above that may help you in debugging the problem. If you are simply missing these files, then you need to install the framework module.');
}

/* Initialize the Horde_Test:: class. */
if (!class_exists('Horde_Test')) {
    /* Try and provide enough information to debug the missing file. */
    _hordeTestError('Unable to find the Horde_Test library. Your Horde installation may be missing critical files, or PHP may not have sufficient permissions to include files. There may be error messages printed above this message that will help you in debugging the problem.');
}

/* Load the application. */
$app = Horde_Util::getFormData('app', 'horde');
$app_name = $registry->get('name', $app);
$app_version = $registry->getVersion($app);

/* If we've gotten this far, we should have found enough of Horde to run
 * tests. Create the testing object. */
if ($app != 'horde') {
    try {
        $registry->pushApp($app, array('check_perms' => false));
    } catch (Exception $e) {
        _hordeTestError($e->getMessage());
    }
}
$classname = ucfirst($app) . '_Test';
if (!class_exists($classname)) {
    _hordeTestError('No tests found for ' . ucfirst($app) . ' [' . $app_name . '].');
}
$test_ob = new $classname();

/* Register a session. */
if ($session && !$session->exists('horde', 'test_count')) {
    $session->set('horde', 'test_count', 0);
}

/* Template location. */
$test_templates = HORDE_TEMPLATES . '/test';

/* Self URL. */
$url = Horde::url('test.php', false, array('app' => 'horde'));
$self_url = $url->copy()->add('app', $app);

/* Handle special modes. */
switch (Horde_Util::getGet('mode')) {
case 'extensions':
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">';
    $ext_get = Horde_Util::getGet('ext');
    require $test_templates . '/extensions.inc';
    exit;

case 'phpinfo':
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">';
    echo '<a href="' . htmlspecialchars($self_url) . '">&lt;&lt; Back to test.php</a>';
    phpinfo();
    exit;

case 'unregister':
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">';
    $session->remove('horde', 'test_count');
?>
<html>
 <body>
 The test session has been unregistered.<br />
 <a href="$self_url">Go back</a> to the test.php page.<br />
 </body>
</html>
<?php
    exit;
}

/* Get the status output now. */
$pear_output = $test_ob->pearModuleCheck();
Horde::startBuffer();
require $test_templates . '/header.inc';
require $test_templates . '/version.inc';

if ($app == 'horde') {
?>
<h1>Horde Applications</h1>
<ul>
<?php
    /* Get Horde module version information. */
    if (!$init_exception) {
        try {
            $app_list = array_diff($registry->listAllApps(), array($app));
            sort($app_list);
            foreach ($app_list as $val) {
                echo '<li>' . ucfirst($val);
                if ($name = $registry->get('name', $val)) {
                    echo ' [' . $name . ']';
                }
                echo ': ' . $registry->getVersion($val) .
                    ' (<a href="' . $url->copy()->add('app', $val) . "\">run tests</a>)</li>\n";
            }
        } catch (Exception $e) {
            $init_exception = $e;
        }
    }

    if ($init_exception) {
        echo '<li style="color:red"><strong>Horde is not correctly configured so no application information can be displayed. Please follow the instructions in horde/docs/INSTALL and ensure horde/config/conf.php and horde/config/registry.php are correctly configured.</strong></li>' .
            '<li><strong>Error:</strong> ' . $e->getMessage() . '</li>';
    }
?>
</ul>
<?php
} elseif ($output = $test_ob->requiredAppCheck()) {
?>
<h1>Other Horde Applications</h1>
<ul>
 <?php echo $output ?>
</ul>
<?php
}

/* Display PHP Version information. */
$php_info = $test_ob->getPhpVersionInformation();
require $test_templates . '/php_version.inc';

if ($module_output = $test_ob->phpModuleCheck()) {
?>
<h1>PHP Module Capabilities</h1>
<ul>
 <?php echo $module_output ?>
</ul>
<?php
}

if ($setting_output = $test_ob->phpSettingCheck()) {
?>
<h1>Miscellaneous PHP Settings</h1>
<ul>
 <?php echo $setting_output ?>
</ul>
<?php
}

if ($config_output = $test_ob->requiredFileCheck()) {
?>
<h1>Required Configuration Files</h1>
<ul>
    <?php echo $config_output ?>
</ul>
<?php
}
?>

<h1>PHP Sessions</h1>
<ul>
<?php if (!$init_exception): ?>
 <li>Session counter: <?php $tc = $session->get('horde', 'test_count'); echo ++$tc; $session->set('horde', 'test_count', $tc); ?> [refresh the page to increment the counter]</li>
 <li>To unregister the session: <a href="<?php $self_url->copy()->add('mode', 'unregister') ?>">click here</a></li>
<?php else: ?>
 <li style="color:red"><strong>The PHP session test is disabled until Horde is correctly configured.</strong></li>
<?php endif; ?>
</ul>

<h1>PEAR</h1>
<ul>
    <?php echo $pear_output ?>
</ul>

<?php

/* Do application specifc tests now. */
echo $test_ob->appTests();

require $test_templates . '/footer.inc';
echo Horde::endBuffer();
