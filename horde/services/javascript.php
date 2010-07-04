<?php
/**
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none', 'session_control' => 'readonly'));

// Figure out if we've been inlined, or called directly.
$send_headers = strstr($_SERVER['PHP_SELF'], 'javascript.php');

$app = Horde_Util::getFormData('app', Horde_Util::nonInputVar('app'));
$file = Horde_Util::getFormData('file', Horde_Util::nonInputVar('file'));
if (!empty($app) && !empty($file) && strpos($file, '..') === false) {
    $script_file = $registry->get('templates', $app) . '/javascript/' . $file;
    if (file_exists($script_file)) {
        $registry->pushApp($app, array('check_perms' => false));

        header('Cache-Control: no-cache');
        header('Content-Type: text/javascript');
        require $script_file;
    }
}
