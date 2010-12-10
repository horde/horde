<?php
/**
 * The Vilma script to add/edit aliases.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Daniel Collins <horde_dev@argentproductions.com>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$vilma = Horde_Registry::appInit('vilma');

/* Only admin should be using this. */
if (!Vilma::hasPermission($domain)) {
    $registry->authenticateFailure('vilma');
}
$vars = Variables::getDefaultVariables();

/* If the form is submitted, $vars['mode'] will be set. Catch this and process
 * the submission so that the displayed form accurately indicates the result of
 * the transaction. */
if (isset($vars->mode)) {
    $form = new Vilma_Form_EditAlias($vars);
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        try {
            $alias_id = $vilma->driver->saveAlias($info);
            $notification->push(_("Alias saved."), 'horde.success');
            Horde::url('users/index.php', true)
                ->add('domain_id', $domain['id'])
                ->redirect();
        } catch (Exception $e) {
            Horde::logMessage($e);
            $notification->push(sprintf(_("Error saving alias. %s"), $e->getMessage()), 'horde.error');
            // Remove the mode, and rearrange the alias information to clean
            // up the form.
            unset($vars->mode);
            $vars->add('retry', true);
            if (isset($vars->alias)) {
                unset($vars->alias_address);
            } elseif (isset($vars->address)) {
                unset($vars->alias_address, $vars->alias);
            }
        }
    }
}

/* Check if a form is being edited. */
if (!isset($vars->mode) || $vars->retry) {
    if (isset($vars->alias)) {
        $alias = $vars->alias;
        try {
            $addrInfo = $vilma->driver->getAddressInfo($alias, 'alias');
            $address = $vilma->driver->getAddressInfo($addrInfo['destination']);
        } catch (Exception $e) {
            $notification->push(sprintf(_("Error reading address information from backend: %s"), $e->getMessage()), 'horde.error');
            Horde::url('users/index.php', true)->redirect();
        }
        $vars = new Variables($address);
        $vars->mode = 'edit';
        $vars->add('alias_address', $alias);
        $vars->add('alias', $alias);
        $vars->add('address', $address['address']);
    } elseif (isset($vars->address)) {
        try {
            $address = $vilma->driver->getAddressInfo($vars->address, 'all');
        } catch (Exception $e) {
            $notification->push(sprintf(_("Error reading address information from backend: %s"), $e->getMessage()), 'horde.error');
            Horde::url('users/index.php', true)->redirect();
        }
        $vars = new Variables($address);
        $vars->mode = 'new';
    }

    $form = new Vilma_Form_EditAlias($vars);
/*
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        $alias_id = $vilma->driver->saveAlias($info);
        if (is_a($alias_id, 'PEAR_Error')) {
            Horde::logMessage($user_id, 'ERR');
            $notification->push(sprintf(_("Error saving alias. %s"), $alias_id->getMessage()), 'horde.error');
        } else {
            $notification->push(_("Alias saved."), 'horde.success');
        }
    }
*/
}

/* Render the form. */
$renderer = new Horde_Form_Renderer();

$template = $injector->createInstance('Horde_Template');

Horde::startBuffer();
$form->renderActive($renderer, $vars, 'editAlias.php', 'post');
$template->set('main', Horde::endBuffer());

$template->set('menu', Horde::menu());

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require $registry->get('templates', 'horde') . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
