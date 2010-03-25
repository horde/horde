<?php
/**
 * A Horde_Injector:: based Horde_Editor:: factory.
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
 * A Horde_Injector:: based Horde_Editor:: factory.
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
class Horde_Core_Factory_Editor
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
     * Return the Horde_Editor:: instance.
     *
     * @param string $driver  The editor driver.
     * @param array $params   Additional parameters to pass to the driver
     *                        (will override Horde defaults).
     *
     * @return Horde_Editor  The singleton editor instance.
     * @throws Horde_Editor_Exception
     */
    public function getEditor($driver, $params = array())
    {
        $browser = $this->_injector->getInstance('Horde_Browser');
        if (!$browser->hasFeature('rte')) {
            return Horde_Editor::factory();
        }

        $params = array_merge(
            Horde::getDriverConfig('editor', $driver),
            $params,
            array(
                'browser' => $browser
            )
        );

        return Horde_Editor::factory($driver, $params);
    }

}
