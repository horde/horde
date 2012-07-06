<?php
/**
 * A Horde_Injector:: based Horde_Core_Ajax_Imple:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Core_Ajax_Imple:: factory.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_Imple extends Horde_Core_Factory_Base
{
    /**
     * Attempts to return a concrete Imple instance.
     *
     * @param string $driver     The driver name.
     * @param array $params      A hash containing any additional
     *                           configuration or parameters a subclass might
     *                           need.
     * @param boolean $noattach  Don't attach on creation.
     *
     * @return Horde_Core_Ajax_Imple  The newly created instance.
     * @throws Horde_Exception
     */
    public function create($driver, array $params = array(),
                           $noattach = false)
    {
        $class = $this->_getDriverName($driver, 'Horde_Core_Ajax_Imple');

        $ob = new $class($params);

        /* Sanity checking: we may directly use the class name from browser
         * input data, we should make absolute sure that this class is defined
         * as an Imple handler. */
        if (!($ob instanceof Horde_Core_Ajax_Imple)) {
            throw new Horde_Exception('Imple driver not found.');
        }

        if (!$noattach) {
            $ob->attach();
        }

        return $ob;
    }

}
