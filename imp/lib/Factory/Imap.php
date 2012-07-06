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
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the IMP_Imap:: instance.
     *
     * @param string $id  The server ID.
     *
     * @return IMP_Imap  The singleton instance.
     * @throws IMP_Exception
     */
    public function create($id = null)
    {
        global $injector, $registry, $session;

        if (is_null($id) &&
            !($id = $session->get('imp', 'server_key'))) {
            $id = 'default';
        }

        if (isset($this->_instances[$id])) {
            return $this->_instances[$id];
        }

        if (empty($this->_instances)) {
            register_shutdown_function(array($this, 'shutdown'));
        }

        try {
            $ob = $session->get('imp', 'imap_ob/' . $id);
        } catch (Exception $e) {
            // This indicates an unserialize() error.  This is fatal, so
            // logout.
            throw new Horde_Exception_AuthenticationFailure('', Horde_Auth::REASON_SESSION);
        }

        if (!$ob) {
            $ob = new IMP_Imap();

            /* Explicitly save object when first creating. Prevents losing
             * authentication information in case a misconfigured server
             * crashes before shutdown operations can occur. */
            if ($id != 'default') {
                $session->set('imp', 'imap_ob/' . $id, $ob);
            }
        }

        $this->_instances[$id] = $ob;

        return $this->_instances[$id];
    }

    /**
     * Saves IMP_Imap instances to the session on shutdown.
     */
    public function shutdown()
    {
        if ($GLOBALS['registry']->getAuth() !== false) {
            foreach ($this->_instances as $id => $ob) {
                if (($id != 'default') && $ob->changed) {
                    $GLOBALS['session']->set('imp', 'imap_ob/' . $id, $ob);
                }
            }
        }
    }

}
