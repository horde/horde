<?php
/**
 * The Components_Dependencies_Injector:: class provides the
 * Components dependencies based on the Horde injector.
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
 * The Components_Dependencies_Injector:: class provides the
 * Components dependencies based on the Horde injector.
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
class Components_Dependencies_Injector
extends Horde_Injector
implements Components_Dependencies
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(new Horde_Injector_TopLevel());
        $this->setInstance('Components_Dependencies', $this);
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
        $this->setInstance('Components_Config', $config);
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
}