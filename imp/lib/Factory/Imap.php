<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based IMP_Imap factory.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Imap extends Horde_Core_Factory_Base
{
    /**
     * Default server ID to use.
     *
     * @var string
     */
    public $defaultID;

    /**
     * Default instance. Used before successful authentication.
     *
     * @var IMP_Imap
     */
    private $_default = null;

    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     */
    public function __construct(Horde_Injector $injector)
    {
        parent::__construct($injector);

        $this->defaultID = $GLOBALS['session']->get('imp', 'server_key');
    }

    /**
     * Return the IMP_Imap instance.
     *
     * @param string $id  The server ID.
     *
     * @return IMP_Imap  The singleton instance.
     * @throws IMP_Exception
     */
    public function create($id = null)
    {
        global $session;

        if (is_null($id)) {
            $id = $this->defaultID;
        }

        if (is_null($id)) {
            if (!isset($this->_default)) {
                $this->_default = new IMP_Imap();
            }
            return $this->_default;
        }

        if (isset($this->_instances[$id])) {
            return $this->_instances[$id];
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
            $session->set('imp', 'imap_ob/' . $id, $ob);
        }

        if (empty($this->_instances)) {
            register_shutdown_function(array($this, 'shutdown'));
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
                if ($ob->changed) {
                    $GLOBALS['session']->set('imp', 'imap_ob/' . $id, $ob);
                }
            }
        }
    }

}
