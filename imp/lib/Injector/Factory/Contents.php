<?php
/**
 * A Horde_Injector:: based IMP_Contents:: factory.
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
 * A Horde_Injector:: based IMP_Contents:: factory.
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
class IMP_Injector_Factory_Contents
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
     * Return the Horde_Editor:: instance.
     *
     * @param string $mailbox  The mailbox name.
     * @param integer $uid     The message UID.
     *
     * @return IMP_Contents  The singleton contents instance.
     * @throws IMP_Exception
     */
    public function getOb($mailbox, $uid)
    {
        $uid = $uid . IMP::IDX_SEP . $mailbox;
        if (!isset($this->_instances[$uid])) {
            $this->_instances[$uid] = new IMP_Contents($uid);
        }
        return $this->_instances[$uid];
    }

}
