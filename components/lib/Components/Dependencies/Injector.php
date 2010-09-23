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
     *
     * @param Components_Config $config The configuration.
     */
    public function __construct(Components_Config $config)
    {
        parent::__construct(new Horde_Injector_TopLevel());
        $this->setInstance('Components_Config', $config);
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
     * Returns the installer for a package.
     *
     * @return Components_Runner_Installer The installer.
     */
    public function getRunnerInstaller()
    {
        return $this->getInstance('Components_Runner_Installer');
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