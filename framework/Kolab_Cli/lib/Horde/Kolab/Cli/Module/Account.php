<?php
/**
 * The Horde_Kolab_Cli_Module_Account:: handles operations that require a full
 * account.
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
 * The Horde_Kolab_Cli_Module_Account:: handles operations that require a full
 * account.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Cli_Module_Account
implements Horde_Kolab_Cli_Module
{
    /**
     * Get the usage description for this module.
     *
     * @return string The description.
     */
    public function getUsage()
    {
        return Horde_Kolab_Cli_Translation::t("  account - Handles operations on an account level (like listing *all* available groupware objects)

  - all [TYPE]       : List all groupware objects of the account (optionally
                       limit to TYPE)
  - defects [TYPE]   : List all defects of the account (optionally limit to
                       TYPE)
  - issuelist [TYPE] : A brief list of issues of the account (optionally
                       limit to TYPE)


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
            $action = 'all';
        } else {
            $action = $arguments[1];
        }
        switch ($action) {
        case 'all':
            if (!isset($arguments[2])) {
                $folders = $world['storage']->getList()->getQuery()->listTypes();
            } else {
                $names = $world['storage']->getList()
                    ->getQuery()
                    ->listByType($arguments[2]);
                $folders = array();
                foreach ($names as $name) {
                    $folders[$name] = $arguments[2];
                }
            }
            foreach ($folders as $folder => $type) {
                if ($type == 'mail') {
                    continue;
                }
                $data = $world['storage']->getData($folder, $type);
                foreach ($data->getObjects() as $id => $object) {
                    $this->_yamlOutput($cli, $folder . ': ' . $id, $object);
                }
            }
            break;
        case 'defects':
            if (!isset($arguments[2])) {
                $folders = $world['storage']->getList()->getQuery()->listTypes();
            } else {
                $names = $world['storage']->getList()
                    ->getQuery()
                    ->listByType($arguments[2]);
                $folders = array();
                foreach ($names as $name) {
                    $folders[$name] = $arguments[2];
                }
            }
            foreach ($folders as $folder => $type) {
                if ($type == 'mail') {
                    continue;
                }
                $data = $world['storage']->getData($folder, $type);
                foreach ($data->getErrors() as $id) {
                    $complete = $data->fetchComplete($id);
                    $message = "FAILED PARSING:\n\n" .
                        $complete[1]->toString(array('headers' => $complete[0]));
                    $this->_messageOutput($cli, $folder . ': ' . $id, $message);
                }
                foreach ($data->getDuplicates() as $object => $ids) {
                    foreach ($ids as $id) {
                        $this->_yamlOutput(
                            $cli,
                            "DUPLICATE $object in $folder (backend $id)",
                            $data->fetch(array($id))
                        );
                    }
                }
            }
            break;
        case 'issuelist':
            if (!isset($arguments[2])) {
                $folders = $world['storage']->getList()->getQuery()->listTypes();
            } else {
                $names = $world['storage']->getList()
                    ->getQuery()
                    ->listByType($arguments[2]);
                $folders = array();
                foreach ($names as $name) {
                    $folders[$name] = $arguments[2];
                }
            }
            foreach ($folders as $folder => $type) {
                if ($type == 'mail') {
                    continue;
                }
                $data = $world['storage']->getData($folder, $type);
                $issues = '';
                $errors = $data->getErrors();
                if (!empty($errors)) {
                    $issues = "FAILED parsing the messages with the following UIDs:\n\n";
                    foreach ($errors as $id) {
                        $issues .= " - $id\n";
                    }
                    $issues .= "\n";
                }
                $duplicates = $data->getDuplicates();
                if (!empty($duplicates)) {
                    foreach ($duplicates as $object => $ids) {
                        $issues .= "DUPLICATE object ID \"$object\" represented by messages with the following UIDs:\n\n";
                        foreach ($ids as $id) {
                            $issues .= " - $id\n";
                        }
                        $issues .= "\n";
                    }
                }
                if (!empty($issues)) {
                    $cli->writeln('Error report for folder "' . $folder . '"');
                    $cli->writeln('================================================================================');
                    $cli->writeln();
                    $cli->writeln($issues);
                    $cli->writeln('================================================================================');
                    $cli->writeln();
                }
            }
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