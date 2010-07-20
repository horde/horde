<?php
/**
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    header('Location: ' . Horde::applicationUrl('', true));
    exit;
}

// Exit if this isn't an authenticated, administrative user
if (!$registry->isAdmin()) {
    header('Location: ' . Horde::applicationUrl($prefs->getValue('defaultview') . '.php', true));
    exit;
}

require_once KRONOLITH_BASE . '/lib/Forms/CreateResource.php';

$vars = Horde_Variables::getDefaultVariables();
$form = new Kronolith_CreateResourceForm($vars);

// Execute if the form is valid.
if ($form->validate($vars)) {
    try {
        $result = $form->execute();
        $notification->push(sprintf(_("The calendar \"%s\" has been created."), $vars->get('name')), 'horde.success');
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }

    header('Location: ' . Horde::applicationUrl('resources/', true));
    exit;
}

$title = $form->getTitle();
require KRONOLITH_TEMPLATES . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'create.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
