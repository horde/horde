<?php
/**
 * A Horde_Injector:: based Horde_Data:: factory.
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
 * A Horde_Injector:: based Horde_Data:: factory.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Horde_Core_Factory_Data
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
     * Return the Horde_Data:: instance.
     *
     * @param string $driver  The driver.
     * @param string $params  Driver parameters.
     *
     * @return Horde_Data_Driver  The instance.
     * @throws Horde_Data_Exception
     */
    public function getData($driver, array $params = array())
    {
        $params['browser'] = $this->_injector->getInstance('Horde_Browser');
        $params['vars'] = Horde_Variables::getDefaultVariables();

        if (strcasecmp($driver, 'csv') === 0) {
            $params['charset'] = $GLOBALS['registry']->getCharset();
        }

        return Horde_Data::factory($driver, $params);
    }

}
