<?php
/**
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Horde
 */

require_once __DIR__ . '/../lib/base.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

$vars = $injector->getInstance('Horde_Variables');

// Make sure signups are enabled before proceeding
$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();
if ($conf['signup']['allow'] !== true ||
    !$auth->hasCapability('add')) {
    throw new Horde_Exception(_("User Registration has been disabled for this site."));
}

try {
    $signup = $injector->getInstance('Horde_Core_Auth_Signup');
} catch (Horde_Exception $e) {
    Horde::logMessage($e, 'ERR');
    throw new Horde_Exception(_("User Registration is not properly configured for this site."));
}

// Verify hash.
if (hash_hmac('sha1', $vars->u, $conf['secret_key']) != $vars->h) {
    throw new Horde_Exception(_("Invalid hash."));
}

// Deny signup.
if ($vars->a == 'deny') {
    $signup->removeQueuedSignup($vars->u);
    printf(_("The signup request for user \"%s\" has been removed."), $vars->u);
    exit;
}
if ($vars->a != 'approve') {
    throw new Horde_Exception(sprintf(_("Invalid action %s"), $vars->a));
}

// Read and verify user data.
$thisSignup = $signup->getQueuedSignup($vars->u);
$info = $thisSignup->getData();

if (empty($info['user_name']) && isset($info['extra']['user_name'])) {
    $info['user_name'] = $info['extra']['user_name'];
}
if (empty($info['password']) && isset($info['extra']['password'])) {
    $info['password'] = $info['extra']['password'];
}
if (empty($info['user_name'])) {
    throw new Horde_Exception(_("No username specified."));
}
if ($auth->exists($info['user_name'])) {
    throw new Horde_Exception(sprintf(_("The user \"%s\" already exists."), $info['user_name']));
}

$credentials = array('password' => $info['password']);
if (isset($info['extra'])) {
    foreach ($info['extra'] as $field => $value) {
        $credentials[$field] = $value;
    }
}

// Add user.
try {
     $auth->addUser($info['user_name'], $credentials);
} catch (Horde_Auth_Exception $e) {
    throw new Horde_Exception(sprintf(_("There was a problem adding \"%s\" to the system: %s"), $info['user_name'], $e->getMessage()));
}
if (isset($info['extra'])) {
    try {
        Horde::callHook('signup_addextra', array($info['user_name'], $info['extra']));
    } catch (Horde_Exception $e) {
        throw new Horde_Exception(sprintf(_("Added \"%s\" to the system, but could not add additional signup information: %s."), $info['user_name'], $e->getMessage()));
    } catch (Horde_Exception_HookNotSet $e) {}
}
$signup->removeQueuedSignup($vars->u);

echo sprintf(_("Successfully added \"%s\" to the system."), $info['user_name']);
