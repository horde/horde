<?php
/**
 * Represents a component.
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
 * Represents a component.
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
interface Components_Component
{
    /**
     * Return the name of the component.
     *
     * @return string The component name.
     */
    public function getName();

    /**
     * Return the component summary.
     *
     * @return string The summary of the component.
     */
    public function getSummary();

    /**
     * Return the component description.
     *
     * @return string The description of the component.
     */
    public function getDescription();

    /**
     * Return the version of the component.
     *
     * @return string The component version.
     */
    public function getVersion();

    /**
     * Return the channel of the component.
     *
     * @return string The component channel.
     */
    public function getChannel();

    /**
     * Return the dependencies for the component.
     *
     * @return array The component dependencies.
     */
    public function getDependencies();

    /**
     * Return the component lead developers.
     *
     * @return string The component lead developers.
     */
    public function getLeads();

    /**
     * Return the component license.
     *
     * @return string The component license.
     */
    public function getLicense();

    /**
     * Return the component license URI.
     *
     * @return string The component license URI.
     */
    public function getLicenseLocation();

    /**
     * Return the dependency list for the component.
     *
     * @return Components_Component_DependencyList The dependency list.
     */
    public function getDependencyList();

    /**
     * Update the package.xml file for this component.
     *
     * @param string $action  The action to perform. Either "update", "diff",
     *                        or "print".
     * @param array  $options Options for this operation.
     *
     * @return NULL
     */
    public function updatePackageXml($action, $options);

    /**
     * Update the component changelog.
     *
     * @param string                      $log     The log entry.
     * @param Components_Helper_ChangeLog $helper  The change log helper.
     * @param array                       $options Options for the operation.
     *
     * @return NULL
     */
    public function changed(
        $log, Components_Helper_ChangeLog $helper, $options
    );

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
    public function placeArchive($destination, $options);

    /**
     * Identify the repository root.
     *
     * @param Components_Helper_Root $helper The root helper.
     *
     * @return NULL
     */
    public function repositoryRoot(Components_Helper_Root $helper);
}