<?php
/**
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

@define('VILMA_BASE', dirname(__FILE__));
require_once VILMA_BASE . '/lib/base.php';

$vilma_configured = (@is_readable(VILMA_BASE . '/config/conf.php') &&
                     @is_readable(VILMA_BASE . '/config/prefs.php'));

if (!$vilma_configured) {
    require VILMA_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Vilma', VILMA_BASE, array('conf.php', 'prefs.php'));
}

require VILMA_BASE . '/domains/index.php';
