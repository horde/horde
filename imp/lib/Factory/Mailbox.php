<?php
/**
 * A Horde_Injector:: based IMP_Mailbox:: factory.
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
 * A Horde_Injector:: based IMP_Mailbox:: factory.
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
class IMP_Factory_Mailbox
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

        if (empty($this->_instances[$mbox])) {
            $this->_instances[$mbox] = new IMP_Mailbox($mbox);
        }

        return $this->_instances[$mbox];
    }

}
