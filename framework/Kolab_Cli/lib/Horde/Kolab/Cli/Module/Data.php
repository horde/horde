<?php
/**
 * The Horde_Kolab_Cli_Module_Data:: class handles Kolab data.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Kolab_Cli
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Cli
 */

/**
 * The Horde_Kolab_Cli_Module_Data:: class handles Kolab data.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Kolab_Cli
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Cli
 */
class Horde_Kolab_Cli_Module_Data
implements Horde_Kolab_Cli_Module
{
    /**
     * Get the usage description for this module.
     *
     * @return string The description.
     */
    public function getUsage()
    {
        return Horde_Kolab_Cli_Translation::t("  data - Handle Kolab data (the default action is \"info\"). PATH refers to the path of the folder that holds the data and the optional TYPE argument indicates which data type should be read. This is usually already defined by the folder setting.

  - info      PATH               : Display general information.
  - stamp     PATH               : Display the folder status information.
  - ids       PATH TYPE          : Display all object ids in the folder PATH of
                                   type TYPE.
  - complete  PATH BACKENDID     : Return the complete message from folder PATH
                                   for the given BACKENDID.
  - create    PATH TYPE yaml PATH: Create an object as defined in the specified
                                   YAML data
  - backendid PATH TYPE OBJECTID : Return the backend ID for the object with ID
                                   OBJECTID.
  - delete    PATH TYPE ID,ID,.. : Delete the given object id's.

");
    }

    /**
     * Get a set of base options that this module adds to the CLI argument
     * parser.
     *
     * @return array The options.
     */
    public function getBaseOptions()
    {
        return array();
    }

    /**
     * Indicate if the module provides an option group.
     *
     * @return boolean True if an option group should be added.
     */
    public function hasOptionGroup()
    {
        return false;
    }

    /**
     * Return the title for the option group representing this module.
     *
     * @return string The group title.
     */
    public function getOptionGroupTitle()
    {
        return '';
    }

    /**
     * Return the description for the option group representing this module.
     *
     * @return string The group description.
     */
    public function getOptionGroupDescription()
    {
        return '';
    }

    /**
     * Return the options for this module.
     *
     * @return array The group options.
     */
    public function getOptionGroupOptions()
    {
        return array();
    }

    /**
     * Handle the options and arguments.
     *
     * @param mixed &$options   An array of options.
     * @param mixed &$arguments An array of arguments.
     * @param array &$world     A list of initialized dependencies.
     *
     * @return NULL
     */
    public function handleArguments(&$options, &$arguments, &$world)
    {
    }

    /**
     * Run the module.
     *
     * @param Horde_Cli $cli       The CLI handler.
     * @param mixed     $options   An array of options.
     * @param mixed     $arguments An array of arguments.
     * @param array     &$world    A list of initialized dependencies.
     *
     * @return NULL
     */
    public function run($cli, $options, $arguments, &$world)
    {
        if (!isset($arguments[1])) {
            $action = 'info';
        } else {
            $action = $arguments[1];
        }
        if (!isset($arguments[2])) {
            $folder_name = 'INBOX';
        } else {
            $folder_name = $arguments[2];
        }
        switch ($action) {
        case 'info':
            break;
        case 'synchronize':
            $world['storage']->getData($folder_name, $arguments[3])->synchronize();
            break;
        case 'stamp':
            $cli->writeln(
                (string)$world['storage']->getData($folder_name)->getStamp()
            );
            break;
        case 'complete':
            $data = $world['storage']->getData($folder_name);
            $complete = $data->fetchComplete($arguments[3]);
            $cli->writeln($complete[1]->toString(array('headers' => $complete[0])));
            break;
        case 'part':
            $data = $world['storage']->getData($folder_name);
            $part = $data->fetchPart($arguments[3], $arguments[4]);
            rewind($part);
            $cli->writeln(quoted_printable_decode(stream_get_contents($part)));
            break;
        case 'fetch':
            $data = $world['storage']->getData($folder_name, $arguments[3]);
            $objects = $data->fetch(explode(',', $arguments[4]));
            foreach ($objects as $uid => $message) {
                $this->_yamlOutput($cli, $uid, $message);
            }
            break;
        case 'ids':
            $data = $world['storage']->getData($folder_name, $arguments[3]);
            foreach ($data->getObjectIds() as $id) {
                $cli->writeln((string)$id);
            }
            break;
        case 'objects':
            $data = $world['storage']->getData($folder_name, $arguments[3]);
            foreach ($data->getObjects() as $id => $object) {
                $this->_yamlOutput($cli, $id, $object);
            }
            break;
        case 'backendobjects':
            $data = $world['storage']->getData($folder_name, $arguments[3]);
            foreach ($data->getObjectsByBackendId() as $id => $object) {
                $this->_yamlOutput($cli, $id, $object);
            }
            break;
        case 'object':
            $data = $world['storage']->getData($folder_name, $arguments[3]);
            $object = $data->getObject($arguments[4]);
            $this->_yamlOutput($cli, $arguments[4], $object);
            break;
        case 'backendobject':
            $data = $world['storage']->getData($folder_name, $arguments[3]);
            $object = $data->getObjectByBackendId($arguments[4]);
            $this->_yamlOutput($cli, $arguments[4], $object);
            break;
        case 'create':
            $data = $world['storage']->getData($folder_name, $arguments[3]);
            switch (strtolower($arguments[4])) {
            case 'yaml':
                if (class_exists('Horde_Yaml')) {
                    $object = Horde_Yaml::loadFile($arguments[5]);
                } else {
                    throw new Horde_Kolab_Cli_Exception(
                        'The Horde_Yaml package is missing!'
                    );
                }
            }
            $data->create($object);
            $cli->writeln($object['uid']);
            break;
        case 'move':
            $data = $world['storage']->getData($folder_name, $arguments[3]);
            $objects = $data->move($arguments[4], $arguments[5]);
            break;
        case 'delete':
            $data = $world['storage']->getData($folder_name, $arguments[3]);
            $objects = $data->delete(explode(',', $arguments[4]));
            break;
        case 'deleteall':
            $world['storage']->getData($folder_name, $arguments[3])->deleteAll();
            break;
        case 'deleteuids':
            $data = $world['storage']->getData($folder_name, $arguments[3]);
            $objects = $data->deleteBackendIds(explode(',', $arguments[4]));
            break;
        case 'backendid':
            $data = $world['storage']->getData($folder_name, $arguments[3]);
            $cli->writeln((string)$data->getBackendId($arguments[4]));
            break;
        default:
            $cli->message(
                sprintf(
                    Horde_Kolab_Cli_Translation::t('Action %s not supported!'),
                    $action
                ),
                'cli.error'
            );
            break;
        }
    }

    private function _messageOutput($cli, $id, $output)
    {
        $cli->writeln('Message UID [' . $id . ']');
        $cli->writeln('================================================================================');
        $cli->writeln();
        $cli->writeln($output);
        $cli->writeln();
        $cli->writeln('================================================================================');
        $cli->writeln();
    }

    private function _yamlOutput($cli, $id, $output)
    {
        $output = $this->_convertDates($output);
        if (class_exists('Horde_Yaml')) {
            $this->_messageOutput($cli, $id, Horde_Yaml::dump($output));
        } else {
            $this->_messageOutput($cli, $id, print_r($output, true));
        }
    }


    private function _convertDates($output)
    {
        $result = array();
        foreach ($output as $name => $element) {
            if (is_array($element)) {
                $result[$name] = $this->_convertDates($element);
            } else if ($element instanceOf DateTime) {
                $result[$name] = $element->format('c');
            } else {
                $result[$name] = $element;
            }
        }
        return $result;
    }
}