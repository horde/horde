<?php
/**
 * The Vilma script to add/edit users.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @author David Cummings <davidcummings@acm.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$vilma = Horde_Registry::appInit('vilma');

require_once VILMA_BASE . '/lib/Forms/EditUserForm.php';

/* Only admin should be using this. */
if (!Vilma::hasPermission($domain)) {
    $registry->authenticateFailure('vilma', $e);
}
$vars = Horde_Variables::getDefaultVariables();
$address = $vars->get('address');
$section = Horde_Util::getFormData('section','all');

//$addrInfo = $vilma->driver->getAddressInfo($address, 'all');
$domain = Vilma::stripDomain($address);

/* Check if a form is being edited. */
if (!$vars->exists('mode')) {
    if ($address) {
        $address = $vilma->driver->getAddressInfo($address,$section);
        if (is_a($address, 'PEAR_Error')) {
            $notification->push(sprintf(_("Error reading address information from backend: %s"), $address->getMessage()), 'horde.error');
            $url = '/users/index.php';
            require VILMA_BASE . $url;
            exit;
        }
        $vars = new Horde_Variables($address);
        $user_name = //$vars->get('address');
                     $vars->get('user_name');
        $vars->set('user_name', Vilma::stripUser($user_name));
        $domain = Vilma::stripDomain($user_name);
        $vars->set('domain', $domain);
        $vars->set('type', $address['type']);
        $vars->set('target', 'test'); //$address['target']);
        $vars->set('mode', 'edit');
    } else {
        $vars->set('mode', 'new');
        $domain_info = $session->get('vilma', 'domain');
        $domain = $domain_info['domain_name'];
        $domain_id = $domain_info['domain_id'];
        $vars->set('domain', $domain);
        $vars->set('id', $domain_id);
        $vars->add('user_name', Horde_Util::getFormData('user_name',''));
    }
}

$domain = Vilma::stripDomain($address['address']);
$tmp = $vars->get('domain');
if(!isset($tmp)) {
    $vars->set('domain', $domain);
}
$form = &new EditUserForm($vars);
if (!$vars->exists('id') && !$vilma->driver->isBelowMaxUsers($domain)) {
    $notification->push(sprintf(_("\"%s\" already has the maximum number of users allowed."), $domain), 'horde.error');
    require VILMA_BASE . '/users/index.php';
    exit;
}
if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    $info['user_name'] = Horde_String::lower($info['user_name']) . '@' . $domain;
    $user_id = $vilma->driver->saveUser($info);
    if (is_a($user_id, 'PEAR_Error')) {
        Horde::logMessage($user_id, 'ERR');
        $notification->push(sprintf(_("Error saving user. %s"), $user_id->getMessage()), 'horde.error');
    } else {
        $notification->push(_("User details saved."), 'horde.success');
        /*
        $virtuals = $vilma->driver->getVirtuals($info['name']);
        if (count($virtuals)) {
            //User has virtual email addresses set up.
            $url = Horde::url('users/index.php', true);
            header('Location: ' . (Vilma::hasPermission($domain) ? $url : Horde_Util::addParameter($url, 'domain', $domain, false)));
        } else {
            //User does not have any virtual email addresses set up.
            $notification->push(_("No virtual email address set up for this user. You should set up at least one virtual email address if this user is to receive any emails."), 'horde.warning');
            $url = Horde::url('virtuals/edit.php', true);
            $url = Horde_Util::addParameter($url, array('domain' => $domain, 'stripped_email' => Vilma::stripUser($info['name']), 'virtual_destination' => $info['name']), null, false);
            header('Location: ' . (Vilma::hasPermission($domain) ? $url : Horde_Util::addParameter($url, 'domain', $domain, false)));
        }
        */
        //exit;
    }
}

/* Render the form. */
require_once 'Horde/Form/Renderer.php';
$renderer = &new Horde_Form_Renderer();

Horde::startBuffer();
$form->renderActive($renderer, $vars, 'edit.php', 'post');
$main = Horde::endBuffer();

$template = $injector->createInstance('Horde_Template');
$template->set('main', $main);
$template->set('menu', Vilma::getMenu('string'));

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require $registry->get('templates', 'horde') . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
