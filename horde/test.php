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
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Brent J. Nordquist <bjn@horde.org>
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael Slusarz <slusarz@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$api = new Horde_Application(array('authentication' => 'none'));

/* We should have loaded the String class, from the Horde_Util package. If it
 * isn't defined, then we're not finding some critical libraries. */
if (!class_exists('Horde_String')) {
    echo '<br /><h2 style="color:red">Required Horde libraries were not found. If PHP\'s error_reporting setting is high enough and display_errors is on, there should be error messages printed above that may help you in debugging the problem. If you are simply missing these files, then you need to install the framework module.</h2>';
    exit;
}

/* Initialize the Horde_Test:: class. */
if (!class_exists('Horde_Test')) {
    /* Try and provide enough information to debug the missing file. */
    echo '<br /><h2 style="color:red">Unable to find the Horde_Test library. Your Horde installation may be missing critical files, or PHP may not have sufficient permissions to include files. There may be error messages printed above this message that will help you in debugging the problem.</h2>';
    exit;
}

/* Load the application. */
$app = Horde_Util::getFormData('app', 'horde');
$app_name = $registry->get('name', $app);
$app_version = $registry->getVersion($app);

/* If we've gotten this far, we should have found enough of Horde to run
 * tests. Create the testing object. */
if ($app != 'horde') {
    $registry->pushApp($app, array('check_perms' => false));
}
$classname = ucfirst($app) . '_Test';
if (!class_exists($classname)) {
    echo '<h2 style="color:red">No tests found for ' . $app . ' [' . $app_name . '].</h2>';
    exit;
}
$test_ob = new $classname();

/* Register a session. */
if (!isset($_SESSION['horde_test_count'])) {
    $_SESSION['horde_test_count'] = 0;
}

/* Template location. */
$test_templates = HORDE_TEMPLATES . '/test';

/* Self URL. */
$url = Horde::selfUrl();
$self_url = $url->copy()->add('app', $app);

/* Handle special modes. */
switch (Horde_Util::getGet('mode')) {
case 'extensions':
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">';
    require $test_templates . '/extensions.inc';
    exit;

case 'phpinfo':
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">';
    echo '<a href="' . htmlspecialchars($self_url) . '">&lt;&lt; Back to test.php</a>';
    phpinfo();
    exit;

case 'unregister':
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">';
    unset($_SESSION['horde_test_count']);
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
$module_output = $test_ob->phpModuleCheck();
$setting_output = $test_ob->phpSettingCheck();
$pear_output = $test_ob->pearModuleCheck();
$config_output = $test_ob->requiredFileCheck();

require $test_templates . '/header.inc';
require $test_templates . '/version.inc';

if ($app == 'horde') {
?>
<h1>Horde Applications</h1>
<ul>
<?php

    /* Get Horde module version information. */
    $app_list = $registry->listApps(null, true);
    unset($app_list[$app]);
    ksort($app_list);
    foreach (array_keys($app_list) as $val) {
        echo '<li>' . ucfirst($val) . ' [' . $registry->get('name', $val) . ']: ' . $registry->getVersion($val) .
            ' (<a href="' . $url->copy()->add('app', $val) . "\">run tests</a>)</li>\n";
    }

?>
</ul>
<?php
} else {
?>
<h1>Other Horde Applications</h1>
<ul>
 <?php echo $test_ob->requiredAppCheck(); ?>
</ul>
<?php
}

/* Display PHP Version information. */
$php_info = $test_ob->getPhpVersionInformation();
require $test_templates . '/php_version.inc';

?>

<h1>PHP Module Capabilities</h1>
<ul>
 <?php echo $module_output ?>
</ul>

<h1>Miscellaneous PHP Settings</h1>
<ul>
 <?php echo $setting_output ?>
</ul>

<h1>Required Horde Configuration Files</h1>
<ul>
    <?php echo $config_output ?>
</ul>

<h1>PHP Sessions</h1>
<?php $_SESSION['horde_test_count']++; ?>
<ul>
 <li>Session counter: <?php echo $_SESSION['horde_test_count'] ?> [refresh the page to increment the counter]</li>
 <li>To unregister the session: <a href="<?php $self_url->copy()->add('mode', 'unregister') ?>">click here</a></li>
</ul>

<h1>PEAR</h1>
<ul>
    <?php echo $pear_output ?>
</ul>

<?php

/* Do application specifc tests now. */
echo $test_ob->appTests();

require $test_templates . '/footer.inc';
