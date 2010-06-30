<?php
/**
 * The Horde_Qc:: class is the entry point for the various quality control /
 * packaging actions provided by the package.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Qc
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Qc
 */

/**
 * The Horde_Qc:: class is the entry point for the various quality control /
 * packaging actions provided by the package.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Qc
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Qc
 */
class Horde_Qc
{
    static public function main($parameters = array())
    {
        $modules = new Horde_Qc_Modules();
        if (!isset($parameters['config'])) {
            $parameters['config'] = array();
        }
        $config = new Horde_Qc_Config($parameters['config']);
        $modules->addModulesFromDirectory(dirname(__FILE__) . '/Qc/Module');
        $config->handleModules($modules);
        foreach ($modules as $module) {
            $module->handle($config->getOptions());
        }
    }
}