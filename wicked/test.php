<?php
/**
 * $Horde: wicked/test.php,v 1.15 2009/10/04 05:25:52 mrubinsk Exp $
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

/* Include Horde's core.php file. */
include_once '../lib/core.php';

/* We should have loaded the String class, from the Horde_Util package, in
 * core.php. If Horde_String:: isn't defined, then we're not finding some critical
 * libraries. */
if (!class_exists('Horde_String')) {
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

/* Wicked version. */
$module = 'Wicked';
require_once './lib/version.php';
$module_version = WICKED_VERSION;

require TEST_TEMPLATES . 'header.inc';
require TEST_TEMPLATES . 'version.inc';

/* Display PHP Version information. */
$php_info = $horde_test->getPhpVersionInformation();
require TEST_TEMPLATES . 'php_version.inc';

/* PEAR */
$pear_list = array(
    'Text_Wiki' => array(
        'path' => 'Text/Wiki.php',
        'error' => 'The Text_Wiki module is required to parse and render the wiki markup in Wicked.',
        'required' => true,
        'function' => '_check_pear_text_wiki_version'
    ),
    'Text_Wiki_BBCode' => array(
        'path' => 'Text/Wiki/BBCode.php',
        'error' => 'The Text_Wiki_BBCode module is required if you plan on using BBCode formatting.',
        'required' => false,
    ),
    'Text_Wiki_Cowiki' => array(
        'path' => 'Text/Wiki/Cowiki.php',
        'error' => 'The Text_Wiki_Cowiki module is required if you plan on using Cowiki formatting.',
        'required' => false,
    ),
    'Text_Wiki_Creole' => array(
        'path' => 'Text/Wiki/Creole.php',
        'error' => 'The Text_Wiki_Creole module is required if you plan on using Creole formatting.',
        'required' => false,
    ),
    'Text_Wiki_Mediawiki' => array(
        'path' => 'Text/Wiki/Mediawiki.php',
        'error' => 'The Text_Wiki_Mediawiki module is required if you plan on using Mediawiki formatting.',
        'required' => false,
    ),
    'Text_Wiki_Tiki' => array(
        'path' => 'Text/Wiki/Tiki.php',
        'error' => 'The Text_Wiki_Tiki module is required if you plan on using Tiki formatting.',
        'required' => false,
    ),
);

/* Additional check for PEAR Text_Wiki module for its version. */
function _check_pear_text_wiki_version()
{
    if (!is_callable(array('Text_Wiki', 'setRenderConf'))) {
        return 'Your version of Text_Wiki is not recent enough.';
    }
}

?>
<h1>PEAR Modules</h1>
<ul>
    <?php echo $horde_test->PEARModuleCheck($pear_list) ?>
</ul>

<?php
require TEST_TEMPLATES . 'footer.inc';
