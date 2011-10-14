<?php
/**
 * Creates the Horde_Push content object.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Push_Cli
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/gpl GPL-2.0
 * @link     http://www.horde.org/components/Horde_Push_Cli
 */

/**
 * Creates the Horde_Push content object.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL-2.0). If you did
 * not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @package  Push_Cli
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/gpl GPL-2.0
 * @link     http://www.horde.org/components/Horde_Push_Cli
 */
class Horde_Push_Cli_Factory_Push
{
    /**
     * Create the Horde_Push content element.
     *
     * @param array $arguments The command line arguments.
     * @param array $options   Command line options.
     * @param array $conf      The configuration.
     *
     * @return array The elements to be pushed.
     */
    public function create($arguments, $options, $conf)
    {
        if (empty($arguments)) {
            return array(new Horde_Push());
        }
        $result = array();
        foreach ($arguments as $argument) {
            $result[] = $this->_parseArgument($argument, $conf);
        }
        return $result;
    }

    /**
     * Parse an argument into a Horde_Push element.
     *
     * @param string $argument A single command line argument.
     * @param array  $conf     The configuration.
     *
     * @return Horde_Push The element to be pushed.
     */
    private function _parseArgument($argument, $conf)
    {
        $elements = explode('://', $argument);
        if (isset($elements[0])) {
            $argument = substr($argument, strlen($elements[0]) + 3);
            if (empty($argument)) {
                throw new Horde_Push_Cli_Exception('Missing file path!');
            }
            switch ($elements[0]) {
            case 'kolab':
                return $this->_parseKolab($argument, $conf);
            case 'php':
                return $this->_parsePhp($argument, $conf);
            case 'yaml':
                return $this->_parseYaml($argument, $conf);
            }
        }
        throw new Horde_Push_Cli_Exception(
            sprintf('Invalid command line arguments: %s!', $argument)
        );
    }

    /**
     * Parse the content of a Kolab object into a Horde_Push element.
     *
     * @param string $argument A single command line argument (without the scheme argument).
     * @param array $conf      The configuration.
     *
     * @return Horde_Push The element to be pushed.
     */
    private function _parseKolab($argument, $conf)
    {
        if (!file_exists($argument)) {
            throw new Horde_Push_Cli_Exception(
                sprintf('Invalid file path: "%s"!', $argument)
            );
        }
        $elements = explode('/', $argument);
        $id = array_pop($elements);
        $path = join('/', $elements);
        $factory = new Horde_Kolab_Storage_Factory($conf['kolab']);
        return $this->_createFromData(
            $factory->create()->getData($path, 'note')->getObject($id)
        );
    }

    /**
     * Parse the content of a PHP file into a Horde_Push element.
     *
     * @param string $argument A single command line argument (without the scheme argument).
     * @param array $conf      The configuration.
     *
     * @return Horde_Push The element to be pushed.
     */
    private function _parsePhp($argument, $conf)
    {
        if (!file_exists($argument)) {
            throw new Horde_Push_Cli_Exception(
                sprintf('Invalid file path: "%s"!', $argument)
            );
        }

        global $push;
        include $argument;
        return $this->_createFromData($push);
    }

    /**
     * Parse the content of a YAML file into a Horde_Push element.
     *
     * @param string $argument A single command line argument (without the scheme argument).
     * @param array $conf      The configuration.
     *
     * @return Horde_Push The element to be pushed.
     */
    private function _parseYaml($argument, $conf)
    {
        if (!class_exists('Horde_Yaml')) {
            throw new Horde_Push_Cli_Exception(
                'The Horde_Yaml package is missing!'
            );
        }
        if (!file_exists($argument)) {
            throw new Horde_Push_Cli_Exception(
                sprintf('Invalid file path: "%s"!', $argument)
            );
        }
        return $this->_createFromData(Horde_Yaml::loadFile($argument));
    }

    /**
     * Generate a Horde_Push element based on the provided data.
     *
     * @param array $data The data to be pushed.
     *
     * @return Horde_Push The element to be pushed.
     */
    private function _createFromData($data)
    {
        if (!isset($data['summary'])) {
            throw new Horde_Push_Cli_Exception(
                'Data is lacking a summary element!'
            );
        }
        $push = new Horde_Push();
        $push->setSummary($data['summary']);
        if (isset($data['body'])) {
            $push->addContent($data['body']);
        }
        return $push;
    }

}