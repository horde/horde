<?php
/**
 * A Horde_Injector:: based IMP_Mailbox:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  IMP
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 */

/**
 * A Horde_Injector:: based IMP_Mailbox:: factory.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @category Horde
 * @package  IMP
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 */
class IMP_Injector_Factory_Mailbox
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Return the IMP_Mailbox:: instance.
     *
     * @param string $mailbox  The mailbox name.
     * @param string $msgmbox  The mailbox name of the current index.
     * @param integer $uid     The message UID of the current index.
     *
     * @return IMP_Mailbox  The singleton mailbox instance.
     * @throws IMP_Exception
     */
    public function getOb($mailbox, $msgmbox = null, $uid = null)
    {
        $uid = (is_null($msgmbox) || is_null($uid))
            ? null
            : $uid . IMP::IDX_SEP . $msgmbox;

        if (!isset($this->_instances[$mailbox])) {
            $this->_instances[$mailbox] = new IMP_Mailbox($mailbox, $uid);
        } elseif (!is_null($uid)) {
            $this->_instances[$mailbox]->setIndex($uid);
        }

        return $this->_instances[$mailbox];
    }

}
