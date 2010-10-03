<?php
/**
 * The Components:: class is the entry point for the various component actions
 * provided by the package.
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
 * The Components:: class is the entry point for the various component actions
 * provided by the package.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Components
{
    /**
     * The main entry point for the application.
     *
     * @param array $parameters A list of named configuration parameters.
     * <pre>
     * 'cli'        - (array)  CLI configuration parameters.
     *   'parser'   - (array)  Parser configuration parameters.
     *     'class'  - (string) The class name of the parser to use.
     * </pre>
     */
    static public function main(array $parameters = array())
    {
        $parser = self::_prepareParser($parameters);
        $config = self::_prepareConfig($parser);
        if (isset($parameters['dependencies'])
            && $parameters['dependencies'] instanceOf Components_Dependencies) {
            $dependencies = $parameters['dependencies'];
        } else {
            $dependencies = new Components_Dependencies_Injector();
        }
        $dependencies->initConfig($config);
        $modules = self::_prepareModules($dependencies);
        $config->handleModules($modules);
        try {
            self::_validateArguments($config);
        } catch (Components_Exception $e) {
            $parser->parserError($e->getMessage());
            return;
        }
        try {
            foreach ($modules as $module) {
                $module->handle($config);
            }
        } catch (Components_Exception $e) {
            $dependencies->getOutput()->fail($e->getMessage());
            return;
        }
    }

    static private function _prepareParser(array $parameters = array())
    {
        if (empty($parameters['cli']['parser']['class'])) {
            $parser_class = 'Horde_Argv_Parser';
        } else {
            $parser_class = $parameters['cli']['parser']['class'];
        }
        return new $parser_class(
            array(
                'usage' => '%prog ' . _("[options] PACKAGE_PATH")
            )
        );
    }

    static private function _prepareConfig(Horde_Argv_Parser $parser)
    {
        $config = new Components_Configs();
        $config->addConfigurationType(
            new Components_Config_Cli(
                $parser
            )
        );
        return $config;
    }

    static private function _prepareModules(Components_Dependencies $dependencies)
    {
        $modules = new Components_Modules($dependencies);
        $modules->addModulesFromDirectory(dirname(__FILE__) . '/Components/Module');
        return $modules;
    }

    static private function _validateArguments(Components_Config $config)
    {
        $arguments = $config->getArguments();
        if (empty($arguments[0])) {
            throw new Components_Exception('Please specify the path of the PEAR package!');
        }

        if (!is_dir($arguments[0])) {
            throw new Components_Exception(sprintf('%s specifies no directory!', $arguments[0]));
        }

        if (!file_exists($arguments[0] . '/package.xml')) {
            throw new Components_Exception(sprintf('There is no package.xml at %s!', $arguments[0]));
        }
    }
}