<?php
/**
 * A Horde_Injector:: based IMP_Contents:: factory.
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
 * A Horde_Injector:: based IMP_Contents:: factory.
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
class IMP_Factory_Contents extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the IMP_Contents:: instance.
     *
     * @param IMP_Indices $indices  An indices object.
     *
     * @return IMP_Contents  The singleton contents instance.
     * @throws IMP_Exception
     */
    public function create($indices)
    {
        $key = strval($indices);

        if (!isset($this->_instances[$key])) {
            $this->_instances[$key] = new IMP_Contents($indices);
        }

        return $this->_instances[$key];
    }

}
