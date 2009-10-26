<?php
/**
 * IMP test script.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Brent J. Nordquist <bjn@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

function _doConnectionTest()
{
    $imap_config = array(
        'username' => isset($_POST['user']) ? $_POST['user'] : '',
        'password' => isset($_POST['passwd']) ? $_POST['passwd'] : '',
        'hostspec' => isset($_POST['server']) ? $_POST['server'] : '',
        'port' => isset($_POST['port']) ? $_POST['port'] : '',
        'secure' => ($_POST['port'] == 'yes')
    );

    $driver = ($_POST['server_type'] == 'imap') ? 'Socket' : 'Socket_Pop3';

    try {
        $imap_client = Horde_Imap_Client::factory($driver, $imap_config);
    } catch (Horde_Imap_Client_Exception $e) {
        return _errorMsg($e);
    }

    echo "<strong>Attempting to login to the server:</strong>\n";

    try {
        $imap_client->login();
    } catch (Horde_Imap_Client_Exception $e) {
        return _errorMsg($e);
    }

    echo '<span style="color:green">SUCCESS</span><p />';

    if ($driver == 'Socket') {
        echo "<strong>The following IMAP server information was discovered from the remote server:</strong>\n" .
            "<blockquote><em>Namespace Information</em><blockquote><pre>";

        try {
            $namespaces = $imap_client->getNamespaces();
            foreach ($namespaces as $val) {
                echo "NAMESPACE: \"" . htmlspecialchars($val['name']) . "\"\n";
                echo "DELIMITER: " . htmlspecialchars($val['delimiter']) . "\n";
                echo "TYPE: " . htmlspecialchars($val['type']) . "\n\n";
            }
        } catch (Horde_Imap_Client_Exception $e) {
            _errorMsg($e);
        }

        echo "</pre></blockquote></blockquote>\n" .
            "<blockquote><em>IMAP server capabilities:</em><blockquote><pre>";

        try {
            foreach ($imap_client->capability() as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $val2) {
                        echo htmlspecialchars($key) . '=' . htmlspecialchars($val2) . "\n";
                    }
                } else {
                    echo htmlspecialchars($key) . "\n";
                }
            }
        } catch (Horde_Imap_Client_Exception $e) {
            _errorMsg($e);
        }

        echo "</pre></blockquote></blockquote>\n";

        try {
            $id_info = $imap_client->getID();
            if (!empty($id_info)) {
                echo "<blockquote><em>IMAP server information:</em><blockquote><pre>";
                foreach ($id_info as $key => $val) {
                    echo htmlspecialchars("$key:  $val") . "\n";
                }
                echo "</pre></blockquote></blockquote>\n";
            }
        } catch (Horde_Imap_Client_Exception $e) {
            // Ignore a lack of the ID capability.
        }

        // @todo IMAP Charset Search Support
    }
}

function _errorMsg($e)
{
    echo "<span style=\"color:red\">ERROR</span> - The server returned the following error message:\n" .
        '<pre>' . $e->getMessage() . '</pre><p />';
}


/* Include Horde's core.php file. */
require_once dirname(__FILE__) . '/lib/Application.php';

/* We should have loaded the Horde_String class, from the Horde_Util
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

/* IMP version. */
$module = 'IMP';
require_once dirname(__FILE__) . '/lib/Application.php';
$app = new IMP_Application();
$module_version = $app->version;

require TEST_TEMPLATES . 'header.inc';
require TEST_TEMPLATES . 'version.inc';

/* Display versions of other Horde applications. */
$app_list = array(
    'gollem' => array(
        'error' => 'Gollem provides access to local VFS filesystems to attach files.',
        'version' => '2.0'
    ),
    'ingo' => array(
        'error' => 'Ingo provides basic mail filtering capabilities to IMP.',
        'version' => '2.0'
    ),
    'nag' => array(
        'error' => 'Nag allows tasks to be directly created from e-mail data.',
        'version' => '3.0'
    ),
    'turba' => array(
        'error' => 'Turba provides addressbook/contacts capabilities to IMP.',
        'version' => '3.0'
    )
);
$app_output = $horde_test->requiredAppCheck($app_list);

?>
<h1>Other Horde Applications</h1>
<ul>
    <?php echo $app_output ?>
</ul>
<?php

/* Display PHP Version information. */
$php_info = $horde_test->getPhpVersionInformation();
require TEST_TEMPLATES . 'php_version.inc';

/* PHP settings. */
$setting_list = array(
    'file_uploads'  =>  array(
        'setting' => true,
        'error' => 'file_uploads must be enabled to use various features of IMP. See the INSTALL file for more information.'
    )
);

/* IMP configuration files. */
$file_list = array(
    'config/conf.php' => 'The file <code>./config/conf.php</code> appears to be missing. You must generate this file as an administrator via Horde.  See horde/docs/INSTALL.',
    'config/mime_drivers.php' => null,
    'config/prefs.php' => null,
    'config/servers.php' => null
);

/* PEAR/PECL modules. */
$pear_list = array(
    'Auth_SASL' => array(
        'path' => 'Auth/SASL.php',
        'error' => 'If your IMAP server uses CRAM-MD5 or DIGEST-MD5 authentication, this module is required.'
    )
);

/* Get the status output now. */
$setting_output = $horde_test->phpSettingCheck($setting_list);
$file_output = $horde_test->requiredFileCheck($file_list);
$pear_output = $horde_test->PEARModuleCheck($pear_list);

?>

<h1>Miscellaneous PHP Settings</h1>
<ul>
    <?php echo $setting_output ?>
</ul>

<h1>Required IMP Configuration Files</h1>
<ul>
    <?php echo $file_output ?>
</ul>

<h1>PEAR</h1>
<ul>
    <?php echo $pear_output ?>
</ul>

<h1>PHP Mail Server Support Test</h1>
<?php
if (isset($_POST['user']) && isset($_POST['passwd'])) {
    _doConnectionTest();
}
?>

<form name="form1" method="post" action="test.php">
<table>
<tr><td align="right"><label for="server">Server:</label></td><td><input type="text" id="server" name="server" /></td><td>(If blank, attempts to connects to a server running on the same machine as IMP)</td></tr>
<tr><td align="right"><label for="port">Port:</label></td><td><input type="text" id="port" name="port" /></td><td>(If non-standard port; leave blank to auto-detect using standard ports)</td></tr>
<tr><td align="right"><label for="user">User:</label></td><td><input type="text" id="user" name="user" /></td></tr>
<tr><td align="right"><label for="passwd">Password:</label></td><td><input type="password" id="passwd" name="passwd" /></td></tr>
<tr><td align="right"><label for="server_type">Server Type:</label></td><td><select id="server_type" name="server_type"><option value="imap">IMAP4rev1</option><option value="pop">POP3</option></select></td></tr>
<tr><td align="right"><label for="encrypt">Use SSL/TLS:</label></td><td><select id="encrypt" name="encrypt"><option value="no">No</option><option value="ssl">SSL</option><option value="tls">TLS</option></select></td></tr>
<tr><td></td><td><input type="submit" name="f_submit" value="Submit" /><input type="reset" name="f_reset" value="Reset" /></td></tr>
</table>
</form>

<?php
require TEST_TEMPLATES . 'footer.inc';
