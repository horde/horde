<?php
/**
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/base.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

// Make sure signups are enabled before proceeding
$auth = Horde_Auth::singleton($conf['auth']['driver']);
if ($conf['signup']['allow'] !== true ||
    !$auth->hasCapability('add')) {
    throw new Horde_Exception(_("User Registration has been disabled for this site."));
}
$signup = Horde_Auth_Signup::factory();
if (is_a($signup, 'PEAR_Error')) {
    Horde::logMessage($signup, __FILE__, __LINE__, PEAR_LOG_ERR);
    throw new Horde_Exception(_("User Registration is not properly configured for this site."));
}

// Verify hash.
$user = Horde_Util::getFormData('u');
$hash = Horde_Util::getFormData('h');
$action = Horde_Util::getFormData('a');
if (hash_hmac('sha1', $user, $conf['secret_key']) != $hash) {
    throw new Horde_Exception(_("Invalid hash."));
}

// Deny signup.
if ($action == 'deny') {
    $result = $signup->removeQueuedSignup($user);
    if (is_a($result, 'PEAR_Error')) {
        throw new Horde_Exception($result);
    }
    printf(_("The signup request for user \"%s\" has been removed."), $user);
    exit;
}
if ($action != 'approve') {
    throw new Horde_Exception(sprintf(_("Invalid action %s"), $action));
}

// Read and verify user data.
$thisSignup = $signup->getQueuedSignup($user);
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
if (is_a($ret = $auth->addUser($info['user_name'], $credentials), 'PEAR_Error')) {
    throw new Horde_Exception(sprintf(_("There was a problem adding \"%s\" to the system: %s"), $info['user_name'], $ret->getMessage()));
}
if (isset($info['extra'])) {
    try {
        Horde::callHook('signup_addextra', array($info['user_name'], $info['extra']));
    } catch (Horde_Exception $e) {
        throw new Horde_Exception(sprintf(_("Added \"%s\" to the system, but could not add additional signup information: %s."), $info['user_name'], $e->getMessage()));
    } catch (Horde_Exception_HookNotSet $e) {}
}
$signup->removeQueuedSignup($user);

echo sprintf(_("Successfully added \"%s\" to the system."), $info['user_name']);
