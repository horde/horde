<?php
/**
 * $Id: index.php 31 2007-12-13 14:33:33Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
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
