<?php
/**
 * $Id: index.php 31 2007-12-13 14:33:33Z duck $
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */

require_once dirname(__FILE__) . '/lib/base.php';

$cloud = $news->getCloud();
if ($cloud instanceof PEAR_Error) {
    $notification->push($cloud);
    $cloud = '';
}

require NEWS_TEMPLATES . '/common-header.inc';
require NEWS_TEMPLATES . '/menu.inc';

echo $cloud;

require $registry->get('templates', 'horde') . '/common-footer.inc';
