<?php
/**
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

/* Include Horde's core.php file. */
require_once dirname(__FILE__) . '/lib/base.load.php';
include_once HORDE_BASE . '/lib/core.php';

/* We should have loaded the String class, from the Horde_Util
 * package, in core.php. If Horde_String:: isn't defined, then we're not
 * finding some critical libraries. */
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

/* Ansel version. */
$module = 'Ansel';
require_once ANSEL_BASE . '/lib/version.php';
$module_version = ANSEL_VERSION;

/* Ansel configuration files. */
$file_list = array(
    'config/conf.php' => 'The file <code>./config/conf.php</code> appears to be missing. You probably just forgot to generate it using the Horde config system - see docs/INSTALL for details. While you do that, take a look at the settings and make sure they are appropriate for your site.',
    'config/prefs.php' => 'The file <code>./config/prefs.php</code> appears to be missing. You probably just forgot to copy <code>./config/prefs.php.dist</code> over. While you do that, take a look at the settings and make sure they are appropriate for your site.'
);

require TEST_TEMPLATES . 'header.inc';
require TEST_TEMPLATES . 'version.inc';

/* Display versions of other Horde applications. */
$app_list = array(
    'horde' => array(
        'error' => 'Ansel requires at least Horde 4.0',
        'version' => '4.0'
    ),
    'agora' => array(
        'error' => 'Agora provides the ability for users to comment on images.',
        'version' => '1.0')
);
$app_output = $horde_test->requiredAppCheck($app_list);
?>
<h1>Other Horde Applications</h1>
<ul>
    <?php echo $app_output ?>
</ul>

<?php
/* PHP module capabilities. */
$module_list = array(
    'gd' => array(
        'descrip' => 'GD Support',
        'error' => 'You need either GD2 support in PHP, or an external driver like ImageMagick.  Either recompile PHP with GD2 support, or make sure that the path to ImageMagick\'s convert utility is set in horde/config/conf.php.'
    ),
    'imagick' => array(
        'descrip' => 'Imagick Library',
        'required' => false,
        'error' => 'Ansel can make use of the Imagick Library, if it is installed on your system.  It is highly recommended to use either ImageMagick\'s convert utility or the Imagick php library for faster results.'
    ),
    'zip' => array(
        'descrip' => 'Zip Support',
        'required' => false,
        'error' => 'Ansel can make use of PHP\'s Zip extension for more efficiently processing uploaded ZIP files.'
    ),
    'opencv' => array(
        'descrip' => 'OpenCV Library',
        'required' => false,
        'error' => 'Ansel can make use of the OpenCV PHP extension for automatically detecting human faces in images. You need either this library or the one immediately below to detect human faces.'
    ),
    'facedetect' => array(
        'descrip' => 'Facedetect Face Detection Library',
        'required' => false,
        'error' => 'Ansel can make use of the Facedetect PHP extension for automatically detecting human faces in images.  You need either OpenCV (above) or Facedetect to detect human faces.'
    ),
    'libpuzzle' => array(
        'descrip' => 'Puzzle Library',
        'required' => false,
        'error' => 'Ansel can make use of the libpuzzle PHP extension for finding similar images based on image content.'
    )
);

/* PEAR */
$pear_list = array('MDB2' => array(
                        'path' => 'MDB2.php',
                        'required' => true,
                        'error' => 'You do not have the MDB2 package installed on your system. In addition to this package, you will need the appropriate MDB2_Driver package for your database backend.'),
);

/* Display PHP Version information. */
$php_info = $horde_test->getPhpVersionInformation();
require TEST_TEMPLATES . 'php_version.inc';

?>
<h1>PHP Module Capabilities</h1>
<ul>
    <?php echo $horde_test->phpModuleCheck($module_list) ?>
</ul>

<h1>Ansel Configuration Files</h1>
<ul>
    <?php echo $horde_test->requiredFileCheck($file_list) ?>
</ul>

<h1>PEAR Modules</h1>
<ul>
    <?php echo $horde_test->PEARModuleCheck($pear_list) ?>
</ul>

<?php
require TEST_TEMPLATES . 'footer.inc';
