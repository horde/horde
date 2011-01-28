<?php
/**
 * A Horde_Injector:: based Horde_Crypt:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Crypt:: factory.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_Crypt
{
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
