<?php
/**
 * The Components_Dependencies_Bootstrap:: class provides the Components
 * dependencies specifically for the bootstrapping process.
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
 * The Components_Dependencies_Bootstrap:: class provides the Components
 * dependencies specifically for the bootstrapping process.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
class Components_Dependencies_Bootstrap
implements Components_Dependencies
{
    /**
     * Initialized instances.
     *
     * @var array
     */
    private $_instances;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Returns an instance.
     *
     * @param string $interface The interface matching the requested instance.
     *
     * @return mixed the instance.
     */
    public function getInstance($interface)
    {
        if (!isset($this->_instances[$interface])) {
            switch ($interface) {
            case 'Components_Pear_Factory':
                require_once dirname(__FILE__) . '/../Pear/Factory.php';
                $this->_instances[$interface] = new $interface($this);
                break;
            case 'Components_Config':
                require_once dirname(__FILE__) . '/../Config.php';
                require_once dirname(__FILE__) . '/../Config/Bootstrap.php';
                $this->_instances[$interface] = new Components_Config_Bootstrap();
                break;
            case 'Components_Output':
                require_once dirname(__FILE__) . '/../Output.php';
                $this->_instances[$interface] = new Components_Output(
                    $this->getInstance('Horde_Cli'),
                    $this->getInstance('Components_Config')
                );
                break;
            case 'Horde_Cli':
                require_once dirname(__FILE__) . '/../../../../framework/Cli/lib/Horde/Cli.php';
                $this->_instances[$interface] = new Horde_Cli();
                break;
            }
        }
        return $this->_instances[$interface];
    }

    /**
     * Creates an instance.
     *
     * @param string $interface The interface matching the requested instance.
     *
     * @return mixed the instance.
     */
    public function createInstance($interface)
    {
        switch ($interface) {
        case 'Components_Pear_InstallLocation':
            return new $interface($this->getInstance('Components_Output'));
        case 'Components_Pear_Package':
            return new $interface($this->getInstance('Components_Output'));
        case 'Components_Pear_Dependencies':
            return new $interface($this->getInstance('Components_Output'));
        }
    }

    /**
     * Initial configuration setup.
     *
     * @param Components_Config $config The configuration.
     *
     * @return NULL
     */
    public function initConfig(Components_Config $config)
    {
    }

    /**
     * Returns the continuous integration setup handler.
     *
     * @return Components_Runner_CiSetup The CI setup handler.
     */
    public function getRunnerCiSetup()
    {
        return $this->getInstance('Components_Runner_CiSetup');
    }

    /**
     * Returns the continuous integration pre-build handler.
     *
     * @return Components_Runner_CiPrebuild The CI pre-build handler.
     */
    public function getRunnerCiPrebuild()
    {
        return $this->getInstance('Components_Runner_CiPrebuild');
    }

    /**
     * Returns the distribution handler for a package.
     *
     * @return Components_Runner_Distribute The distribution handler.
     */
    public function getRunnerDistribute()
    {
        return $this->getInstance('Components_Runner_Distribute');
    }

    /**
     * Returns the documentation handler for a package.
     *
     * @return Components_Runner_Document The distribution handler.
     */
    public function getRunnerDocument()
    {
        return $this->getInstance('Components_Runner_Document');
    }

    /**
     * Returns the release handler for a package.
     *
     * @return Components_Runner_Release The release handler.
     */
    public function getRunnerRelease()
    {
        return $this->getInstance('Components_Runner_Release');
    }

    /**
     * Returns the snapshot packaging handler for a package.
     *
     * @return Components_Runner_Snapshot The snapshot handler.
     */
    public function getRunnerSnapshot()
    {
        return $this->getInstance('Components_Runner_Snapshot');
    }

    /**
     * Returns the dependency list handler for a package.
     *
     * @return Components_Runner_Dependencies The dependency handler.
     */
    public function getRunnerDependencies()
    {
        return $this->getInstance('Components_Runner_Dependencies');
    }

    /**
     * Returns the installer for a package.
     *
     * @return Components_Runner_Installer The installer.
     */
    public function getRunnerInstaller()
    {
        return $this->getInstance('Components_Runner_Installer');
    }

    /**
     * Returns the package XML handler for a package.
     *
     * @return Components_Runner_PearPackageXml The package XML handler.
     */
    public function getRunnerPearPackageXml()
    {
        return $this->getInstance('Components_Runner_PearPackageXml');
    }

    /**
     * Returns the output handler.
     *
     * @return Components_Output The output handler.
     */
    public function getOutput()
    {
        return $this->getInstance('Components_Output');
    }

    /**
     * Create the CLI handler.
     *
     * @return Horde_Cli The CLI handler.
     */
    public function createCli()
    {
        return Horde_Cli::init();
    }
}