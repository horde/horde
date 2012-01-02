<?php
/**
 * The Components_Dependencies:: interface is a central broker for
 * providing the dependencies to the different application parts.
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
 * The Components_Dependencies:: interface is a central broker for
 * providing the dependencies to the different application parts.
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
interface Components_Dependencies
{
    /**
     * Initial configuration setup.
     *
     * @param Components_Config $config The configuration.
     *
     * @return NULL
     */
    public function initConfig(Components_Config $config);

    /**
     * Set the list of modules.
     *
     * @param Horde_Cli_Modular $modules The list of modules.
     *
     * @return NULL
     */
    public function setModules(Horde_Cli_Modular $modules);

    /**
     * Return the list of modules.
     *
     * @retunr Horde_Cli_Modular The list of modules.
     */
    public function getModules();

    /**
     * Set the CLI parser.
     *
     * @param Horde_Argv_Parser $parser The parser.
     *
     * @return NULL
     */
    public function setParser($parser);

    /**
     * Return the CLI parser.
     *
     * @retunr Horde_Argv_Parser The parser.
     */
    public function getParser();

    /**
     * Returns the continuous integration setup handler.
     *
     * @return Components_Runner_CiSetup The CI setup handler.
     */
    public function getRunnerCiSetup();

    /**
     * Returns the continuous integration pre-build handler.
     *
     * @return Components_Runner_CiPrebuild The CI pre-build handler.
     */
    public function getRunnerCiPrebuild();

    /**
     * Returns the release handler for a package.
     *
     * @return Components_Runner_Release The release handler.
     */
    public function getRunnerRelease();

    /**
     * Returns the qc handler for a package.
     *
     * @return Components_Runner_Qc The qc handler.
     */
    public function getRunnerQc();

    /**
     * Returns the change log handler for a package.
     *
     * @return Components_Runner_Change The change log handler.
     */
    public function getRunnerChange();

    /**
     * Returns the snapshot packaging handler for a package.
     *
     * @return Components_Runner_Snapshot The snapshot handler.
     */
    public function getRunnerSnapshot();

    /**
     * Returns the distribution handler for a package.
     *
     * @return Components_Runner_Distribute The distribution handler.
     */
    public function getRunnerDistribute();

    /**
     * Returns the website documentation handler for a package.
     *
     * @return Components_Runner_Webdocs The documentation handler.
     */
    public function getRunnerWebdocs();

    /**
     * Returns the documentation fetch handler for a package.
     *
     * @return Components_Runner_Fetchdocs The fetch handler.
     */
    public function getRunnerFetchdocs();

    /**
     * Returns the installer for a package.
     *
     * @return Components_Runner_Installer The installer.
     */
    public function getRunnerInstaller();

    /**
     * Returns the package XML handler for a package.
     *
     * @return Components_Runner_Update The package XML handler.
     */
    public function getRunnerUpdate();

    /**
     * Returns the release tasks handler.
     *
     * @return Components_Release_Tasks The release tasks handler.
     */
    public function getReleaseTasks();

    /**
     * Returns the output handler.
     *
     * @return Components_Output The output handler.
     */
    public function getOutput();

    /**
     * Returns a component instance factory.
     *
     * @return Components_Component_Factory The component factory.
     */
    public function getComponentFactory();

    /**
     * Returns the handler for remote PEAR servers.
     *
     * @return Horde_Pear_Remote The handler.
     */
    public function getRemote();
}