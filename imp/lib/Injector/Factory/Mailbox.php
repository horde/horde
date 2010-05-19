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
     * @param string $mailbox       The mailbox name.
     * @param IMP_Indices $indices  An indices object.
     *
     * @return IMP_Mailbox  The singleton mailbox instance.
     * @throws IMP_Exception
     */
    public function getOb($mailbox, $indices = null)
    {
        if (!isset($this->_instances[$mailbox])) {
            $this->_instances[$mailbox] = new IMP_Mailbox($mailbox, $indices);
        } elseif (!is_null($indices)) {
            $this->_instances[$mailbox]->setIndex($indices);
        }

        return $this->_instances[$mailbox];
    }

}
