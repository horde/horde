<?php
/**
 * A Horde_Injector:: based IMP_Imap:: factory.
 *
 * PHP version 5
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */

/**
 * A Horde_Injector:: based IMP_Imap:: factory.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */
class IMP_Factory_Imap extends Horde_Core_Factory_Base
{
    /**
     * Name of the instance used for the initial authentication.
     * Needed because the session may not be setup yet to indicate the
     * default instance to use.
     *
     * @var string
     */
    private $_authInstance;

    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * The list of instances to save.
     *
     * @var array
     */
    private $_save = array();

    /**
     * Return the IMP_Imap:: instance.
     *
     * @param string $id     The server ID.
     * @param boolean $save  Save the instance in the session?
     *
     * @return IMP_Imap  The singleton instance.
     * @throws IMP_Exception
     */
    public function create($id = null, $save = false)
    {
        global $injector, $registry, $session;

        if (is_null($id) &&
            !($id = $session->get('imp', 'server_key')) &&
            !($id = $this->_authInstance)) {
            $id = 'default';
        }

        if (!isset($this->_instances[$id])) {
            if (empty($this->_instances)) {
                register_shutdown_function(array($this, 'shutdown'));
            }

            try {
                $ob = $session->get('imp', 'imap_ob/' . $id);
            } catch (Exception $e) {
                // This indicates an unserialize() error.  This is fatal, so
                // logout.
                $injector->getInstance('Horde_Core_Factory_Auth')->create()->setError(Horde_Auth::REASON_SESSION);
                $registry->authenticateFailure('imp');
            }

            if ($ob) {
                /* If retrieved from session, we know $save should implicitly
                 * be true. */
                $this->_save[] = $id;
            } else {
                $ob = new IMP_Imap();
                $this->_authInstance = $id;
            }

            $this->_instances[$id] = $ob;
        }

        if ($save) {
            $this->_save[] = $id;
            /* Explicitly save object when first creating. Prevents losing
             * authentication information in case a misconfigured server
             * crashes before shutdown operations can occur. */
            $session->set('imp', 'imap_ob/' . $id, $this->_instances[$id]);
        }

        return $this->_instances[$id];
    }

    /**
     * Saves IMP_Imap instances to the session on shutdown.
     */
    public function shutdown()
    {
        if ($GLOBALS['registry']->getAuth() !== false) {
            foreach (array_unique($this->_save) as $id) {
                if ($this->_instances[$id]->changed) {
                    $GLOBALS['session']->set('imp', 'imap_ob/' . $id, $this->_instances[$id]);
                }
            }
        }
    }

}
