<?php
/**
 * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2008-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://www.horde.org/libraries/Horde_Autoloader
 * @package   Autoloader
 */

/**
 * A basic autoloader implementation that allows loading classes according to
 * PSR-0 in all directories defined in the include path.
 *
 * @author    Bob Mckee <bmckee@bywires.com>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2008-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://www.horde.org/libraries/Horde_Autoloader
 * @package   Autoloader
 */
class Horde_Autoloader_IncludePath extends Horde_Autoloader_Base
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        foreach (array_reverse(explode(PATH_SEPARATOR, get_include_path())) as $path) {
            if (($path != '.') && ($path = realpath($path))) {
                $this->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Default($path));
            }
        }
    }

}
