<?php
/**
 * News base
 *
 * Copyright 2006 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: base.php 200 2008-01-09 08:29:52Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package News
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';
require_once 'Horde/Loader.php';

// Registry.
$registry = &Registry::singleton();
if (($pushed = $registry->pushApp('news', !defined('AUTH_HANDLER'))) instanceof PEAR_Error) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
define('NEWS_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = &Notification::singleton();
$notification->attach('status');

// Define the base file path of News.
if (!defined('NEWS_BASE')) {
    define('NEWS_BASE', dirname(__FILE__) . '/..');
}

// Cache
$GLOBALS['cache'] = &Horde_Cache::singleton($GLOBALS['conf']['cache']['driver'],
                                            Horde::getDriverConfig('cache', $GLOBALS['conf']['cache']['driver']));

// News base library
require_once NEWS_BASE . '/lib/News.php';
require_once NEWS_BASE . '/lib/Categories.php';
require_once NEWS_BASE . '/lib/View.php';

// Set up News drivers.
$GLOBALS['news'] = new News();
$GLOBALS['news_cat'] = new News_Categories();

// Start compression.
if (!Util::nonInputVar('no_compress')) {
    Horde::compressOutput();
}

