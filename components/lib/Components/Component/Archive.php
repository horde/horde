<?php
/**
 * Represents a component archive.
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
 * Represents a component archive.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
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
class Components_Component_Archive extends Components_Component_Base
{
    /**
     * Path to the archive.
     *
     * @var string
     */
    private $_archive;

    /**
     * Constructor.
     *
     * @param string                  $directory Path to the source directory.
     * @param boolean                 $shift     Did identification of the
     *                                           component consume an argument?
     * @param Components_Config       $config    The configuration for the
     *                                           current job.
     * @param Components_Component_Factory $factory Generator for additional
     *                                              helpers.
     */
    public function __construct(
        $archive,
        Components_Config $config,
        Components_Component_Factory $factory
    )
    {
        $this->_archive = $archive;
        parent::__construct($config, $factory);
    }

    /**
     * Return the name of the component.
     *
     * @return string The component name.
     */
    public function getName()
    {
        return $this->getPackage()->getName();
    }

    /**
     * Return the version of the component.
     *
     * @return string The component version.
     */
    public function getVersion()
    {
        return $this->getPackage()->getVersion();
    }

    /**
     * Return the channel of the component.
     *
     * @return string The component channel.
     */
    public function getChannel()
    {
        return $this->getPackage()->getChannel();
    }

    /**
     * Return the dependencies for the component.
     *
     * @return array The component dependencies.
     */
    public function getDependencies()
    {
        return $this->getPackage()->getDependencies();
    }

    /**
     * Place the component source archive at the specified location.
     *
     * @param string $destination The path to write the archive to.
     * @param array  $options     Options for the operation.
     *
     * @return array An array with at least [0] the path to the resulting
     *               archive, optionally [1] an array of error strings, and [2]
     *               PEAR output.
     */
    public function placeArchive($destination, $options = array())
    {
        copy($this->_archive, $destination . '/' . basename($this->_archive));
        return array($destination . '/' . basename($this->_archive));
    }

    /**
     * Install a component.
     *
     * @param Components_Pear_Environment $env The environment to install
     *                                         into.
     * @param array                 $options   Install options.
     * @param string                $reason    Optional reason for adding the
     *                                         package.
     *
     * @return NULL
     */
    public function install(
        Components_Pear_Environment $env, $options = array(), $reason = ''
    )
    {
        $this->installChannel($env, $options);

        $installation_options = array();
        $installation_options['force'] = !empty($options['force']);
        $installation_options['nodeps'] = !empty($options['nodeps']);
        $installation_options['offline'] = true;

        $env->addComponent(
            $this->getName(),
            array($component->getArchivePath()),
            $installation_options,
            ' from the archive ' . $component->getArchivePath(),
            $reason
        );
    }
}