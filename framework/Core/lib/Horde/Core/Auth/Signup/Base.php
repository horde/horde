<?php
/**
 * This class defines the abstract driver implementation for
 * Horde_Core_Auth_Signup.
 *
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Core
 */
abstract class Horde_Core_Auth_Signup_Base
{
    /**
     * Adds a new user to the system and handles any extra fields that may have
     * been compiled, relying on the hooks.php file.
     *
     * @param mixed $info  Reference to array of parameters to be passed
     *                     to hook.
     *
     * @throws Horde_Exception
     */
    public function addSignup(&$info)
    {
        /* @var $auth Horde_Auth_Base */
        /* @var $injector Horde_Injector */
        global $auth, $injector;

        // Perform any preprocessing if requested.
        $this->_preSignup($info);

        // Attempt to add the user to the system.
        $auth->addUser($info['user_name'], array('password' => $info['password']));

        // Attempt to add/update any extra data handed in.
        if (!empty($info['extra'])) {
            try {
                $injector->getInstance('Horde_Core_Hooks')->callHook(
                    'signup_addextra',
                    'horde',
                    array(
                        $info['user_name'],
                        $info['extra'],
                        $info['password']
                    )
                );
            } catch (Horde_Exception_HookNotSet $e) {}
        }
    }

    /**
     * Queues the user's submitted registration info for later admin approval.
     *
     * @param mixed $info  Reference to array of parameters to be passed
     *                     to hook.
     *
     * @throws Horde_Exception
     * @throws Horde_Mime_Exception
     */
    public function queueSignup(&$info)
    {
        /* @var $conf array */
        /* @var $injector Horde_Injector */
        /* @var $registry Horde_Registry */
        global $conf, $injector, $registry;

        // Perform any preprocessing if requested.
        $this->_preSignup($info);

        // If it's a unique username, go ahead and queue the request.
        $signup = $this->newSignup($info['user_name']);
        if (!empty($info['extra'])) {
            $signup->setData($info['extra']);
        }
        $signup->setData(array_merge($signup->getData(), array(
            'dateReceived' => time(),
            'password' => $info['password'],
        )));

        $this->_queueSignup($signup);

        try {
            $injector->getInstance('Horde_Core_Hooks')->callHook(
                'signup_queued',
                'horde',
                array(
                    $info['user_name'],
                    $info
                )
            );
        } catch (Horde_Exception_HookNotSet $e) {}

        if (!empty($conf['signup']['email'])) {
            $link = Horde::url($registry->get('webroot', 'horde') . '/admin/signup_confirm.php', true, -1)->setRaw(true)->add(array(
                'u' => $signup->getName(),
                'h' => hash_hmac('sha1', $signup->getName(), $conf['secret_key'])
            ));
            $message = sprintf(Horde_Core_Translation::t("A new account for the user \"%s\" has been requested through the signup form."), $signup->getName())
                . "\n\n"
                . Horde_Core_Translation::t("Approve the account:")
                . "\n" . $link->copy()->add('a', 'approve') . "\n"
                . Horde_Core_Translation::t("Deny the account:")
                . "\n" . $link->copy()->add('a', 'deny');
            $mail = new Horde_Mime_Mail(array(
                'body' => $message,
                'Subject' => sprintf(Horde_Core_Translation::t("Account signup request for \"%s\""), $signup->getName()),
                'To' => $conf['signup']['email'],
                'From' => $conf['signup']['email']));
            $mail->send($injector->getInstance('Horde_Mail'));
        }
    }

    /**
     * Perform common presignup actions.
     *
     * @param array $info  Reference to array of parameters.
     *
     * @throws Horde_Exception
     */
    protected function _preSignup(&$info)
    {
        /* @var $auth Horde_Auth_Base */
        /* @var $injector Horde_Injector */
        global $auth, $injector;

        try {
            $info = $injector->getInstance('Horde_Core_Hooks')->callHook(
                'signup_preprocess',
                'horde',
                array($info)
            );
        } catch (Horde_Exception_HookNotSet $e) {}

        // Check to see if the username already exists in the auth backend or
        // the signup queue.
        if ($auth->exists($info['user_name']) ||
            $this->exists($info['user_name'])) {
            throw new Horde_Exception(sprintf(Horde_Core_Translation::t("Username \"%s\" already exists."), $info['user_name']));
        }
    }

    /**
     * Checks if a user exists in the system.
     *
     * @param string $user  The user to check.
     *
     * @return boolean  True if the user exists.
     * @throws Horde_Db_Exception
     */
    abstract public function exists($user);

    /**
     * Queues the user's submitted registration info for later admin approval.
     *
     * @param object $signup  Signup data.
     *
     * @throws Horde_Exception
     */
    abstract protected function _queueSignup($signup);

    /**
     * Get a user's queued signup information.
     *
     * @param string $username  The username to retrieve the queued info for.
     *
     * @return object  The object for the requested signup.
     * @throws Horde_Exception
     */
    abstract public function getQueuedSignup($username);

    /**
     * Get the queued information for all pending signups.
     *
     * @return array  An array of objects, one for each signup in the queue.
     * @throws Horde_Exception
     */
    abstract public function getQueuedSignups();

    /**
     * Remove a queued signup.
     *
     * @param string $username  The user to remove from the signup queue.
     *
     * @throws Horde_Exception
     */
    abstract public function removeQueuedSignup($username);

    /**
     * Return a new signup object.
     *
     * @param string $name  The signups's name.
     *
     * @return object  A new signup object.
     * @throws Horde_Exception
     */
    abstract public function newSignup($name);
}
