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

    const ERROR_NO_COMPONENT = 'You are neither in a component directory nor specified it as the first argument!';

    const ERROR_NO_ACTION = 'You did not specify an action!';

    const ERROR_NO_ACTION_OR_COMPONENT = '"%s" specifies neither an action nor a component directory!';

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
            self::_identifyComponent(
                $config, self::_getActionArguments($modular)
            );
        } catch (Components_Exception $e) {
            $parser->parserError($e->getMessage());
            return;
        }

        try {
            $ran = false;
            foreach ($modular->getModules() as $module) {
                $ran |= $modular->getProvider()->getModule($module)->handle($config);
            }
        } catch (Components_Exception $e) {
            $dependencies->getOutput()->fail($e);
            return;
        }

        if (!$ran) {
            $parser->parserError(self::ERROR_NO_ACTION);
        }
    }

    static private function _prepareModular(
        Components_Dependencies $dependencies, array $parameters = array()
    ) {
        return new Horde_Cli_Modular(
            array(
                'parser' => array(
                    'class' => empty($parameters['parser']['class']) ? 'Horde_Argv_Parser' : $parameters['parser']['class'],
                    'usage' => '[options] [COMPONENT_PATH] [ACTION] [ARGUMENTS]

COMPONENT_PATH

Specifies the path to the component you want to work with. This argument is optional in case your current working directory is the base directory of a component and contains a package.xml file.

ACTION

Selects the action to perform. Most actions can also be selected with an option switch.

This is a list of available actions (use "help ACTION" to get additional information on the specified ACTION):

'
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

    /**
     * Provide a list of available action arguments.
     *
     * @param Components_Config $config The active configuration.
     *
     * @return NULL
     */
    static private function _getActionArguments(Horde_Cli_Modular $modular)
    {
        $actions = array();
        foreach ($modular->getModules() as $module) {
            $actions = array_merge(
                $actions,
                $modular->getProvider()->getModule($module)->getActions()
            );
        }
        return $actions;
    }

    /**
     * Identify the selected component based on the command arguments.
     *
     * @param Components_Config $config  The active configuration.
     * @param array             $actions The list of available actions.
     *
     * @return NULL
     */
    static private function _identifyComponent(
        Components_Config $config,
        $actions
    ) {
        $arguments = $config->getArguments();

        if (isset($arguments[0]) && self::_isPackageXml($arguments[0])) {
            $config->setComponentDirectory(dirname($arguments[0]), true);
            return;
        }

        if (isset($arguments[0]) && !in_array($arguments[0], $actions)) {
            self::_requireDirectory($arguments[0]);
            $config->setComponentDirectory($arguments[0], true);
            return;
        }

        $cwd = getcwd();
        try {
            self::_requireDirectory($cwd);
            self::_requirePackageXml($cwd);
        } catch (Components_Exception $e) {
            throw new Components_Exception(self::ERROR_NO_COMPONENT);
        }
        $config->setComponentDirectory($cwd);
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
        if (empty($path) || !is_dir($path)) {
            throw new Components_Exception(
                sprintf(self::ERROR_NO_ACTION_OR_COMPONENT, $path)
            );
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

    /**
     * Checks if the file name is a package.xml file.
     *
     * @param string $path The path.
     *
     * @return boolean True if the provided file name points to a package.xml
     *                 file.
     */
    static private function _isPackageXml($path)
    {
        if (basename($path) == 'package.xml' && file_exists($path)) {
            return true;
        }
        return false;
    }
}