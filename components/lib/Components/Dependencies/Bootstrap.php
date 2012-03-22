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
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * The Components_Dependencies_Bootstrap:: class provides the Components
 * dependencies specifically for the bootstrapping process.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
            case 'Components_Component_Factory':
                require_once __DIR__ . '/../Component/Factory.php';
                $this->_instances[$interface] = new $interface(
                    $this->getInstance('Components_Config'),
                    $this->getInstance('Components_Pear_Factory'),
                    new Horde_Http_Client()
                );
                break;
            case 'Components_Pear_Factory':
                require_once __DIR__ . '/../Pear/Factory.php';
                $this->_instances[$interface] = new $interface($this);
                break;
            case 'Components_Config':
                require_once __DIR__ . '/../Config.php';
                require_once __DIR__ . '/../Config/Base.php';
                require_once __DIR__ . '/../Config/Bootstrap.php';
                $this->_instances[$interface] = new Components_Config_Bootstrap();
                break;
            case 'Components_Output':
                require_once __DIR__ . '/../Output.php';
                $this->_instances[$interface] = new Components_Output(
                    $this->getInstance('Horde_Cli'),
                    $this->getInstance('Components_Config')->getOptions()
                );
                break;
            case 'Horde_Cli':
                require_once __DIR__ . '/../../../../framework/Cli/lib/Horde/Cli.php';
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
        case 'Components_Pear_Environment':
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
     * Set the list of modules.
     *
     * @param Horde_Cli_Modular $modules The list of modules.
     *
     * @return NULL
     */
    public function setModules(Horde_Cli_Modular $modules)
    {
    }

    /**
     * Return the list of modules.
     *
     * @retunr Horde_Cli_Modular The list of modules.
     */
    public function getModules()
    {
    }

    /**
     * Set the CLI parser.
     *
     * @param Horde_Argv_Parser $parser The parser.
     *
     * @return NULL
     */
    public function setParser($parser)
    {
    }

    /**
     * Return the CLI parser.
     *
     * @retunr Horde_Argv_Parser The parser.
     */
    public function getParser()
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
     * Returns the website documentation handler for a package.
     *
     * @return Components_Runner_Webdocs The documentation handler.
     */
    public function getRunnerWebdocs()
    {
        return $this->getInstance('Components_Runner_Webdocs');
    }

    /**
     * Returns the documentation fetch handler for a package.
     *
     * @return Components_Runner_Fetchdocs The fetch handler.
     */
    public function getRunnerFetchdocs()
    {
        return $this->getInstance('Components_Runner_Fetchdocs');
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
     * Returns the qc handler for a package.
     *
     * @return Components_Runner_Qc The qc handler.
     */
    public function getRunnerQc()
    {
        return $this->getInstance('Components_Runner_Qc');
    }

    /**
     * Returns the change log handler for a package.
     *
     * @return Components_Runner_Change The change log handler.
     */
    public function getRunnerChange()
    {
        return $this->getInstance('Components_Runner_Change');
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
     * @return Components_Runner_Update The package XML handler.
     */
    public function getRunnerUpdate()
    {
        return $this->getInstance('Components_Runner_Update');
    }

    /**
     * Returns the release tasks handler.
     *
     * @return Components_Release_Tasks The release tasks handler.
     */
    public function getReleaseTasks()
    {
        return $this->getInstance('Components_Release_Tasks');
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
     * Returns a component instance factory.
     *
     * @return Components_Component_Factory The component factory.
     */
    public function getComponentFactory()
    {
        return $this->getInstance('Components_Component_Factory');
    }

    /**
     * Returns the handler for remote PEAR servers.
     *
     * @return Horde_Pear_Remote The handler.
     */
    public function getRemote()
    {
        return $this->getInstance('Horde_Pear_Remote');
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