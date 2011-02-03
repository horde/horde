<?php
/**
 * A Horde_Injector:: based IMP_Mailbox_List:: factory.
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
 * A Horde_Injector:: based IMP_Mailbox_List:: factory.
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
class IMP_Factory_MailboxList extends Horde_Core_Factory_Base
{
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
        $mode = IMP::getViewMode();

        if (!isset($this->_instances[$mailbox])) {
            switch ($mode) {
            case 'dimp':
            case 'mobile':
                $ob = new IMP_Mailbox_List($mailbox);
                break;

            case 'imp':
            case 'mimp':
                try {
                    $ob = $GLOBALS['session']->get('imp', 'imp_mailbox/' . $mailbox);
                } catch (Exception $e) {
                    $ob = null;
                }

                if (is_null($ob)) {
                    $ob = new IMP_Mailbox_List_Track($mailbox);
                }
                break;
            }

            $this->_instances[$mailbox] = $ob;
        }

        switch ($mode) {
        case 'imp':
        case 'mimp':
            $this->_instances[$mailbox]->setIndex($indices);
            $this->_instance[$mailbox]->checkcache = is_null($indices);
            break;
        }

        return $this->_instances[$mailbox];
    }

    /**
     * Tasks to perform on shutdown.
     */
    public function shutdown()
    {
        switch (IMP::getViewMode()) {
        case 'imp':
        case 'mimp':
            /* Cache mailbox information if viewing in standard (IMP) message
             * mode. Needed to keep navigation consistent when moving through
             * the message list, and to ensure messages aren't marked as
             * missing in search mailboxes (e.g. if search is dependent on
             * unseen flag). */
            foreach ($this->_instances as $key => $val) {
                if ($val->changed) {
                    $GLOBALS['session']->set('imp', 'imp_mailbox/' . $key, $val);
                }
            }
        }
    }

}
