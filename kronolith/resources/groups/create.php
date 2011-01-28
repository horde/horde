<?php
/**
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('kronolith');

// Exit if this isn't an authenticated, administrative user
if (!$registry->isAdmin()) {
    Horde::url($prefs->getValue('defaultview') . '.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$form = new Kronolith_Form_CreateResourceGroup($vars);

// Execute if the form is valid.
if ($form->validate($vars)) {
    try {
        $result = $form->execute();
        $notification->push(sprintf(_("The calendar \"%s\" has been created."), $vars->get('name')), 'horde.success');
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }

    Horde::url('resources/groups/', true)->redirect();
}

$title = $form->getTitle();
$menu = Horde::menu();
require $registry->get('templates', 'horde') . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
echo $menu;
$notification->notify(array('listeners' => 'status'));
echo $form->renderActive($form->getRenderer(), $vars, 'create.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
