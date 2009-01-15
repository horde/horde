<?php
/**
 * Cached data output script.
 *
 * Parameters in (via PATH_INFO):
 *   1st parameter = The type of content to output ('css', 'fckeditor', 'js')
 *   2nd parameter = Cache ID
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

/* The amount of time (in minutes) to cache the generated CSS and JS files.
 * DEFAULT: 525600 = 1 year */
$expire_time = 525600;

/* Load IMP.php to access IMP::getPathInfo(). */
$imp_dir = dirname(__FILE__);
if (!defined('HORDE_BASE')) {
    /* Temporary fix - if horde does not live directly under the imp
     * directory, the HORDE_BASE constant should be defined in
     * imp/lib/base.local.php. */
    if (file_exists($imp_dir . '/lib/base.local.php')) {
        include $imp_dir . '/lib/base.local.php';
    } else {
        define('HORDE_BASE', $imp_dir . '/..');
    }
}
require_once HORDE_BASE . '/lib/core.php';
$path_info = trim(Util::getPathInfo(), '/');
if (empty($path_info)) {
    exit;
}

// 'fckeditor' output won't have a second slash, so ignore errors
$old_error = error_reporting(0);
list($type, $cid) = explode('/', $path_info, 2);
error_reporting($old_error);

/* Allow CSS and JS caches to be cached on the browser since there is no
 * dynamic code that will change over the course of a session. Can't cache
 * fckeditor setting as it may change. Only authenticate for 'fckeditor'
 * actions (for access to user's prefs). */
if ($type == 'fckeditor') {
    $session_cache_limiter = 'nocache';
} else {
    $session_cache_limiter = 'public';
    session_cache_expire($expire_time);
    @define('AUTH_HANDLER', true);
    $authentication = 'none';
}
$session_control = 'readonly';
$session_timeout = 'none';
require_once $imp_dir . '/lib/base.php';

switch ($type) {
case 'css':
    $type = 'text/css';
    $lifetime = (empty($GLOBALS['conf']['server']['cachecssparams']['lifetime'])) ? 0 : $GLOBALS['conf']['server']['cachecssparams']['lifetime'];
    break;

case 'fckeditor':
    header('Content-Type: text/javascript');
    echo 'FCKConfig.ToolbarSets["ImpToolbar"] = ' . $GLOBALS['prefs']->getValue('fckeditor_buttons') . ';' . "\n" .
        // To more closely match "normal" textarea behavior, send <BR> on
        // enter instead of <P>.
        'FCKConfig.EnterMode = \'br\';' . "\n" .
        'FCKConfig.ShiftEnterMode = \'p\';';
    exit;

case 'js':
    $type = 'text/javascript';
    $lifetime = (empty($GLOBALS['conf']['server']['cachejsparams']['lifetime'])) ? 0 : $GLOBALS['conf']['server']['cachejsparams']['lifetime'];
    break;

default:
    exit;
}

if (empty($cid)) {
    exit;
}

if (!($cache = &IMP::getCacheOb())) {
    Horde::fatal('No cache backend available.', __FILE__, __LINE__);
}

// If cache info doesn't exist, just output an empty body.
header('Content-Type: ' . $type);
$cache->output($cid, $lifetime);
