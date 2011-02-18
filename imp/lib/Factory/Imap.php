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
        global $session;

        if (is_null($id) &&
            !($id = $session->get('imp', 'server_key'))) {
            $id = 'default';
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
            if ($this->_instances[$id]->ob->changed) {
                $GLOBALS['session']->set('imp', 'imap_ob/' . $id, $this->_instances[$id]);
            }
        }
    }

}
