<?php
/**
 * The Vilma script to add/edit forwardes.
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
    $form = new Vilma_Form_EditForward($vars);
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        try {
            $forward_id = $vilma->driver->saveForward($info);
            $notification->push(_("forward saved."), 'horde.success');
        } catch (Exception $e) {
            Horde::logMessage($e);
            $notification->push(sprintf(_("Error saving forward. %s"), $e->getMessage()), 'horde.error');
            // Remove the mode, and rearrange the forward information to clean
            // up the form.
            unset($vars->mode);
            $vars->add('retry', true);
            if (isset($vars->forward)) {
                unset($vars->forward_address);
            } elseif (isset($vars->address)) {
                unset($vars->forward_address, $vars->forward);
            }
        }
    }
}

/* Check if a form is being edited. */
if (!isset($vars->mode) || $vars->retry) {
    if (isset($vars->forward)) {
        try {
            $addrInfo = $vilma->driver->getAddressInfo($vars->forward, 'forward');
            $address = $vilma->driver->getAddressInfo($addrInfo['destination']);
        } catch (Exception $e) {
            Horde::logMessage($e);
            $notification->push(sprintf(_("Error reading address information from backend: %s"), $e->getMessage()), 'horde.error');
            Horde::url('users/index.php', true)->redirect();
        }
        $vars = new Variables($address);
        $vars->mode = 'edit';
        $vars->add('forward_address', $forward);
        $vars->add('forward', $forward);
        $vars->add('address', $address['address']);
    } elseif (isset($vars->address)) {
        $address = $vilma->driver->getAddressInfo($vars->address, 'all');
        $vars = new Variables($address);
        $vars->mode = 'new';
    }

    $form = new EditforwardForm($vars);
/*
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        $forward_id = $vilma->driver->saveforward($info);
        if (is_a($forward_id, 'PEAR_Error')) {
            Horde::logMessage($user_id, 'ERR');
            $notification->push(sprintf(_("Error saving forward. %s"), $forward_id->getMessage()), 'horde.error');
        } else {
            $notification->push(_("forward saved."), 'horde.success');
        }
    }
*/
}

/* Render the form. */
$renderer = new Horde_Form_Renderer();

$template = $injector->createInstance('Horde_Template');

Horde::startBuffer();
$form->renderActive($renderer, $vars, 'editForward.php', 'post');
$template->set('main', Horde::endBuffer());

$template->set('menu', Horde::menu());

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require $registry->get('templates', 'horde') . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
