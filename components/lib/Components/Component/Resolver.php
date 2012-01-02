<?php
/**
 * Components_Component_Resolver:: resolves component names and dependencies
 * into component representations.
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
 * Components_Component_Resolver:: resolves component names and dependencies
 * into component representations.
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
class Components_Component_Resolver
{
    /**
     * The factory for the component representation of a dependency.
     *
     * @var Components_Component_Factory
     */
    private $_factory;

    /**
     * The repository root.
     *
     * @var Components_Helper_Root
     */
    private $_root;

    /**
     * The list of remotes already generated.
     *
     * @var array
     */
    private $_remotes;

    /**
     * Constructor.
     *
     * @param Components_Helper_Root       $root    The repository root.
     * @param Components_Component_Factory $factory Helper factory.
     */
    public function __construct(
        Components_Helper_Root $root, Components_Component_Factory $factory
    )
    {
        $this->_factory = $factory;
        $this->_root = $root;
    }

    /**
     * Try to resolve a dependency into a component.
     *
     * @param Components_Component_Dependency $dependency The dependency.
     * @param array                           $options    Additional options.
     * <pre>
     *  - allow_remote: May the resolver try to resolve to a remote channel?
     *  - order:        Order of stability preference.
     * </pre>
     *
     * @return Components_Component|boolean The component if the name could be
     *                                      resolved.
     */
    public function resolveDependency(
        Components_Component_Dependency $dependency, $options
    )
    {
        return $this->resolveName(
            $dependency->getName(), $dependency->getChannel(), $options
        );
    }

    /**
     * Try to resolve the given name and channel into a component.
     *
     * @param string $name    The name of the component.
     * @param string $channel The channel origin of the component.
     * @param array  $options Additional options.
     *
     * @return Components_Component|boolean The component if the name could be
     *                                      resolved.
     */
    public function resolveName($name, $channel, $options)
    {
        foreach ($this->_getAttempts($options) as $attempt) {
            if ($attempt == 'git' && $channel == 'pear.horde.org') {
                try {
                    $path = $this->_root->getPackageXml($name);
                    return $this->_factory->createSource(dirname($path));
                } catch (Components_Exception $e) {
                }
            }
            if ($attempt == 'snapshot') {
                if ($local = $this->_identifyMatchingLocalPackage($name, $channel, $options)) {
                    return $this->_factory->createArchive(
                        $local
                    );
                }
            }
            if (!empty($options['allow_remote'])) {
                $remote = $this->_getRemote($channel);
                if ($remote->getLatestRelease($name, $attempt)) {
                    return $this->_factory->createRemote(
                        $name, $attempt, $channel, $remote
                    );
                }
            }
        }
        return false;
    }

    /**
     * Return the order of resolve attempts.
     *
     * @param array $options Resolve options.
     *
     * @return array The list of attempts
     */
    private function _getAttempts($options)
    {
        if (isset($options['order'])) {
            return $options['order'];
        }
        $order = array('git', 'snapshot', 'stable', 'beta', 'alpha', 'devel');
        if (!empty($options['snapshot'])) {
            $order = array('snapshot', 'stable', 'beta', 'alpha', 'devel', 'git');
        }
        if (!empty($options['stable'])) {
            $order = array('stable', 'beta', 'alpha', 'devel', 'snapshot', 'git');
        }
        if (!empty($options['beta'])) {
            $order = array('beta', 'stable', 'alpha', 'devel', 'snapshot', 'git');
        }
        if (!empty($options['alpha'])) {
            $order = array('alpha', 'beta', 'stable', 'devel', 'snapshot', 'git');
        }
        if (!empty($options['devel'])) {
            $order = array('devel', 'alpha', 'beta', 'stable', 'snapshot', 'git');
        }
        if (empty($options['allow_remote'])) {
            $result = array();
            foreach ($order as $element) {
                if (in_array($element, array('git', 'snapshot'))) {
                    $result[] = $element;
                }
            }
            return $result;
        }
        return $order;
    }

    /**
     * Get a remote PEAR server handler for a specific channel.
     *
     * @param string $channel The channel name.
     *
     * @return Horde_Pear_Remote The remote handler.
     */
    private function _getRemote($channel)
    {
        if (!isset($this->_remotes[$channel])) {
            $this->_remotes[$channel] = $this->_factory->createRemoteChannel(
                $channel
            );
        }
        return $this->_remotes[$channel];
    }

    /**
     * Identify a dependency that is available via a downloaded *.tgz archive.
     *
     * @param string $name    The component name.
     * @param string $channel The component channel.
     * @param array  $options Resolve options.
     *
     * @return string A path to the local archive if it was found.
     */
    public function _identifyMatchingLocalPackage($name, $channel, $options)
    {
        if (empty($options['sourcepath'])) {
            return false;
        }
        $source = $options['sourcepath'] . '/' . $channel;
        if (!file_exists($source)) {
            return false;
        }
        foreach (new DirectoryIterator($source) as $file) {
            if (preg_match('/' . $name . '-[0-9]+(\.[0-9]+)+([a-z0-9]+)?/', $file->getBasename('.tgz'), $matches)) {
                return $file->getPathname();
            }
        }
        return false;
    }

}
