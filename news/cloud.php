<?php
/**
* $Id: index.php 31 2007-12-13 14:33:33Z duck $
*
* Copyright Obala d.o.o. (www.obala.si)
*
* See the enclosed file COPYING for license information (GPL). If you
* did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
*
* @author Duck <duck@obala.net>
* @package Folks
*/

define('NEWS_BASE', dirname(__FILE__));
require_once NEWS_BASE . '/lib/base.php';

$cloud = $news->getCloud();
if ($cloud instanceof PEAR_Error) {
    $notification->push($cloud->getMessage(), 'horde.error');
    $cloud = '';
}

require NEWS_TEMPLATES . '/common-header.inc';
require NEWS_TEMPLATES . '/menu.inc';

echo $cloud;

require $registry->get('templates', 'horde') . '/common-footer.inc';
