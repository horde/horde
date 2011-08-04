<?php
/**
 * A Horde_Injector:: based IMP_Imap:: factory.
 *
 * PHP version 5
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */

/**
 * A Horde_Injector:: based IMP_Imap:: factory.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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
        global $registry, $session;

        if (is_null($id) &&
            !($id = $session->get('imp', 'server_key')) &&
            !($id = $this->_authInstance)) {
            // TODO: Sometimes, on relogin, a session can be created but
            // 'server_key' is missing from the session. Thus, a default
            // IMP_Imap object is used instead of the IMP_Imap object that
            // contains a Horde_Imap_Client_Base object. I am unsure of why
            // this happens - for now, check for auth credentials and re-add
            // server_key to the session.
            if ($id = $registry->getAuthCredential('imp_server_key', 'imp')) {
                $session->set('imp/server_key', $id);
            } else {
                $id = 'default';
            }
        }

        if (!isset($this->_instances[$id])) {
            if (empty($this->_instances)) {
                register_shutdown_function(array($this, 'shutdown'));
            }

            if ($ob = $session->get('imp', 'imap_ob/' . $id)) {
                /* If retrieved from session, we know $save should implcitly
                 * be true. */
                $save = true;
            } else {
                $ob = new IMP_Imap();
                $this->_authInstance = $id;
            }

            $this->_instances[$id] = $ob;
        }

        if ($save) {
            $this->_save[] = $id;
        }

        return $this->_instances[$id];
    }

    /**
     * Saves IMP_Imap instances to the session.
     */
    public function shutdown()
    {
        foreach (array_unique($this->_save) as $id) {
            if ($this->_instances[$id]->changed) {
                $GLOBALS['session']->set('imp', 'imap_ob/' . $id, $this->_instances[$id]);
            }
        }
    }

}
