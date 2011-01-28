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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * The Components_Dependencies:: interface is a central broker for
 * providing the dependencies to the different application parts.
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
     * Returns the snapshot packaging handler for a package.
     *
     * @return Components_Runner_DevPackage The snapshot handler.
     */
    public function getRunnerDevPackage();

    /**
     * Returns the distribution handler for a package.
     *
     * @return Components_Runner_Distribute The distribution handler.
     */
    public function getRunnerDistribute();

    /**
     * Returns the documentation handler for a package.
     *
     * @return Components_Runner_Document The distribution handler.
     */
    public function getRunnerDocument();

    /**
     * Returns the installer for a package.
     *
     * @return Components_Runner_Installer The installer.
     */
    public function getRunnerInstaller();

    /**
     * Returns the package XML handler for a package.
     *
     * @return Components_Runner_PearPackageXml The package XML handler.
     */
    public function getRunnerPearPackageXml();

    /**
     * Returns the output handler.
     *
     * @return Components_Output The output handler.
     */
    public function getOutput();
}