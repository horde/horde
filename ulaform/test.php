<?php
/**
 * $Horde: ulaform/test.php,v 1.10 2009-06-10 05:25:19 slusarz Exp $
 */

/* Include Horde's core.php file. */
include_once '../lib/core.php';

/* We should have loaded the String class, from the Horde_Util
 * package, in core.php. If Horde_String:: isn't defined, then we're not
 * finding some critical libraries. */
if (!class_exists('String')) {
    echo '<br /><h2 style="color:red">The Horde_Util package was not found. If PHP\'s error_reporting setting is high enough and display_errors is on, there should be error messages printed above that may help you in debugging the problem. If you are simply missing these files, then you need to get the <a href="http://cvs.horde.org/cvs.php/framework">framework</a> module from <a href="http://www.horde.org/source/">Horde CVS</a>, and install the packages in it with the install-packages.php script.</h2>';
    exit;
}

require_once './lib/version.php';
define('CONFIG_DIR', dirname(__FILE__) . '/config/');

$setup_ok = true;

$config_files = array('conf.php', 'prefs.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">

<html>
<head>
<title>Ulaform: System Capabilities Test</title>
</head>

<body>

<h1>Ulaform Version</h1>
<ul>
 <li>Ulaform: <?php echo ULAFORM_VERSION ?></li>
</ul>

<h1>Ulaform Configuration Files</h1>
<ul>
<?php foreach ($config_files as $file): ?>
<?php if (file_exists(CONFIG_DIR . $file)): ?>
 <li><?php echo $file ?> - <font color="green"><strong>Yes</strong></font>
<?php else : $setup_ok = false; ?>
 <li><?php echo $file ?> - <font color="red"><strong>No</strong></font>
<?php endif; endforeach; ?>
</ul>

</body>
</html>
