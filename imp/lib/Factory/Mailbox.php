<?php
/**
 * A Horde_Injector:: based IMP_Mailbox:: factory.
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
 * A Horde_Injector:: based IMP_Mailbox:: factory.
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
class IMP_Factory_Mailbox extends Horde_Core_Factory_Base
{
    const STORAGE_KEY = 'mbox/';

    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the IMP_Mailbox:: instance.
     *
     * @param string $mbox  The IMAP mailbox name.
     *
     * @return IMP_Mailbox  The singleton mailbox instance.
     * @throws IMP_Exception
     */
    public function create($mbox)
    {
        if ($mbox instanceof IMP_Mailbox) {
            return $mbox;
        }

        if (!isset($this->_instances[$mbox])) {
            if (empty($this->_instances)) {
                register_shutdown_function(array($this, 'shutdown'));
            }

            $ob = new IMP_Mailbox($mbox);
            $ob->cache = $GLOBALS['session']->get('imp', self::STORAGE_KEY . $mbox, Horde_Session::TYPE_ARRAY);

            $this->_instances[$mbox] = $ob;
        }

        return $this->_instances[$mbox];
    }

    /**
     * Saves IMP_Mailbox instances to the session.
     *
     * A bit hackish - it would theoretically be cleaner code if we just
     * stored a serialized version of the object in the value. However, this
     * incurs the overhead of 1) having to define the classname in each
     * serialized string, and 2) the mailbox name will be duplicated inside
     * of the object (since it is the same as the key). Since a user may
     * have 100's of mailboxes, we need to pack info as tightly as possible
     * in the session; thus, the slightly unorthodox way we store the
     * mailbox data in the session.
     */
    public function shutdown()
    {
        foreach ($this->_instances as $ob) {
            switch ($ob->changed) {
            case IMP_Mailbox::CHANGED_YES:
                $GLOBALS['session']->set('imp', self::STORAGE_KEY . $ob, $ob->cache);
                break;

            case IMP_Mailbox::CHANGED_DELETE:
                $GLOBALS['session']->remove('imp', self::STORAGE_KEY . $ob);
                break;
            }
        }
    }

}
