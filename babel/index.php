<?php
/**
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Joel Vandal <joel@scopserv.com>
 * @package Babel
 */

@define('BABEL_BASE', dirname(__FILE__)) ;
require_once BABEL_BASE . '/lib/base.php';

/* Render the page. */
require BABEL_TEMPLATES . '/common-header.inc';
echo $template->fetch(BABEL_TEMPLATES . '/layout.html');

require BABEL_TEMPLATES . '/index.php';

require $registry->get('templates', 'horde') . '/common-footer.inc';
