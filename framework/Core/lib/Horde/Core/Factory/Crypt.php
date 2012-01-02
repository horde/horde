<?php
/**
 * A Horde_Injector:: based Horde_Crypt:: factory.
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
 * A Horde_Injector:: based Horde_Crypt:: factory.
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
class Horde_Core_Factory_Crypt extends Horde_Core_Factory_Base
{
    /**
     * Return the Horde_Crypt:: instance.
     *
     * @param string $driver  The driver name.
     * @param array $params   Any parameters needed by the driver.
     *
     * @return Horde_Crypt  The instance.
     * @throws Horde_Exception
     */
    public function create($driver, $params = array())
    {
        global $registry;

        $params = array_merge(array(
            'email_charset' => $registry->getEmailCharset(),
            'temp' => Horde::getTempDir()
        ), $params);

        return Horde_Crypt::factory($driver, $params);
    }

}
