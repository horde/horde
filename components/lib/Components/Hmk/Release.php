<?php
/**
 * Components_Hmk_Release:: generates a release.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Hmk_Release:: generates a release.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Hmk_Release
extends Components_Hmk_Base
{
    public function getUsage()
    {
        return '

Action "release"

  Release this module on pear.horde.org.

Action "testrelease"

  Test the module release on peartest.horde.org.
';
    }

    public function handle(Components_Config $config)
    {
        $arguments = $config->getArguments();
        if (isset($arguments[1]) && $arguments[1] == 'release') {
            $config->setOption('releaseserver', '');
            $config->setOption('releasedir', '');
            $this->_dependencies->getRunnerRelease()->run();
        }
    }
}
