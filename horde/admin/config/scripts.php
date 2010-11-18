<?php
/**
 * Generates upgrade scripts for Horde's configuration.
 *
 * Currently allows the generation of PHP upgrade scripts for conf.php
 * files either as download or saved to the server's temporary
 * directory.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('admin' => true));

/* Get form data. */
$setup = Horde_Util::getFormData('setup');
$type = Horde_Util::getFormData('type');
$save = Horde_Util::getFormData('save');
$clean = Horde_Util::getFormData('clean');

$filename = 'horde_configuration_upgrade.php';

/* Check if this is only a request to clean up. */
if ($clean == 'tmp') {
    $tmp_dir = Horde::getTempDir();
    $path = Horde_Util::realPath($tmp_dir . '/' . $filename);
    if (@unlink($tmp_dir . '/' . $filename)) {
        $notification->push(sprintf(_("Deleted configuration upgrade script \"%s\"."), $path), 'horde.success');
    } else {
        $notification->push(sprintf(_("Could not delete configuration upgrade script \"%s\"."), Horde_Util::realPath($path)), 'horde.error');
    }
    $registry->clearCache();
    Horde::url('admin/config/index.php', true)->redirect();
}

$data = '';
if ($setup == 'conf' && $type == 'php') {
    /* Save PHP code into a string for creating the script to be run at the
     * command prompt. */
    $data = '#!/usr/bin/env php' . "\n";
    $data .= '<?php' . "\n";
    foreach ($session->get('horde', 'config/') as $app => $php) {
        $path = $registry->get('fileroot', $app) . '/config';
        $data .= '$conf = \'' . $path . '/conf.php\';' . "\n";
        /* Add code to save backup. */
        $data .= 'if (file_exists(\'$conf\')) {' . "\n";
        $data .= '    if (is_link(\'$conf\')) {' . "\n";
        $data .= '        $conf = readlink($conf);' . "\n";
        $data .= '    }' . "\n";
        $data .= '    if (@copy(\'$conf\', \'' . $path . '/conf.bak.php\')) {' . "\n";
        $data .= '        echo \'Successfully saved backup configuration.\' . "\n";' . "\n";
        $data .= '    } else {' . "\n";
        $data .= '        echo \'Could NOT save a backup configuation.\' . "\n";' . "\n";
        $data .= '    }' . "\n";
        $data .= '}' . "\n";

        $data .= 'if ($fp = @fopen(\'$conf\', \'w\')) {' . "\n";
        $data .= '    fwrite($fp, \'';
        $data .= str_replace(array('\\', '\''), array('\\\\', '\\\''), $php);
        $data .= '\');' . "\n";
        $data .= '    fclose($fp);' . "\n";
        $data .= '    echo \'' . sprintf('Saved %s configuration.', $app) . '\' . "\n";' . "\n";
        $data .= '} else {' . "\n";
        $data .= '    echo \'' . sprintf('Could NOT save %s configuration.', $app) . '\' . "\n";' . "\n";
        $data .= '    exit;' . "\n";
        $data .= '}' . "\n\n";
    }
}

if ($save != 'tmp') {
    /* Output script to browser for download. */
    $browser->downloadHeaders($filename, 'text/plain', false, strlen($data));
    echo $data;
    exit;
}

$tmp_dir = Horde::getTempDir();
/* Add self-destruct code. */
$data .= 'echo \'Self-destructing...\' . "\n";' . "\n";
$data .= 'if (@unlink(__FILE__)) {' . "\n";
$data .= '    echo \'Upgrade script deleted.\' . "\n";' . "\n";
$data .= '} else {' . "\n";
$data .= '    echo \'WARNING!!! REMOVE SCRIPT MANUALLY FROM ' . $tmp_dir . '\' . "\n";' . "\n";
$data .= '}' . "\n";
/* The script should be saved to server's temporary directory. */
$path = Horde_Util::realPath($tmp_dir . '/' . $filename);
if ($fp = @fopen($tmp_dir . '/' . $filename, 'w')) {
    fwrite($fp, $data);
    fclose($fp);
    chmod($tmp_dir . '/' . $filename, 0777);
    $notification->push(sprintf(_("Saved configuration upgrade script to: \"%s\"."), $path), 'horde.success');
} else {
    $notification->push(sprintf(_("Could not save configuration upgrade script to: \"%s\"."), $path), 'horde.error');
}

Horde::url('admin/config/index.php', true)->redirect();
