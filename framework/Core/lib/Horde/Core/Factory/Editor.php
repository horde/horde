<?php
/**
 * A Horde_Injector:: based Horde_Editor:: factory.
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
 * A Horde_Injector:: based Horde_Editor:: factory.
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
class Horde_Core_Factory_Editor extends Horde_Core_Factory_Injector
{
    /**
     * Return the Horde_Editor:: instance.
     *
     * @param string $driver  The editor driver.
     *
     * @return Horde_Editor  The singleton editor instance.
     * @throws Horde_Editor_Exception
     */
    public function create(Horde_Injector $injector)
    {
        return $injector->getInstance('Horde_Core_Editor_Ckeditor');
    }
}
