<?php
/**
 * Tag browsing.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package Trean
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('trean');

require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));

$v = new Trean_View_Browse();
$v->render();

require_once $registry->get('templates', 'horde') . '/common-footer.inc';
