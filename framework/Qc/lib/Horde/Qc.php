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
        $modules = self::_prepareModules();
        $config->handleModules($modules);
        try {
            self::_validateArguments($config->getArguments());
        } catch (Horde_Qc_Exception $e) {
            $parser->parserError($e->getMessage());
            return;
        }
        $options = $config->getOptions();
        foreach ($modules as $module) {
            $module->handle($options);
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
        $config = new Horde_Qc_Configs();
        $config->addConfigurationType(
            new Horde_Qc_Config_Cli(
                $parser
            )
        );
        return $config;
    }

    static private function _prepareModules()
    {
        $modules = new Horde_Qc_Modules();
        $modules->addModulesFromDirectory(dirname(__FILE__) . '/Qc/Module');
        return $modules;
    }

    static private function _validateArguments(array $arguments)
    {
        if (empty($arguments[0])) {
            throw new Horde_Qc_Exception('Please specify the path of the PEAR package!');
        }

        if (!is_dir($arguments[0])) {
            throw new Horde_Qc_Exception(sprintf('%s specifies no directory!', $arguments[0]));
        }

        if (!file_exists($arguments[0] . '/package.xml')) {
            throw new Horde_Qc_Exception(sprintf('There is no package.xml at %s!', $arguments[0]));
        }
    }
}