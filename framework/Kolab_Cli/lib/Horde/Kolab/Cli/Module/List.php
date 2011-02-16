<?php
/**
 * The Horde_Kolab_Cli_Module_List:: handles folder lists.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Kolab_Cli
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Cli
 */

/**
 * The Horde_Kolab_Cli_Module_List:: handles folder lists.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Kolab_Cli
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Cli
 */
class Horde_Kolab_Cli_Module_List
implements Horde_Kolab_Cli_Module
{
    /**
     * Get the usage description for this module.
     *
     * @return string The description.
     */
    public function getUsage()
    {
        return Horde_Kolab_Cli_Translation::t("  list - Handle folder lists (the default action is \"folders\")

  - folders          : List the folders in the backend
  - types            : Display all folders that have a folder type.
  - type TYPE        : Display the folders of type TYPE.
  - owners           : List all folders and their owners.
  - defaults         : List the default folders for all users.
  - aclsupport       : Display if the server supports ACL.
  - namespace        : Display the server namespace information.
  - sync             : Synchronize the cache.


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
            $action = 'folders';
        } else {
            $action = $arguments[1];
        }
        switch ($action) {
        case 'folders':
            $folders = $world['storage']->getList()->listFolders();
            foreach ($folders as $folder) {
                $cli->writeln($folder);
            }
            break;
        case 'types':
            $types = $world['storage']->getList()
                ->getQuery()
                ->listTypes();
            if (!empty($types)) {
                $pad = max(array_map('strlen', array_keys($types))) + 2;
                foreach ($types as $folder => $type) {
                    $cli->writeln(Horde_String::pad($folder . ':', $pad) . $type);
                }
            }
            break;
        case 'type':
            if (!isset($arguments[2])) {
                throw new Horde_Kolab_Cli_Exception('You must provide a TYPE argument!');
            }
            $type = $arguments[2];
            $folders = $world['storage']->getList()
                ->getQuery()
                ->listByType($type);
            foreach ($folders as $folder) {
                $cli->writeln($folder);
            }
            break;
        case 'owners':
            $owners = $world['storage']->getList()
                ->getQuery()
                ->listOwners();
            if (!empty($owners)) {
                $pad = max(array_map('strlen', array_keys($owners))) + 2;
                foreach ($owners as $folder => $owner) {
                    $cli->writeln(Horde_String::pad($folder . ':', $pad) . $owner);
                }
            }
            break;
        case 'defaults':
            $defaults = $world['storage']->getList()
                ->getQuery()
                ->listDefaults();
            if (!empty($defaults)) {
                foreach ($defaults as $owner => $folders) {
                    $cli->writeln('User "' . $owner . '":');
                    $cli->writeln();
                    foreach ($folders as $type => $folder) {
                        $cli->writeln('  ' . Horde_String::pad($type . ':', 14) . $folder);
                    }
                    $cli->writeln();
                }
            }
            break;
        case 'aclsupport':
            if ($world['storage']->getList()
                ->getQuery(Horde_Kolab_Storage_List::QUERY_ACL)
                ->hasAclSupport()) {
                echo "The remote server supports ACL.\n";
            } else {
                echo "The remote server does not support ACL.\n";
            }
            break;
        case 'namespaces':
            $cli->writeln((string) $world['storage']->getList()->getNamespace());
            break;
        case 'sync':
            $folders = $world['storage']->getList()->synchronize();
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
}