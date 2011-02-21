<?php
/**
 * The Horde_Kolab_Cli_Module_Format:: handles the Kolab format.
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
 * The Horde_Kolab_Cli_Module_Format:: handles the Kolab format.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Cli_Module_Format
implements Horde_Kolab_Cli_Module
{
    /**
     * Get the usage description for this module.
     *
     * @return string The description.
     */
    public function getUsage()
    {
        return Horde_Kolab_Cli_Translation::t("  format - Handle the Kolab format (the default action is \"read\")

  - read TYPE [FILE|FOLDER UID PART]: Read a Kolab format file of the specified
                                      type. Specify either a direct file name
                                      or a combination of an IMAP folder, a UID
                                      within that folder and the specific part
                                      that should be parsed.


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
            $action = 'read';
        } else {
            $action = $arguments[1];
        }
        switch ($action) {
        case 'read':
            $parser = $world['format']->create('Xml', $arguments[2]);
            if (empty($arguments[4])) {
                if (file_exists($arguments[3])) {
                    $contents = file_get_contents($arguments[3]);
                    $data = $parser->load($contents);
                    $id = $arguments[3];
                } else {
                    $cli->message(
                        sprintf(
                            Horde_Kolab_Cli_Translation::t('%s is no local file!'),
                            $arguments[3]
                        ),
                        'cli.error'
                    );
                }
            } else {
                $ks_data = $world['storage']->getData($arguments[3]);
                $part = $ks_data->fetchPart($arguments[4], $arguments[5]);
                rewind($part);
                $xml = quoted_printable_decode(stream_get_contents($part));
                $data = $parser->load($xml);
                $id = $arguments[3] . ':' . $arguments[4] . '[' . $arguments[5] . ']';
            }
            if (class_exists('Horde_Yaml')) {
                $this->_formatOutput($cli, $id, Horde_Yaml::dump($data));
            } else {
                $this->_formatOutput($cli, $id, print_r($data, true));
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

    private function _formatOutput($cli, $id, $output)
    {
        $cli->writeln('Kolab XML [' . $id . ']');
        $cli->writeln('================================================================================');
        $cli->writeln();
        $cli->writeln($output);
        $cli->writeln();
        $cli->writeln('================================================================================');
        $cli->writeln();
    }

}