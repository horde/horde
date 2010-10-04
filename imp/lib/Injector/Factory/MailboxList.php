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
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class IMP_Injector_Factory_MailboxList
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array(
        'list' => array(),
        'track' => array()
    );

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Return the IMP_Mailbox_List:: instance.
     *
     * @param string $mailbox  The mailbox name.
     *
     * @return IMP_Mailbox_List  The singleton mailbox instance.
     * @throws IMP_Exception
     */
    public function getList($mailbox)
    {
        if (!isset($this->_instances['list'][$mailbox])) {
            $this->_instances['list'][$mailbox] = new IMP_Mailbox_List($mailbox);
        }

        return $this->_instances['list'][$mailbox];
    }

    /**
     * Return the IMP_Mailbox_List_Track:: instance.
     *
     * @param string $mailbox       The mailbox name.
     * @param IMP_Indices $indices  An indices object.
     *
     *
     * @return IMP_Mailbox_List_Track  The singleton mailbox instance.
     * @throws IMP_Exception
     */
    public function getListTrack($mailbox, $indices = null)
    {
        if (!isset($this->_instances['track'][$mailbox])) {
            $ob = null;
            if (isset($_SESSION['imp']['cache']['imp_mailbox'][$mailbox])) {
                try {
                    $ob = @unserialize($_SESSION['imp']['cache']['imp_mailbox'][$mailbox]);
                } catch (Exception $e) {}
            }

            if (!$ob) {
                $ob = new IMP_Mailbox_List_Track($mailbox);
            }

            $this->_instances['track'][$mailbox] = $ob;
        }

        if (!is_null($indices)) {
            $this->_instances['track'][$mailbox]->setIndex($indices);
        }

        return $this->_instances['track'][$mailbox];
    }

    /**
     * Tasks to perform on shutdown.
     */
    public function shutdown()
    {
        /* Cache mailbox information if viewing in standard (IMP) message
         * mode. Needed to keep navigation consistent when moving through the
         * message list, and to ensure messages aren't marked as missing in
         * search mailboxes (e.g. if search is dependent on unseen flag). */
        foreach ($this->_instances['track'] as $key => $val) {
            if ($val->changed) {
                $_SESSION['imp']['cache']['imp_mailbox'][$key] = serialize($val);
            }
        }
    }

}
