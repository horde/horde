<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based IMP_Contents factory.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
