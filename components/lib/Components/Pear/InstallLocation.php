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
class Components_Pear_InstallLocation
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
        mkdir("$root/pear");
        mkdir("$root/pear/php");
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
        Components_Exception_Pear::catchError(
            $installer->doInstall(
                'install',
                array('nodeps' => true),
                array($package)
            )
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

        $hordeDir = $this->getPearConfig()->get('horde_dir', 'user', 'pear.horde.org');
        $destDir = $this->getPearConfig()->get('php_dir');

        ob_start();
        $warnings = array();
        $pkg = $this->_factory->createPackageForEnvironment($package, $this);
        $dir = dirname($package);
        foreach ($pkg->getInstallationFilelist() as $file) {
            $orig = realpath($dir . '/' . $file['attribs']['name']);
            if (empty($orig)) {
                $warnings[] = 'Install file does not seem to exist: ' . $dir . '/' . $file['attribs']['name'];
                continue;
            }

            switch ($file['attribs']['role']) {
            case 'horde':
                if (isset($file['attribs']['install-as'])) {
                    $dest = $hordeDir . '/' . $file['attribs']['install-as'];
                } else {
                    $warnings[] = 'Could not determine install directory (role "horde") for ' . $hordeDir;
                    continue;
                }
                break;

            case 'php':
                if (isset($file['attribs']['install-as'])) {
                    $dest = $destDir . '/' . $file['attribs']['install-as'];
                } elseif (isset($file['attribs']['baseinstalldir'])) {
                    $dest = $destDir . $file['attribs']['baseinstalldir'] . '/' . $file['attribs']['name'];
                } else {
                    $dest = $destDir . '/' . $file['attribs']['name'];
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
     * Add an external dependency based on a package name or package tarball.
     *
     * @param Components_Pear_Dependency $dependency The package dependency.
     * @param string $package The name of the package of the path of the tarball.
     * @param string $reason  Optional reason for adding the package.
     *
     * @return NULL
     */
    public function addPackageFromPackage(
        Components_Pear_Dependency $dependency,
        $reason = ''
    ) {
        $installer = $this->getInstallationHandler();
        $this->_output->ok(
            sprintf(
                'About to add external package %s%s',
                $dependency->key(),
                $reason
            )
        );
        if ($local = $this->_identifyMatchingLocalPackage($dependency->name())) {
            $pkg = $this->_factory->getPackageFileFromTgz($local, $this);

            ob_start();
            Components_Exception_Pear::catchError(
                $installer->doInstall(
                    'install',
                    array(
                        'offline' => true
                    ),
                    array($local)
                )
            );
            $this->_output->pear(ob_get_clean());
        } else {
            $this->_output->warn(
                sprintf(
                    'Adding external package %s via network.',
                    $dependency->key()
                )
            );
            ob_start();
            Components_Exception_Pear::catchError(
                $installer->doInstall(
                    'install',
                    array(
                        'channel' => $dependency->channel(),
                    ),
                    array($dependency->name())
                )
            );
            $this->_output->pear(ob_get_clean());
        }
        $this->_output->ok(
            sprintf(
                'Successfully added external package %s%s',
                $dependency->key(),
                $reason
            )
        );
    }

    /**
     * Identify any dependencies we need when installing via downloaded packages.
     *
     * @param Components_Pear_Dependency $dependency The package dependency.
     *
     * @return Components_Pear_Dependencies The dependency helper for the local package.
     */
    public function identifyRequiredLocalDependencies(
        Components_Pear_Dependency $dependency
    ) {
        if ($local = $this->_identifyMatchingLocalPackage($dependency->name())) {
            $this->_checkSetup();
            return $this->_factory
                ->createTgzPackageForInstallLocation($local, $this)
                ->getDependencyHelper();
        }
        return false;
    }

    /**
     * Identify a dependency that is available via a downloaded *.tgz archive.
     *
     * @param string $package The package name.
     *
     * @return string A path to the local archive if it was found.
     */
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
            throw new Components_Exception('You need to set the factory, the environment and the path to the package file first!');
        }
    }

}
