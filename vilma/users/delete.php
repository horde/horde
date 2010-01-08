<?php
/**
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

@define('VILMA_BASE', dirname(__FILE__) . '/..');
require_once VILMA_BASE . '/lib/base.php';
require_once 'Horde/Form.php';

/* Only admin should be using this. */
if (!Vilma::hasPermission($domain)) {
    Horde_Auth::authenticateFailure('vilma', $e);
}

$vars = Horde_Variables::getDefaultVariables();
$address = $vars->get('address');
$section = Horde_Util::getFormData('section','all');

//$addrInfo = $vilma_driver->getAddressInfo($address, 'all');
/*
$user_id = $vars->get('address');
$formname = $vars->get('formname');
$user = $vilma_driver->getUser($user_id);
print_r($vars) . '<br />';
echo $user_id . '<br />';
echo $fromname . '<br />';
print_r($user) . '<br />';
$domain = Vilma::stripDomain($user['name']);
$domain = $vilma_driver->getDomainByName($domain);
*/
$address = $vilma_driver->getAddressInfo($address);
$type = $address['type'];
if(($section == 'all') && ($type == 'alias')) {
    $address = $vilma_driver->getAddressInfo($vars->get('address'),$type);
}
$user_id = $vars->get('address');
$user = $vilma_driver->getUser($user_id);
$aliases = $vilma_driver->_getAliases($user_id);
$aliasesCount = 0;
if(is_array($aliases)) {
    $aliasesCount = sizeof($aliases);
}
$domain = Vilma::stripDomain($user_id);
$forwards = $vilma_driver->_getGroupsAndForwards('forwards',$domain);
$forwardsCount = 0;
foreach($forwards as $entry) {
    foreach($entry['targets'] as $target) {
        if($user_id === $target) {
            $forwardsCount++;
        }
    }
}
$groups = $vilma_driver->_getGroupsAndForwards('groups',$domain);
$groupsCount = 0;
foreach($groups as $entry) {
    foreach($entry['targets'] as $target) {
        if($user_id === $target) {
            $groupsCount++;
        }
    }
}
if (is_a($address, 'PEAR_Error')) {
    $notification->push(sprintf(_("Error reading address information from backend: %s"), $address->getMessage()), 'horde.error');
    $url = '/users/index.php';
    require VILMA_BASE . $url;
    exit;
}
$user_name = $address['user_name'];
if(!isset($user_name) || empty($user_name)) {
    $user_name = $address['address'];
}
$vars->set('user_name', Vilma::stripUser($user_name));
$domain = Vilma::stripDomain($address);
$domain = $vilma_driver->getDomainByName($domain);
$vars->set('domain', $domain);
$vars->set('mode', 'edit');

$form = new Horde_Form($vars, _("Delete " . $type));
/* Set up the form. */
$form->setButtons(array(_("Delete"), _("Do not delete")));
//$form->addHidden($user_id, 'user_id', 'text', false);
$form->addHidden($address['address'], 'address', 'text', false);
$form->addHidden($section, 'section', 'text', false);

$desc = "Delete $type \"%s\"";
$sub = " and all dependencies?";
$tot = $aliasesCount + $groupsCount + $forwardsCount;
if($tot > 0) {
    $desc .= $sub;
} else {
    $desc .= "?";
}
if($aliasesCount > 0) {
    $desc .= " Account has " . $aliasesCount . " aliases.";
}
if($forwardsCount > 0) {
    $desc .= " Account is the target of  " . $forwardsCount . " forward(s).";
}
if($groupsCount > 0) {
    $desc .= " Account belongs to  " . $groupsCount . " group(s).";
}
$form->addVariable(sprintf(_($desc), $user_name), 'description', 'description', false);
if ($vars->get('submitbutton') == _("Delete")) {
    if($type == 'alias') {
        if ($form->validate($vars)) {
            $form->getInfo($vars, $info);
            $deleteInfo = array();
             $deleteInfo['address'] = $address['destination'];
             $deleteInfo['alias'] = $user_id;
             $delete = $vilma_driver->deleteAlias($deleteInfo);
            if (is_a($delete, 'PEAR_Error')) {
                Horde::logMessage($delete, __FILE__, __LINE__, PEAR_LOG_ERR);
                 $notification->push(sprintf(_("Error deleting alias. %s."), $delete->getMessage()), 'horde.error');
                 $url = Util::addParameter(Horde::applicationUrl('users/index.php'), 'domain_id', $domain['id'], false);
                 header('Location: ' . $url);
                 exit;
             } else {
                 $notification->push(_("Alias deleted."), 'horde.success');
                 $url = Util::addParameter(Horde::applicationUrl('users/index.php'), 'domain_id', $domain['id'], false);
                 header('Location: ' . $url);
                 exit;
             }
         }
     } elseif ($type == 'forward') {
      if ($form->validate($vars)) {
            $form->getInfo($vars, $info);
            $deleteInfo = array();
            $deleteInfo['address'] = $address['destination'];
            $deleteInfo['forward'] = $user_id;
            $delete = $vilma_driver->deleteForward($deleteInfo);
            if (is_a($delete, 'PEAR_Error')) {
                Horde::logMessage($delete, __FILE__, __LINE__, PEAR_LOG_ERR);
                $notification->push(sprintf(_("Error deleting forward. %s."), $delete->getMessage()), 'horde.error');
                $url = Horde_Util::addParameter(Horde::applicationUrl('users/index.php'), 'domain_id', $domain['id'], false);
                header('Location: ' . $url);
                exit;
            } else {
                $notification->push(_("Forward deleted."), 'horde.success');
                $url = Horde_Util::addParameter(Horde::applicationUrl('users/index.php'), 'domain_id', $domain['id'], false);
                header('Location: ' . $url);
                exit;
            }
        }
    } else {
        if ($form->validate($vars)) {
            $form->getInfo($vars, $info);
            //$delete = $vilma_driver->deleteUser($info['user_id']);
            $delete = $vilma_driver->deleteUser($address['address']);
            if (is_a($delete, 'PEAR_Error')) {
                Horde::logMessage($delete, __FILE__, __LINE__, PEAR_LOG_ERR);
                $notification->push(sprintf(_("Error deleting user. %s."), $delete->getMessage()), 'horde.error');
                $url = Horde_Util::addParameter(Horde::applicationUrl('users/index.php'), 'domain_id', $domain['id'], false);
                header('Location: ' . $url);
                exit;
            } else {
                $notification->push(_("$type deleted."), 'horde.success');
                $url = Horde_Util::addParameter(Horde::applicationUrl('users/index.php'), 'domain_id', $domain['id'], false);
                header('Location: ' . $url);
                exit;
            }
        }
    }
} elseif ($vars->get('submitbutton') == _("Do not delete")) {
    $notification->push(_("User not deleted."), 'horde.message');
    $url = Horde_Util::addParameter(Horde::applicationUrl('users/index.php'), 'domain_id', $domain['domain_id'], false);
    header('Location: ' . $url);
    exit;
}

/* Render the form. */
require_once 'Horde/Form/Renderer.php';
$renderer = &new Horde_Form_Renderer();
$main = Horde_Util::bufferOutput(array($form, 'renderActive'), $renderer, $vars, 'delete.php', 'post');

$template->set('main', $main);
$template->set('menu', Vilma::getMenu('string'));
$template->set('notify', Horde_Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require VILMA_TEMPLATES . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
