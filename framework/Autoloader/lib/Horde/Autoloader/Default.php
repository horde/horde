<?php
/**
 * Copyright 2008-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2008-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader
 */

require_once 'Horde/Autoloader.php';
require_once 'Horde/Autoloader/ClassPathMapper.php';
require_once 'Horde/Autoloader/ClassPathMapper/Default.php';

/**
 * Default autoloader definition that uses the include path with default
 * class path mappers.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Bob Mckee <bmckee@bywires.com>
 * @category  Horde
 * @copyright 2008-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Autoloader
 */
class Horde_Autoloader_Default extends Horde_Autoloader
{
    /**
     */
    public function __construct()
    {
        $paths = array_map(
            'realpath',
            array_diff(
                array_unique(explode(PATH_SEPARATOR, get_include_path())),
                array('.')
            )
        );

        foreach (array_reverse($paths) as $path) {
            $this->addClassPathMapper(
                new Horde_Autoloader_ClassPathMapper_Default($path)
            );
        }
    }

}

/* Load default autoloader and register. */
$__autoloader = new Horde_Autoloader_Default();
$__autoloader->registerAutoloader();
