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

/* Only admin should be using this. */
if (!Vilma::hasPermission($domain)) {
    $registry->authenticateFailure('vilma');
}

$vars = Horde_Variables::getDefaultVariables();
$address = $vars->address;
$section = $vars->get('section', 'all');
$domain = Vilma::stripDomain($address);

/* Check if a form is being edited. */
if (!isset($vars->mode)) {
    if ($address) {
        try {
            $address = $vilma->driver->getAddressInfo($address, $section);
        } catch (Exception $e) {
            $notification->push(sprintf(_("Error reading address information from backend: %s"), $e->getMessage()), 'horde.error');
            Horde::url('users/index.php', true)->redirect();
        }
        $vars = new Horde_Variables($address);
        $user_name = $vars->get('user_name');
        $vars->user_name = Vilma::stripUser($user_name);
        $domain = Vilma::stripDomain($user_name);
        $vars->domain = $domain;
        $vars->type = $address['type'];
        $vars->target = 'test'; //$address['target']);
        $vars->mode = 'edit';
    } else {
        $vars->mode = 'new';
        $domain_info = $session->get('vilma', 'domain');
        $domain = $domain_info['domain_name'];
        $domain_id = $domain_info['domain_id'];
        $vars->domain = $domain;
        $vars->id = $domain_id;
        $vars->add('user_name', Horde_Util::getFormData('user_name', ''));
    }
}

$domain = Vilma::stripDomain($address['address']);
$tmp = $vars->domain;
if (!$tmp) {
    $vars->domain = $domain;
}

if (!isset($vars->id) && !$vilma->driver->isBelowMaxUsers($domain)) {
    $notification->push(sprintf(_("\"%s\" already has the maximum number of users allowed."), $domain), 'horde.error');
            Horde::url('users/index.php', true)->redirect();
}

$form = new Vilma_Form_EditUser($vars);
if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    $info['user_name'] = Horde_String::lower($info['user_name']) . '@' . $domain;
    try {
        $vilma->driver->saveUser($info);
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
    } catch (Exception $e) {
        $notification->push(sprintf(_("Error saving user. %s"), $e->getMessage()), 'horde.error');
    }
}

/* Render the form. */
$renderer = new Horde_Form_Renderer();

$template = $injector->createInstance('Horde_Template');

Horde::startBuffer();
$form->renderActive($renderer, $vars, 'edit.php', 'post');
$template->set('main', Horde::endBuffer());

$template->set('menu', Horde::menu());

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require $registry->get('templates', 'horde') . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
