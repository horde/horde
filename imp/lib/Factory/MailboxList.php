<?php
/**
 * A Horde_Injector:: based IMP_Mailbox_List:: factory.
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
 * A Horde_Injector:: based IMP_Mailbox_List:: factory.
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
class IMP_Factory_MailboxList extends Horde_Core_Factory_Base
{
    const STORAGE_KEY = 'mboxlist/';

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

        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Return the mailbox list instance.
     * For IMP/MIMP, returns an IMP_Mailbox_List_Track object.
     * For DIMP/Mobile, returns an IMP_Mailbox_List object.
     *
     * @param string $mailbox       The mailbox name.
     * @param IMP_Indices $indices  An indices object. Only used for 'imp' and
     *                              'mimp' views.
     *
     * @return IMP_Mailbox_List  The singleton instance.
     * @throws IMP_Exception
     */
    public function create($mailbox, $indices = null)
    {
        $mbox_key = strval($mailbox);
        $mode = $GLOBALS['registry']->getView();

        if (!isset($this->_instances[$mbox_key])) {
            switch ($mode) {
            case Horde_Registry::VIEW_DYNAMIC:
            case Horde_Registry::VIEW_SMARTMOBILE:
                $ob = new IMP_Mailbox_List($mailbox);
                break;

            case Horde_Registry::VIEW_BASIC:
            case Horde_Registry::VIEW_MINIMAL:
                try {
                    $ob = $GLOBALS['session']->get('imp', self::STORAGE_KEY . $mailbox);
                } catch (Exception $e) {
                    $ob = null;
                }

                if (is_null($ob) ||
                    !($ob instanceof IMP_Mailbox_List_Track)) {
                    $ob = new IMP_Mailbox_List_Track($mailbox);
                }
                break;
            }

            $this->_instances[$mbox_key] = $ob;
        }

        switch ($mode) {
        case Horde_Registry::VIEW_BASIC:
        case Horde_Registry::VIEW_MINIMAL:
            /* 'checkcache' needs to be set before setIndex(). */
            $this->_instances[$mbox_key]->checkcache = is_null($indices);
            $this->_instances[$mbox_key]->setIndex($indices);
            break;
        }

        return $this->_instances[$mbox_key];
    }

    /**
     * Tasks to perform on shutdown.
     */
    public function shutdown()
    {
        switch ($GLOBALS['registry']->getView()) {
        case Horde_Registry::VIEW_BASIC:
        case Horde_Registry::VIEW_MINIMAL:
            /* Cache mailbox information if viewing in standard (IMP) message
             * mode. Needed to keep navigation consistent when moving through
             * the message list, and to ensure messages aren't marked as
             * missing in search mailboxes (e.g. if search is dependent on
             * unseen flag). */
            foreach ($this->_instances as $key => $val) {
                if ($val->changed) {
                    $GLOBALS['session']->set('imp', self::STORAGE_KEY . $key, $val);
                }
            }
        }
    }

    /**
     * Expires cached entries.
     */
    public function expireAll()
    {
        $GLOBALS['session']->remove('imp', self::STORAGE_KEY);
        $this->_instances = array();
    }

}
