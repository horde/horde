<?php
/**
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$vilma = Horde_Registry::appInit('vilma');

/* Only admin should be using this. */
if (!Vilma::hasPermission($domain)) {
    $registry->authenticateFailure('vilma');
}

$vars = Horde_Variables::getDefaultVariables();
$user_id = $vars->address;
$section = $vars->get('section', 'all');

try {
    $address = $vilma->driver->getAddressInfo($user_id);
    $type = $address['type'];
    if ($section == 'all' && $type == 'alias') {
        $address = $vilma->driver->getAddressInfo($user_id, $type);
    }
} catch (Exception $e) {
    $notification->push(sprintf(_("Error reading address information from backend: %s"), $address->getMessage()), 'horde.error');
    Horde::url('users/index.php', true)->redirect();
}

$user = $vilma->driver->getUser($user_id);
/*
$aliases = $vilma->driver->_getAliases($user_id);
$aliasesCount = 0;
if (is_array($aliases)) {
    $aliasesCount = sizeof($aliases);
}
*/

$domain = Vilma::stripDomain($user_id);
$forwards = $vilma->driver->_getGroupsAndForwards('forwards', $domain);
$forwardsCount = 0;
foreach ($forwards as $entry) {
    foreach ($entry['targets'] as $target) {
        if ($user_id === $target) {
            $forwardsCount++;
        }
    }
}
$groups = $vilma->driver->_getGroupsAndForwards('groups', $domain);
$groupsCount = 0;
foreach ($groups as $entry) {
    foreach ($entry['targets'] as $target) {
        if ($user_id === $target) {
            $groupsCount++;
        }
    }
}

$user_name = $address['user_name'];
if (empty($user_name)) {
    $user_name = $address['address'];
}
$vars->user_name = Vilma::stripUser($user_name);
$domain = Vilma::stripDomain($address);
$domain = $vilma->driver->getDomainByName($domain);
$vars->domain = $domain;
$vars->mode = 'edit';

/* Set up the form. */
$form = new Horde_Form($vars, sprintf(_("Delete %s"), $type));
$form->setButtons(array(_("Delete"), _("Do not delete")));
//$form->addHidden($user_id, 'user_id', 'text', false);
$form->addHidden($address['address'], 'address', 'text', false);
$form->addHidden($section, 'section', 'text', false);

if ($aliasesCount + $groupsCount + $forwardsCount) {
    $desc = _("Delete %s \"%s\" and all dependencies?");
} else {
    $desc = _("Delete %s \"%s\"?");
}
$desc = sprintf($desc, $type, $user_name);
if ($aliasesCount) {
    $desc .= ' ' . sprintf(ngettext("Account has %d alias.", "Account has %d aliases.", $aliasesCount), $aliasesCount);
}
if ($forwardsCount) {
    $desc .= ' ' . sprintf(ngettext("Account is the target of %d forward.", "Account is the target of %d forwards.", $forwardsCount), $forwardsCount);
}
if ($groupsCount) {
    $desc .= ' ' . sprintf(ngettext("Account belongs to %d group.", "Account belongs to %d groups.", $groupsCount), $groupsCount);
}
$form->addVariable($desc, 'description', 'description', false);

if ($vars->get('submitbutton') == _("Do not delete")) {
    $notification->push(_("User not deleted."), 'horde.message');
    Horde::url('users/index.php', true)
        ->add('domain_id', $domain['id'])
        ->redirect();
}

if ($vars->get('submitbutton') == _("Delete") &&
    $form->validate($vars)) {
    $form->getInfo($vars, $info);

    switch ($type) {
    case 'alias':
        $deleteInfo = array('address' => $address['destination'],
                            'alias' => $user_id);
        try {
            $vilma->driver->deleteAlias($deleteInfo);
            $notification->push(_("Alias deleted."), 'horde.success');
        } catch (Exception $e) {
            $notification->push(sprintf(_("Error deleting alias. %s."), $e->getMessage()), 'horde.error');
        }
        Horde::url('users/index.php', true)
            ->add('domain_id', $domain['id'])
            ->redirect();

    case 'forward':
        $deleteInfo = array('address' => $address['destination'],
                            'forward' => $user_id);
        try {
            $vilma->driver->deleteForward($deleteInfo);
            $notification->push(_("Forward deleted."), 'horde.success');
        } catch (Exception $e) {
            $notification->push(sprintf(_("Error deleting forward. %s."), $e->getMessage()), 'horde.error');
        }
        Horde::url('users/index.php', true)
            ->add('domain_id', $domain['id'])
            ->redirect();

    default:
        try {
            $vilma->driver->deleteUser($address['address']);
            $notification->push(_("$type deleted."), 'horde.success');
        } catch (Exception $e) {
            $notification->push(sprintf(_("Error deleting user. %s."), $e->getMessage()), 'horde.error');
        }
        Horde::url('users/index.php', true)
            ->add('domain_id', $domain['id'])
            ->redirect();
    }
}

/* Render the form. */
$renderer = new Horde_Form_Renderer();

$template = $injector->createInstance('Horde_Template');

Horde::startBuffer();
$form->renderActive($renderer, $vars, 'delete.php', 'post');
$template->set('main', Horde::endBuffer());

$template->set('menu', Horde::menu());

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require $registry->get('templates', 'horde') . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
