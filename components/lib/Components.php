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
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
        $dependencies = self::_prepareDependencies($parameters);
        $modular = self::_prepareModular($dependencies, $parameters);
        $parser = $modular->createParser();
        $config = self::_prepareConfig($parser);
        $dependencies->initConfig($config);
        try {
            self::_validateArguments($config);
        } catch (Components_Exception $e) {
            $parser->parserError($e->getMessage());
            return;
        }
        try {
            foreach ($modular->getModules() as $module) {
                $modular->getProvider()->getModule($module)->handle($config);
            }
        } catch (Components_Exception $e) {
            $dependencies->getOutput()->fail($e);
            return;
        }
    }

    /**
     * The main entry point for the hmk script.
     *
     * @param array $parameters A list of named configuration parameters. See main().
     */
    static public function hmk(array $parameters = array())
    {
        $dependencies = self::_prepareDependencies($parameters);
        $modular = self::_prepareModular($dependencies, $parameters);
        $parser = $modular->createParser();
        $config = self::_prepareConfig($parser);
        $dependencies->initConfig($config);

        $cwd = getcwd();
        try {
            self::_requireDirectory($cwd);
            self::_requirePackageXml($cwd);
        } catch (Components_Exception $e) {
            $dependencies->getOutput()->fail(
                sprintf(
                    'You are not in a component directory: %s',
                    $e->getMessage()
                )
            );
            return;
        }
    }

    static private function _prepareModular(
        Components_Dependencies $dependencies, array $parameters = array()
    ) {
        return new Horde_Cli_Modular(
            array(
                'parser' => array(
                    'class' => empty($parameters['parser']['class']) ? 'Horde_Argv_Parser' : $parameters['parser']['class'],
                    'usage' => '%prog [options] PACKAGE_PATH'
                ),
                'modules' => array(
                    'directory' => dirname(__FILE__) . '/Components/Module',
                    'exclude' => 'Base'
                ),
                'provider' => array(
                    'prefix' => 'Components_Module_',
                    'dependencies' => $dependencies
                )
            )
        );
    }

    /**
     * The main entry point for the application.
     *
     * @param array $parameters A list of named configuration parameters.
     *
     * @return Components_Dependencies The dependency handler.
     */
    static private function _prepareDependencies($parameters)
    {
        if (isset($parameters['dependencies'])
            && $parameters['dependencies'] instanceOf Components_Dependencies) {
            return $parameters['dependencies'];
        } else {
            return new Components_Dependencies_Injector();
        }
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

    static private function _validateArguments(Components_Config $config)
    {
        $arguments = $config->getArguments();
        self::_requireDirectory($arguments[0]);
    }

    /**
     * Checks that the provided directory is a directory.
     *
     * @param string $path The path to the directory.
     *
     * @return NULL
     */
    static private function _requireDirectory($path)
    {
        if (empty($path)) {
            throw new Components_Exception('Please specify the path of the PEAR package!');
        }

        if (!is_dir($path)) {
            throw new Components_Exception(sprintf('%s specifies no directory!', $path));
        }
    }

    /**
     * Checks that the provided directory is a directory.
     *
     * @param string $path The path to the directory.
     *
     * @return NULL
     */
    static private function _requirePackageXml($path)
    {
        if (!file_exists($path . '/package.xml')) {
            throw new Components_Exception(sprintf('%s contains no package.xml file!', $path));
        }
    }
}