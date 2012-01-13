<?php
/**
 * Represents a remote component.
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
 * Represents a remote component.
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
class Components_Component_Remote extends Components_Component_Base
{
    /**
     * The remote handler.
     *
     * @var Horde_Pear_Remote
     */
    private $_remote;

    /**
     * Component name.
     *
     * @var string
     */
    private $_name;

    /**
     * Component channel.
     *
     * @var string
     */
    private $_channel;

    /**
     * Component stability.
     *
     * @var string
     */
    private $_stability;

    /**
     * Component version.
     *
     * @var string
     */
    private $_version;

    /**
     * Download location for the component.
     *
     * @var string
     */
    private $_uri;

    /**
     * The HTTP client for remote access.
     *
     * @var Horde_Http_Client
     */
    private $_client;

    /**
     * The package file representing the component.
     *
     * @var Horde_Pear_Package_Xml
     */
    private $_package;

    /**
     * Constructor.
     *
     * @param string                  $name      Component name.
     * @param string                  $stability Component stability.
     * @param string                  $channel   Component channel.
     * @param Horde_Pear_Remote       $remote    Remote channel handler.
     * @param Horde_Http_Client       $client    The HTTP client for remote
     *                                           access.
     * @param Components_Config       $config    The configuration for the
     *                                           current job.
     * @param Components_Component_Factory $factory Generator for additional
     *                                              helpers.
     */
    public function __construct(
        $name,
        $stability,
        $channel,
        Horde_Pear_Remote $remote,
        Horde_Http_Client $client,
        Components_Config $config,
        Components_Component_Factory $factory
    )
    {
        $this->_name = $name;
        $this->_stability = $stability;
        $this->_channel = $channel;
        $this->_remote = $remote;
        $this->_client  = $client;
        parent::__construct($config, $factory);
    }

    /**
     * Return the name of the component.
     *
     * @return string The component name.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Return the version of the component.
     *
     * @return string The component version.
     */
    public function getVersion()
    {
        if (!isset($this->_version)) {
            $this->_version = $this->_remote->getLatestRelease($this->_name, $this->_stability);
        }
        return $this->_version;
    }

    /**
     * Return the channel of the component.
     *
     * @return string The component channel.
     */
    public function getChannel()
    {
        return $this->_channel;
    }

    /**
     * Return the dependencies for the component.
     *
     * @return array The component dependencies.
     */
    public function getDependencies()
    {
        return $this->_remote->getDependencies(
            $this->getName(), $this->getVersion()
        );
    }

    /**
     * Return a data array with the most relevant information about this
     * component.
     *
     * @return array Information about this component.
     */
    public function getData()
    {
        $data = new stdClass;
        $release = $this->_remote->getLatestDetails($this->_name, null);
        $data->name = $this->_name;
        $data->summary = $release->getSummary();
        $data->description = $release->getDescription();
        $data->version = $release->getVersion();
        $data->releaseDate = (string)$release->da;
        $data->download = $release->getDownloadUri();
        $data->hasCi = $this->_hasCi();
        return $data;
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
        $this->createDestination($destination);
        $this->_client->{'request.timeout'} = 60;
        file_put_contents(
            $destination . '/' . basename($this->_getDownloadUri()),
            $this->_client->get($this->_getDownloadUri())->getStream()
        );
        return array($destination . '/' . basename($this->_getDownloadUri()));
    }

    /**
     * Return the download URI of the component.
     *
     * @return string The download URI.
     */
    private function _getDownloadUri()
    {
        if (!isset($this->_uri)) {
            $this->_uri = $this->_remote->getLatestDownloadUri(
                $this->_name, $this->_stability
            );
        }
        return $this->_uri;
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
        if (empty($options['allow_remote'])) {
            throw new Components_Exception(
                sprintf(
                    'Cannot add component "%s". Remote access has been disabled (activate with --allow-remote)!',
                    $this->getName()
                )
            );
        }

        $this->installChannel($env, $options);

        $installation_options = $this->getBaseInstallationOptions($options);
        $installation_options['channel'] = $this->getChannel();
        $env->addComponent(
            $this->getName(),
            array(
                'channel://' . $this->getChannel() . '/' . $this->getName()
            ),
            $installation_options,
            ' via remote channel ' . $this->getChannel(),
            $reason,
            array(
                sprintf(
                    'Adding component %s/%s via network.',
                    $this->getChannel(),
                    $this->getName()
                )
            )
        );
    }

    /**
     * Return a PEAR package representation for the component.
     *
     * @return Horde_Pear_Package_Xml The package representation.
     */
    protected function getPackageXml()
    {
        if (!isset($this->_package)) {
            $this->_package = $this->_remote->getPackageXml(
                $this->getName(), $this->getVersion()
            );
        }
        return $this->_package;
    }

    /**
     * Check if the library has a CI job.
     *
     * @return boolean True if a CI job is defined.
     */
    private function _hasCi()
    {
        if ($this->_channel != 'pear.horde.org') {
            return false;
        }
        $client = new Horde_Http_Client(array('request.timeout' => 15));
        try {
            $response = $client->get('http://ci.horde.org/job/' . str_replace('Horde_', '', $this->_name . '/api/json'));
        } catch (Horde_Http_Exception $e) {
            return false;
        }
        return $response->code != 404;
    }
}