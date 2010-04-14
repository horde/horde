<?php
/**
 * The Horde_Auth_Kolab implementation of the Horde authentication system.
 * Derives from the Horde_Auth_Imap authentication object, and provides
 * parameters to it based on the global Kolab configuration.
 *
 * Copyright 2004-2007 Stuart Binge <s.binge@codefusion.co.za>
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Stuart Binge <s.binge@codefusion.co.za>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Auth
 */
class Horde_Auth_Kolab extends Horde_Auth_Base
{
    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array
     */
    protected $_capabilities = array(
        'authenticate'  => true,
        'list'          => true,
        /** @todo These would only work if we allow manager login */
        'add'           => false,
        'remove'        => false,
        'update'        => false
    );

    /**
     * Find out if a set of login credentials are valid.
     *
     * For Kolab this requires to identify the IMAP server the user should
     * be authenticated against before the credentials can be checked using
     * this server. The Kolab_Server module handles identification of the
     * correct IMAP server.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  An array of login credentials. For Kolab,
     *                            this must contain a "password" entry.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        try {
            $session = $GLOBALS['injector']->getInstance('Horde_Kolab_Session');
            $session->connect($userId, $credentials);
        } catch (Horde_Kolab_Session_Exception_Badlogin $e) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        } catch (Horde_Kolab_Session_Exception $e) {
            Horde::logMessage($e, 'ERR');
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        $this->_credentials['userId'] = $session->getMail();

        return true;

/*         if (!isset($conf['auth']['params']) || */
/*             $conf['auth']['params']['login_block'] != 1) { */
/*             // Return if feature is disabled. */
/*             return $session->auth; */
/*         } */

/*         if ($session->auth !== true && */
/*             class_exists('Horde_History')) { */
/*             $history = $GLOBALS['injector']->getInstance('Horde_History'); */

/*             $history_identifier = "$userId@logins.kolab"; */
/*             $history_log = $history->getHistory($history_identifier); */
/*             $history_list = array(); */

/*             // Extract history list from log. */
/*             if ($history_log && !($history_log instanceof PEAR_Error)) { */
/*                 $data = $history_log->getData(); */
/*                 if (!empty($data)) { */
/*                     $entry = array_shift($data); */
/*                     $history_list = $entry['history_list']; */
/*                 } */
/*             } */

/*             // Calculate the time range. */
/*             $start_time = (time() - $conf['auth']['params']['login_block_time'] * 60); */

/*             $new_history_list = array(); */
/*             $count = 0; */

/*             // Copy and count all relevant timestamps. */
/*             foreach ($history_list as $entry) { */
/*                 $timestamp = $entry[ 'timestamp' ]; */

/*                 if ($timestamp > $start_time) { */
/*                     $new_history_list[] = $entry; */
/*                     $count++; */
/*                 } */
/*             } */

/*             $max_count = $conf['auth']['params']['login_block_count']; */

/*             if ($count > $max_count) { */
/*                 // Add entry for current failed login. */
/*                 $entry = array(); */
/*                 $entry[ 'timestamp' ] = time(); */
/*                 $new_history_list[] = $entry; */

/*                 // Write back history. */
/*                 $history->log($history_identifier, */
/*                               array('action' => 'add', 'who' => $userId, */
/*                                     'history_list' => $new_history_list), true); */

/*                 if ($count > $max_count) { */
/*                     throw new Horde_Auth_Exception(_("Too many invalid logins during the last minutes.")); */
/*                 } */

/*                 throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN); */
/*             } */
/*         } */

/*         return ($session->auth === true); */
    }

/*     /\** */
/*      * List Users */
/*      * */
/*      * @return array  List of Users */
/*      * @throws Horde_Auth_Exception */
/*      *\/ */
/*     public function listUsers() */
/*     { */
/*         $session = Horde_Kolab_Session_Singleton::singleton(); */
/*         $server = $session->getServer(); */
/*         if ($server instanceof PEAR_Error) { */
/*             return $server; */
/*         } */
/*         $users = $server->listObjects(KOLAB_OBJECT_USER); */
/*         $mails = array(); */
/*         foreach ($users as $user) { */
/*             $mails[] = $user->get(KOLAB_ATTR_MAIL); */
/*         } */

/*         return $mails; */
/*     } */

/*     /\** */
/*      * Add a set of authentication credentials. */
/*      * */
/*      * @param string $userId      The userId to add. */
/*      * @param array $credentials  The credentials to be set. */
/*      * */
/*      * @throws Horde_Auth_Exception */
/*      *\/ */
/*     public function addUser($userId, $credentials) */
/*     { */
/*         $session = Horde_Kolab_Session::singleton(); */
/*         $server = $session->getServer(); */
/*         if ($server instanceof PEAR_Error) { */
/*             return $server; */
/*         } */

/*         $result = $server->store(KOLAB_OBJECT_USER, $userId, $credentials); */

/*         if (is_a($result, KOLAB_OBJECT_USER)) { */
/*             return true; */
/*         } else if ($result instanceof PEAR_Error) { */
/*             return $result; */
/*         } */

/*         throw new Horde_Auth_Exception(sprintf('The new Kolab object is a %s rather than a ' . KOLAB_OBJECT_USER, get_class($result))); */
/*     } */

}
