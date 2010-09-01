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

require_once VILMA_BASE . '/lib/Forms/EditAliasForm.php';

/* Only admin should be using this. */
if (!Vilma::hasPermission($domain)) {
    $registry->authenticateFailure('vilma', $e);
}
$vars = Variables::getDefaultVariables();

/* If the form is submitted, $vars['mode'] will be set. Catch this and process the submission so that the displayed form accurately indicates the result of the transaction. */
if ($vars->exists('mode')) {
    Horde::logMessage("Submit Detected: " . print_r(serialize($vars), true), 'DEBUG');
    $form = &new EditAliasForm($vars);

    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        $alias_id = $vilma->driver->saveAlias($info);
        if (is_a($alias_id, 'PEAR_Error')) {
            Horde::logMessage($user_id, 'ERR');
            $notification->push(sprintf(_("Error saving alias. %s"), $alias_id->getMessage()), 'horde.error');
            // remove the mode, and rearrange the alias information to clean
            // up the form.
            $vars->remove('mode');
            $vars->add('retry', true);
            if ($vars->exists('alias')) {
                $vars->remove('alias_address');
            } elseif ($vars->exists('address')) {
                $vars->remove('alias_address');
                $vars->remove('alias');
            }
        } else {
            $notification->push(_("Alias saved."), 'horde.success');
            Horde::url('users/index.php')
                ->add('domain_id', $domain['id'])
                ->redirect();
        }
    }
}

/* Check if a form is being edited. */
if (!$vars->exists('mode') || $vars->getExists('retry')) {
    Horde::logMessage("No-Submit Detected: " . print_r(serialize($vars), true), 'DEBUG');
    if ($vars->exists("alias")) {
        $alias = $vars->get("alias");
        Horde::logMessage("Alias Detected: $alias", 'DEBUG');

        $addrInfo = $vilma->driver->getAddressInfo($alias,'alias');
        Horde::logMessage("addrInfo contains: " . print_r($addrInfo, true), 'DEBUG');
        if (is_a($addrInfo, 'PEAR_Error')) {
            $notification->push(sprintf(_("Error reading address information from backend: %s"), $addrInfo->getMessage()), 'horde.error');
            $url = '/users/index.php';
            require VILMA_BASE . $url;
            exit;
        }
        $address = $vilma->driver->getAddressInfo($addrInfo['destination']);
        Horde::logMessage("address Info contains: " . print_r($address, true), 'DEBUG');
        $vars = new Variables($address);
        $vars->set('mode', 'edit');
        $vars->add('alias_address', $alias);
        $vars->add('alias', $alias);
        $vars->add('address', $address['address']);
    } elseif ($vars->exists("address")) {
        $tmp_address = $vars->get("address");
        Horde::logMessage("Address Detected: $tmp_address", 'DEBUG');

        $address = $vilma->driver->getAddressInfo($tmp_address, 'all');
        Horde::logMessage("addrInfo contains: " . print_r($addrInfo, true), 'DEBUG');
        $vars = new Variables($address);
        $vars->set('mode', 'new');
    }

    $form = &new EditAliasForm($vars);
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
require_once 'Horde/Form/Renderer.php';
$renderer = &new Horde_Form_Renderer();

Horde::startBuffer();
$form->renderActive($renderer, $vars, 'editAlias.php', 'post');
$main = Horde::endBuffer();

$template->set('main', $main);
$template->set('menu', Vilma::getMenu('string'));

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require VILMA_TEMPLATES . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
