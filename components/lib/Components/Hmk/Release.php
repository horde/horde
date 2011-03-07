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
        return "

DO";
    }

    public function handle(Components_Config $config)
    {
        $options = $config->getOptions();
        if (!empty($options['release'])) {
            $this->requirePackageXml($config->getPackageDirectory());
            $this->_dependencies->getRunnerRelease()->run();
        }
    }
}
