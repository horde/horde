<?php
/**
 * Copyright 2005-2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$shout = Horde_Registry::appInit('shout');

require_once SHOUT_BASE . '/lib/Forms/AccountForm.php';

$RENDERER = new Horde_Form_Renderer();

$title = _("Accounts: ");

Horde::addScriptFile('stripe.js', 'horde');

require $registry->get('templates', 'horde') . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

Shout::getAdminTabs();

require $registry->get('templates', 'horde') . '/common-footer.inc';

