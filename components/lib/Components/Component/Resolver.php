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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Component_Resolver:: resolves component names and dependencies
 * into component representations.
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
            $dependency->name(), $dependency->channel(), $options
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
            } else {
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
        if (empty($options['allow_remote'])) {
            return array('git');
        }
        if (isset($options['order'])) {
            return $options['order'];
        } else {
            return array('git', 'stable', 'beta', 'alpha', 'devel');
        }
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
}
