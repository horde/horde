<?php
/**
 * The Null implementation of Horde_Core_Auth_Signup.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Core
 */
class Horde_Core_Auth_Signup_Null extends Horde_Core_Auth_Signup_Base
{
    /**
     * Queues the user's submitted registration info for later admin approval.
     *
     * @param mixed $info  Reference to array of parameters to be passed
     *                     to hook.
     */
    protected function _queueSignup($info)
    {
    }

    /**
     * Get a user's queued signup information.
     *
     * @param string $username  The username to retrieve the queued info for.
     *
     * @return object  The object for the requested signup.
     */
    public function getQueuedSignup($username)
    {
        return new Horde_Support_Stub();
    }

    /**
     * Get the queued information for all pending signups.
     *
     * @return array  An array of objects, one for each signup in the queue.
     */
    public function getQueuedSignups()
    {
        return array();
    }

    /**
     * Remove a queued signup.
     *
     * @param string $username  The user to remove from the signup queue.
     */
    public function removeQueuedSignup($username)
    {
    }

    /**
     * Return a new signup object.
     *
     * @param string $name  The signups's name.
     *
     * @return object  A new signup object.
     */
    public function newSignup($name)
    {
        return new Horde_Support_Stub();
    }

}
