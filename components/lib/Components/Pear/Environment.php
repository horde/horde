<?php
/**
 * Components_Pear_Environment:: handles a specific PEAR environment.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Pear_Environment:: handles a specific PEAR environment.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Pear_Environment
{
    /**
     * The output handler.
     *
     * @param Component_Output
     */
    private $_output;

    /**
     * The factory for PEAR class instances.
     *
     * @param Components_Pear_Factory
     */
    private $_factory;

    /**
     * The base directory for the PEAR install location.
     *
     * @param string
     */
    private $_base_directory;

    /**
     * The path to the configuration file.
     *
     * @param string
     */
    private $_config_file;

    /**
     * The directory that contains channel definitions.
     *
     * @param string
     */
    private $_channel_directory;

    /**
     * The directory that contains package sources.
     *
     * @param string
     */
    private $_source_directory;

    /**
     * Constructor.
     *
     * @param Component_Output $output The output handler.
     */
    public function __construct(Components_Output $output) {
        $this->_output = $output;
    }

    /**
     * Define the factory that creates our PEAR dependencies.
     *
     * @param Components_Pear_Factory
     *
     * @return NULL
     */
    public function setFactory(Components_Pear_Factory $factory)
    {
        $this->_factory = $factory;
    }

    /**
     * Set the path to the install location.
     *
     * @param string $base_directory The base directory for the PEAR install location.
     * @param string $config_file    The name of the configuration file.
     *
     * @return NULL
     */
    public function setLocation($base_directory, $config_file)
    {
        $this->_base_directory = $base_directory;
        if (!file_exists($this->_base_directory)) {
            throw new Components_Exception(
                sprintf(
                    'The path to the install location (%s) does not exist! Create it first.',
                    $this->_base_directory
                )
            );
        }
        $this->_config_file = $config_file;
    }

    /**
     * Set the path to the channel directory.
     *
     * @param array &$options The application options
     *
     * @return NULL
     */
    public function setChannelDirectory(&$options)
    {
        if (empty($options['channelxmlpath'])) {
            $options['channelxmlpath'] = $options['destination']
                . '/distribution/channels';
            if (!file_exists($options['channelxmlpath'])) {
                if (!empty($options['build_distribution'])) {
                    mkdir($options['channelxmlpath'], 0777, true);
                } else {
                    unset($options['channelxmlpath']);
                }
            }
        }
        if (empty($options['channelxmlpath']) &&
            !empty($this->_source_directory)) {
            $options['channelxmlpath'] = $this->_source_directory;
        }
        if (!empty($options['channelxmlpath'])) {
            if (!file_exists($options['channelxmlpath'])) {
                throw new Components_Exception(
                    sprintf(
                        'The path to the channel directory (%s) does not exist!',
                        $options['channelxmlpath']
                    )
                );
            }
            $this->_channel_directory = $options['channelxmlpath'];
        }
    }

    /**
     * Set the path to the source directory.
     *
     * @param array &$options The application options
     *
     * @return NULL
     */
    public function setSourceDirectory(&$options)
    {
        if (empty($options['sourcepath'])) {
            $options['sourcepath'] = $options['destination']
                . '/distribution/source';
            if (!file_exists($options['sourcepath'])) {
                if (!empty($options['build_distribution'])) {
                    mkdir($options['sourcepath'], 0777, true);
                } else {
                    unset($options['sourcepath']);
                }
            }
        }
        if (!empty($options['sourcepath'])) {
            if (!file_exists($options['sourcepath'])) {
                throw new Components_Exception(
                    sprintf(
                        'The path to the source directory (%s) does not exist!',
                        $options['sourcepath']
                    )
                );
            }
            $this->_source_directory = $options['sourcepath'];
        }
    }

    /**
     * Set the paths to the resource directories.
     *
     * @param array &$options The application options
     *
     * @return NULL
     */
    public function setResourceDirectories(&$options)
    {
        $this->setSourceDirectory($options);
        $this->setChannelDirectory($options);
    }

    public function createPearConfig()
    {
        if (empty($this->_config_file)) {
            throw new Components_Exception(
                'Set the path to the PEAR environment first!'
            );
        }
        if (file_exists($this->_config_file)) {
            throw new Components_Exception(
                sprintf(
                    'PEAR configuration file %s already exists!',
                    $this->_config_file
                )
            );
        }
        ob_start();
        $config = Components_Exception_Pear::catchError(
            PEAR_Config::singleton($this->_config_file, '#no#system#config#', false)
        );
        $root = dirname($this->_config_file);
        $config->noRegistry();
        $config->set('php_dir', "$root/pear/php", 'user');
        $config->set('data_dir', "$root/pear/data");
        $config->set('www_dir', "$root/pear/www");
        $config->set('cfg_dir', "$root/pear/cfg");
        $config->set('ext_dir', "$root/pear/ext");
        $config->set('doc_dir', "$root/pear/docs");
        $config->set('test_dir', "$root/pear/tests");
        $config->set('cache_dir', "$root/pear/cache");
        $config->set('download_dir', "$root/pear/download");
        $config->set('temp_dir', "$root/pear/temp");
        $config->set('bin_dir', "$root/pear");
        $config->writeConfigFile();
        $config->_noRegistry = false;
        $config->_registry['default'] = new PEAR_Registry("$root/pear/php");
        $config->_noRegistry = true;
        if (!file_exists("$root/pear")) {
            mkdir("$root/pear/php", 0777, true);
        }
        $this->_output->pear(ob_get_clean());
        $this->_output->ok(
            sprintf(
                'Successfully created PEAR configuration %s',
                $this->_config_file
            )
        );
    }

    public function getPearConfig()
    {
        if (!isset($GLOBALS['_PEAR_Config_instance'])) {
            $GLOBALS['_PEAR_Config_instance'] = false;
        }
        if (empty($this->_config_file)) {
            $config = PEAR_Config::singleton();
            if (!$config->validConfiguration()) {
                throw new Components_Exception(
                    'Set the path to the PEAR environment first!'
                );
            }
            return $config;
        }
        if (!file_exists($this->_config_file)) {
            $this->createPearConfig();
        }
        return Components_Exception_Pear::catchError(
            PEAR_Config::singleton($this->_config_file)
        );
    }

    /**
     * Test if a channel exists within the install location.
     *
     * @param string $channel The channel name.
     *
     * @return boolean True if the channel exists.
     */
    public function channelExists($channel)
    {
        $registered = $this->getPearConfig()->getRegistry()->getChannels();
        foreach ($registered as $c) {
            if ($channel == $c->getName()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Ensure the specified channel exists within the install location.
     *
     * @param string $channel The channel name.
     * @param array  $options Install options.
     * @param string $reason  Optional reason for adding the channel.
     *
     * @return NULL
     */
    public function provideChannel($channel, $options = array(), $reason = '')
    {
        if (!$this->channelExists($channel)) {
            $this->addChannel($channel, $options, $reason);
        }
    }

    /**
     * Provide the PEAR specific installer.
     *
     * @return PEAR_Command_Install
     */
    private function getInstallationHandler()
    {
        $installer = new PEAR_Command_Install(
            new PEAR_Frontend_CLI(),
            $this->getPearConfig()
        );
        return $installer;
    }

    /**
     * Add a package based on a source directory.
     *
     * @param string $package The path to the package.xml in the source directory.
     * @param string $reason  Optional reason for adding the package.
     *
     * @return NULL
     */
    public function linkPackageFromSource($package, $reason = '')
    {
        $this->_output->ok(
            sprintf(
                'About to symlink package %s%s',
                $package,
                $reason
            )
        );

        ob_start();
        $warnings = array();
        $pkg = $this->_factory->createPackageForEnvironment($package, $this);

        $destDir = array(
            'horde' => $this->getPearConfig()->get('horde_dir', 'user', 'pear.horde.org'),
            'php' => $this->getPearConfig()->get('php_dir'),
            'data' => $this->getPearConfig()->get('data_dir') . '/' . $pkg->getName(),
            'script' => $this->getPearConfig()->get('bin_dir'),
        );

        $dir = dirname($package);
        foreach ($pkg->getInstallationFilelist() as $file) {
            $orig = realpath($dir . '/' . $file['attribs']['name']);
            if (empty($orig)) {
                $warnings[] = 'Install file does not seem to exist: ' . $dir . '/' . $file['attribs']['name'];
                continue;
            }

            switch ($file['attribs']['role']) {
            case 'horde':
            case 'php':
            case 'data':
            case 'script':
                if (isset($file['attribs']['install-as'])) {
                    $dest = $destDir[$file['attribs']['role']] . '/' . $file['attribs']['install-as'];
                } elseif (isset($file['attribs']['baseinstalldir'])) {
                    $dest = $destDir[$file['attribs']['role']] . $file['attribs']['baseinstalldir'] . '/' . $file['attribs']['name'];
                } else {
                    $dest = $destDir[$file['attribs']['role']] . '/' . $file['attribs']['name'];
                }
                break;

            default:
                $dest = null;
                break;
            }

            if (!is_null($dest)) {
                if (file_exists($dest)) {
                    @unlink($dest);
                } elseif (!file_exists(dirname($dest))) {
                    @mkdir(dirname($dest), 0777, true);
                }

                print 'SYMLINK: ' . $orig . ' -> ' . $dest . "\n";
                if (!symlink($orig, $dest)) {
                    $warnings[] = 'Could not link ' . $orig . '.';
                }
            }
        }
        $this->_output->pear(ob_get_clean());

        foreach ($warnings as $warning) {
            $this->_output->warn($warning);
        }

        $this->_output->ok(
            sprintf(
                'Successfully symlinked package %s%s',
                $package,
                $reason
            )
        );
    }

    /**
     * Add a channel within the install location.
     *
     * @param string $channel The channel name.
     * @param array  $options Install options.
     * @param string $reason  Optional reason for adding the channel.
     *
     * @return NULL
     */
    public function addChannel($channel, $options = array(), $reason = '')
    {
        $static = $this->_channel_directory . '/' . $channel . '.channel.xml';

        if (!file_exists($static)) {
            if (empty($options['allow_remote'])) {
                throw new Components_Exception(
                    sprintf(
                        'Cannot add channel "%s". Remote access has been disabled (activate with --allow-remote)!',
                        $channel
                    )
                );
            }
            if (!empty($this->_channel_directory)) {
                $remote = new Horde_Pear_Remote($channel);
                file_put_contents($static, $remote->getChannel());
                $this->_output->warn(
                    sprintf(
                        'Downloaded channel %s via network to %s.',
                        $channel,
                        $static
                    )
                );
            }
        }

        $channel_handler = new PEAR_Command_Channels(
            new PEAR_Frontend_CLI(),
            $this->getPearConfig()
        );

        $this->_output->ok(
            sprintf(
                'About to add channel %s%s',
                $channel,
                $reason
            )
        );

        if (file_exists($static)) {
            ob_start();
            Components_Exception_Pear::catchError(
                $channel_handler->doAdd('channel-add', array(), array($static))
            );
            $this->_output->pear(ob_get_clean());
        } else {
            $this->_output->warn(
                sprintf(
                    'Adding channel %s via network.',
                    $channel
                )
            );
            ob_start();
            Components_Exception_Pear::catchError(
                $channel_handler->doDiscover('channel-discover', array(), array($channel))
            );
            $this->_output->pear(ob_get_clean());
        }
        $this->_output->ok(
            sprintf(
                'Successfully added channel %s%s',
                $channel,
                $reason
            )
        );
    }

    /**
     * Add a component to the environemnt.
     *
     * @param string $component The name of the component that should be
     *                          installed.
     * @param string $install   The package that should be installed.
     * @param array  $options   PEAR specific installation opions.
     * @param string $info      Installation details.
     * @param string $reason    Optional reason for adding the package.
     * @param array  $warnings  Optional warnings that should be displayed to
     *                          the user.
     *
     * @return NULL
     */
    public function addComponent(
        $component,
        $install,
        $options,
        $info,
        $reason = '',
        $warnings = array()
    )
    {
        $installer = $this->getInstallationHandler();
        $this->_output->ok(
            sprintf(
                'About to add component %s%s',
                $component,
                $reason
            )
        );
        if (!empty($warnings)) {
            foreach ($warnings as $warning) {
                $this->_output->warn($warnings);
            }
        }
        ob_start();
        Components_Exception_Pear::catchError(
            $installer->doInstall('install', $options, $install)
        );
        $this->_output->pear(ob_get_clean());
        $this->_output->ok(
            sprintf(
                'Successfully added component %s%s%s',
                $component,
                $info,
                $reason
            )
        );
    }

    /**
     * Validate that the required instance parameters are set.
     *
     * @return NULL
     *
     * @throws Components_Exception In case some settings are missing.
     */
    private function _checkSetup()
    {
        if ($this->_factory === null) {
            throw new Components_Exception('You need to set the factory first!');
        }
    }

}
