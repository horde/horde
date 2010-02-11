<?php
/**
 * News base
 *
 * $Id: base.php 1260 2009-02-01 23:15:50Z duck $
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package News
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$registry = new Horde_Registry();
try {
    $registry->pushApp('news', array('check_perms' => (Horde_Util::nonInputVar('news_authentication') != 'none'), 'logintasks' => true));
} catch (Horde_Exception $e) {
    Horde_Auth::authenticateFailure('news', $e);
}
$conf = &$GLOBALS['conf'];
define('NEWS_TEMPLATES', $registry->get('templates'));

// Define the base file path of News.
if (!defined('NEWS_BASE')) {
    define('NEWS_BASE', dirname(__FILE__) . '/..');
}

// Cache
$GLOBALS['cache'] = $injector->getInstance('Horde_Cache');

// Set up News drivers.
$GLOBALS['news'] = News_Driver::factory();
$GLOBALS['news_cat'] = new News_Categories();

// Start compression.
if (!Horde_Util::nonInputVar('no_compress')) {
    Horde::compressOutput();
}
