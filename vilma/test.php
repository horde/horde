<?php
/**
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @package Vilma
 */

require_once './lib/version.php';
@define('CONFIG_DIR', dirname(__FILE__) . '/config/');

$setup_ok = true;

$config_files = array('conf.php', 'prefs.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">

<html>
<head>
<title>Vilma: System Capabilities Test</title>
</head>

<body>

<h1>Vilma Version</h1>
<ul>
 <li>Vilma: <?php echo VILMA_VERSION ?></li>
</ul>

<h1>Vilma Configuration Files</h1>
<ul>
<?php
foreach ($config_files as $file) {
    if (file_exists(CONFIG_DIR . $file)) {
        ?><li><?php echo $file ?> - <font color="green"><strong>Yes</strong></font><?php
    } else {
        $setup_ok = false;
        ?><li><?php echo $file ?> - <font color="red"><strong>No</strong></font><?php
    }
}
?>
</ul>
</body>
</html>
