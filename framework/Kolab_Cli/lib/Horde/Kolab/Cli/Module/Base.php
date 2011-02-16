<?php
/**
 * The Horde_Kolab_Cli_Module_Base:: module provides the base options of the
 * Kolab CLI.
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
 * The Horde_Kolab_Cli_Module_Base:: module provides the base options of the
 * Kolab CLI.
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
class Horde_Kolab_Cli_Module_Base
implements Horde_Kolab_Cli_Module
{
    /**
     * Get the usage description for this module.
     *
     * @return string The description.
     */
    public function getUsage()
    {
        return '';
    }

    /**
     * Get a set of base options that this module adds to the CLI argument
     * parser.
     *
     * @return array The options.
     */
    public function getBaseOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-d',
                '--driver',
                array(
                    'action' => 'store',
                    'choices' => array('horde', 'horde-php', 'php', 'pear', 'roundcube', 'mock'),
                    'help'   => Horde_Kolab_Cli_Translation::t(
"The Kolab backend driver that should be used.
Choices are:

 - horde     [IMAP]: The Horde_Imap_Client driver as pure PHP implementation.
 - horde-php [IMAP]: The Horde_Imap_Client driver based on c-client in PHP
 - php       [IMAP]: The PHP imap_* functions which are based on c-client
 - pear      [IMAP]: The PEAR-Net_IMAP driver
 - roundcube [IMAP]: The roundcube IMAP driver
 - mock      [Mem.]: A dummy driver that uses memory."
                    ),
                    'default' => 'horde'
                )
            ),
            new Horde_Argv_Option(
                '-u',
                '--username',
                array(
                    'action' => 'store',
                    'help'   => Horde_Kolab_Cli_Translation::t('The user accessing the backend.')
                )
            ),
            new Horde_Argv_Option(
                '-p',
                '--password',
                array(
                    'action' => 'store',
                    'help'   => Horde_Kolab_Cli_Translation::t('The password of the user accessing the backend.')
                )
            ),
            new Horde_Argv_Option(
                '-H',
                '--host',
                array(
                    'action' => 'store',
                    'help'   => Horde_Kolab_Cli_Translation::t('The host that holds the data.'),
                    'default' => 'localhost'
                )
            ),
            new Horde_Argv_Option(
                '-P',
                '--port',
                array(
                    'action' => 'store',
                    'help'   => Horde_Kolab_Cli_Translation::t('The port that should be used to connect to the host.')
                )
            ),
            new Horde_Argv_Option(
                '-S',
                '--secure',
                array(
                    'action' => 'store',
                    'help'   => Horde_Kolab_Cli_Translation::t('Sets the connection type. Use either "tls" or "ssl" here.')
                )
            ),
            new Horde_Argv_Option(
                '-t',
                '--timed',
                array(
                    'action' => 'store_true',
                    'help'   => Horde_Kolab_Cli_Translation::t('Produce time measurements to indicate how long the processing takes. You *must* activate logging for this as well.')
                )
            ),
            new Horde_Argv_Option(
                '-m',
                '--memory',
                array(
                    'action' => 'store_true',
                    'help'   => Horde_Kolab_Cli_Translation::t('Report memory consumption statistics. You *must* activate logging for this as well.')
                )
            ),
            new Horde_Argv_Option(
                '-n',
                '--nocache',
                array(
                    'action' => 'store_true',
                    'help'   => Horde_Kolab_Cli_Translation::t('Deactivate caching of the IMAP data.')
                )
            ),
            new Horde_Argv_Option(
                '-l',
                '--log',
                array(
                    'action' => 'store',
                    'help'   => Horde_Kolab_Cli_Translation::t('Write a log file in the provided LOG location. Use "STDOUT" here to direct the log output to the screen.')
                )
            ),
            new Horde_Argv_Option(
                '-D',
                '--debug',
                array(
                    'action' => 'store',
                    'help'   => Horde_Kolab_Cli_Translation::t('Activates the IMAP debug log. This will log the full IMAP communication - CAUTION: the "php" driver is the only driver variant that does not support this feature. For most drivers you should use "STDOUT" which will direct the debug log to your screen. For the horde, the horde-php, and the roundcube drivers you may also set this to a filename and the output will be directed there.'),
                )
            ),
        );
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
        if (isset($options['driver'])
            && in_array($options['driver'], array('roundcube', 'php', 'pear'))) {
            if (defined('E_DEPRECATED')) {
                error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED & ~E_NOTICE);
            } else {
                error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
            }
        }
        if (isset($options['log'])) {
            if (class_exists('Horde_Log_Logger')) {
                $options['log'] = new Horde_Log_Logger(
                    new Horde_Log_Handler_Stream(
                        ($options['log'] == 'STDOUT') ? STDOUT : $options['log']
                    )
                );
            } else {
                file_put_contents($options['log'], 'The Horde_Log_Logger class is not available!');
            }
        }
        $world['storage'] = $this->_getStorage($options);
        $world['format'] = $this->_getFormat($options);
    }


    /**
     * Return the driver for the Kolab storage backend.
     *
     * @param mixed     $options   An array of options.
     *
     * @return Horde_Kolab_Storage The storage handler.
     */
    private function _getStorage($options)
    {
        if (empty($options['driver'])) {
            return;
        }
        $factory = new Horde_Kolab_Storage_Factory();
        $params = array(
            'driver' => $options['driver'],
            'params' => $options,
            'logger' => isset($options['log']) ? $options['log'] : null,
            'timelog' => isset($options['log']) && isset($options['timed']) ? $options['log'] : null,
        );
        if (empty($options['nocache'])) {
            $params['cache'] = array('prefix' => 'kolab_cache_', 'dir' => '/tmp/kolab', 'lifetime' => 0);
        }
        return $factory->createFromParams($params);
    }

    /**
     * Return the factory for the Kolab format parsers.
     *
     * @param mixed     $options   An array of options.
     *
     * @return Horde_Kolab_Format_Factory The format parser factory.
     */
    private function _getFormat($options)
    {
        return new Horde_Kolab_Format_Factory(
            array(
                'timelog' => isset($options['log']) && isset($options['timed']) ? $options['log'] : null,
                'memlog' => isset($options['log']) && isset($options['memory']) ? $options['log'] : null,
            )
        );
    }
}