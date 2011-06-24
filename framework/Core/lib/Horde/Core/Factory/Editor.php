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
class Horde_Core_Factory_Editor extends Horde_Core_Factory_Injector
{
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
    public function create(Horde_Injector $injector)
    {
        return $injector->getInstance('Horde_Editor_Ckeditor');
    }
}
