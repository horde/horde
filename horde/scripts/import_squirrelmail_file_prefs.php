#!/usr/bin/php
<?php
/**
 * This script imports SquirrelMail file-based preferences into Horde.
 * It was developed against SquirrelMail 1.4.0, so use at your own risk
 * against different versions.
 *
 * Input can be either a single squirrelmail .pref file, or a directory
 * containing multiple .pref files.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Ben Chavet <ben@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none', 'cli' => true));

// Read command line parameters.
if ($argc != 2) {
    $cli->message('Too many or too few parameters.', 'cli.error');
    $cli->writeln('Usage: import_squirrelmail_prefs.php path-to-squirrelmail-data');
    exit;
}
$data = $argv[1];

require_once dirname(__FILE__) . '/import_squirrelmail_prefs.php';

// Get list of SquirrelMail pref files
if (is_dir($data)) {
    $files = array();
    if (!($handle = opendir($data))) {
        exit;
    }
    while (false !== ($file = readdir($handle))) {
        if (preg_match('/.pref$/', $file)) {
            $files[] = $data . '/' . $file;
        }
    }
    closedir($handle);
} else {
    $files = array($data);
}

// Loop through SquirrelMail pref files
foreach($files as $file) {
    if (!($handle = fopen($file, 'r'))) {
        continue;
    }

    // Set current user
    $user = substr(basename($file), 0, -5);
    $registry->setAuth($user, array());
    $cli->message('Importing ' . $user . '\'s preferences');

    // Reset user prefs
    $prefs = $injector->getInstance('Horde_Prefs')->getPrefs('horde', array(
        'cache' => false,
        'user' => $user
    ));
    $prefs_cache = array();

    // Read pref file, one line at a time
    while (!feof($handle)) {
        $buffer = fgets($handle);
        if (empty($buffer)) {
            continue;
        }

        /**
         * BEGIN: Code from squirrelmail to parse pref (GPL)
         */
        $pref = trim($buffer);
        $equalsAt = strpos($pref, '=');
        if ($equalsAt > 0) {
            $key = substr($pref, 0, $equalsAt);
            $value = substr($pref, $equalsAt + 1);
            /* this is to 'rescue' old-style highlighting rules. */
            if (substr($key, 0, 9) == 'highlight') {
                $key = 'highlight' . $highlight_num++;
            }

            if ($value != '') {
                $prefs_cache[$key] = $value;
            }
        }
        /**
         * END: Code from squirrelmail to parse pref (GPL)
         */
    }

    fclose($handle);

    savePrefs($user, substr($file, 0, -5), $prefs_cache);
}

function getSignature($basename, $number = 'g')
{
    $sigfile = $basename . '.si' . $number;
    $signature = '';
    if (file_exists($sigfile)) {
        if ($handle = @fopen($sigfile, 'r')) {
            while (!feof($handle)) {
                $signature .= fgets($handle, 1024);
            }
            fclose($handle);
        }
    }
    return $signature;
}
