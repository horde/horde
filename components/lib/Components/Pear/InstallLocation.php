<?php
/**
 * Components_Pear_InstallLocation:: handles a specific PEAR installation
 * location / configuration.
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
 * Components_Pear_InstallLocation:: handles a specific PEAR installation
 * location / configuration.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Components_Pear_InstallLocation
{
    /**
     * The output handler.
     *
     * @param Component_Output
     */
    private $_output;

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
    public function __construct(Components_Output $output)
    {
        $this->_output = $output;
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
        $this->_config_file = $base_directory . DIRECTORY_SEPARATOR . $config_file;
    }

    /**
     * Set the path to the channel directory.
     *
     * @param string $channel_directory The directory containing channel definitions.
     *
     * @return NULL
     */
    public function setChannelDirectory($channel_directory)
    {
        $this->_channel_directory = $channel_directory;
        if (!file_exists($this->_channel_directory)) {
            throw new Components_Exception(
                sprintf(
                    'The path to the channel directory (%s) does not exist!',
                    $this->_channel_directory
                )
            );
        }
    }

    /**
     * Set the path to the source directory.
     *
     * @param string $source_directory The directory containing PEAR packages.
     *
     * @return NULL
     */
    public function setSourceDirectory($source_directory)
    {
        $this->_source_directory = $source_directory;
        if (!file_exists($this->_source_directory)) {
            throw new Components_Exception(
                sprintf(
                    'The path to the source directory (%s) does not exist!',
                    $this->_source_directory
                )
            );
        }
    }

    /**
     * Set the paths to the resource directories.
     *
     * @param array $options The application options
     *
     * @return NULL
     */
    public function setResourceDirectories(array $options)
    {
        if (!empty($options['channelxmlpath'])) {
            $this->setChannelDirectory($options['channelxmlpath']);
        } else if (!empty($options['sourcepath'])) {
            $this->setChannelDirectory($options['sourcepath']);
        }
        if (!empty($options['sourcepath'])) {
            $this->setSourceDirectory($options['sourcepath']);
        }
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
        $command_config = new PEAR_Command_Config(new PEAR_Frontend_CLI(), new stdClass);
        $command_config->doConfigCreate(
            'config-create', array(), array($this->_base_directory, $this->_config_file)
        );
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
     * Add a channel within the install location.
     *
     * @param string $channel The channel name.
     * @param string $reason  Optional reason for adding the channel.
     *
     * @return NULL
     */
    public function addChannel($channel, $reason = '')
    {
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
        $static = $this->_channel_directory . DIRECTORY_SEPARATOR
            . $channel . '.channel.xml';
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
     * Ensure the specified channel exists within the install location.
     *
     * @param string $channel The channel name.
     * @param string $reason  Optional reason for adding the channel.
     *
     * @return NULL
     */
    public function provideChannel($channel, $reason = '')
    {
        if (!$this->channelExists($channel)) {
            $this->addChannel($channel, $reason);
        }
    }

    /**
     * Ensure the specified channels exists within the install location.
     *
     * @param array $channels The list of channels.
     * @param string $reason  Optional reason for adding the channels.
     *
     * @return NULL
     */
    public function provideChannels(array $channels, $reason = '')
    {
        foreach ($channels as $channel) {
            $this->provideChannel($channel, $reason);
        }
    }

    private function getInstallationHandler()
    {
        $installer = new PEAR_Command_Install(
            new PEAR_Frontend_CLI(),
            $this->getPearConfig()
        );
        $installer->setErrorHandling(PEAR_ERROR_EXCEPTION);
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
    public function addPackageFromSource($package, $reason = '')
    {
        $installer = $this->getInstallationHandler();
        $this->_output->ok(
            sprintf(
                'About to add package %s%s',
                $package,
                $reason
            )
        );
        ob_start();
        $installer->doInstall(
            'install',
            array('nodeps' => true),
            array($package)
        );
        $this->_output->pear(ob_get_clean());
        $this->_output->ok(
            sprintf(
                'Successfully added package %s%s',
                $package,
                $reason
            )
        );
    }

    /**
     * Add a package based on a package name or package tarball.
     *
     * @param string $channel The channel name for the package.
     * @param string $package The name of the package of the path of the tarball.
     * @param string $reason  Optional reason for adding the package.
     * @param array  $locals  Packages currently being installed.
     *
     * @return NULL
     */
    public function addPackageFromPackage($channel, $package, $reason = '', array $locals = null)
    {
        $installer = $this->getInstallationHandler();
        $this->_output->ok(
            sprintf(
                'About to add package %s%s',
                $package,
                $reason
            )
        );
        if ($local = $this->_identifyMatchingLocalPackage($package)) {
            if (empty($locals)) {
                $locals = array($package);
            } else {
                $locals[] = $package;
            }
            $pkg = new PEAR_PackageFile($this->getPearConfig());
            $pkg = $pkg->fromTgzFile($local, PEAR_VALIDATE_NORMAL);
            foreach ($pkg->getDeps() as $dependency) {
                if ($dependency['type'] != 'pkg') {
                    continue;
                }
                if (isset($dependency['optional']) && $dependency['optional'] == 'no') {
                    if (in_array($dependency['name'], $locals)) {
                        continue;
                    }
                    $this->addPackageFromPackage($dependency['channel'], $dependency['name'], $reason, $locals);
                }
            }
            ob_start();
            $installer->doInstall(
                'install',
                array(
                    'offline' => true
                ),
                array($local)
            );
            $this->_output->pear(ob_get_clean());
        } else {
            $this->_output->warn(
                sprintf(
                    'Adding package %s via network.',
                    $package
                )
            );
            ob_start();
            $installer->doInstall(
                'install',
                array(
                    'channel' => $channel,
                ),
                array($package)
            );
            $this->_output->pear(ob_get_clean());
        }
        $this->_output->ok(
            sprintf(
                'Successfully added package %s%s',
                $package,
                $reason
            )
        );
    }

    private function _identifyMatchingLocalPackage($package)
    {
        if (empty($this->_source_directory)) {
            return false;
        }
        foreach (new DirectoryIterator($this->_source_directory) as $file) {
            if (preg_match('/' . $package . '-[0-9]+(\.[0-9]+)+([a-z0-9]+)?/', $file->getBasename('.tgz'), $matches)) {
                return $file->getPathname();
            }
        }
        return false;
    }
}
