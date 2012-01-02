<?php
/**
 * Basic query for preferences data.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Basic query for preferences data.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Kolab_Storage 1.1.0
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Data_Query_Preferences_Base
implements Horde_Kolab_Storage_Data_Query_Preferences
{
    /**
     * The queriable data.
     *
     * @var Horde_Kolab_Storage_Data
     */
    private $_data;

    /**
     * The cached preference mapping.
     *
     * @var array
     */
    private $_mapping;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Data $data   The queriable data.
     * @param array                    $params Additional parameters.
     */
    public function __construct(Horde_Kolab_Storage_Data $data,
                                $params)
    {
        $this->_data = $data;
    }

    /**
     * Return the preferences for the specified application.
     *
     * @param string $application The application.
     *
     * @return array The preferences.
     */
    public function getApplicationPreferences($application)
    {
        $this->_initMapping();
        if (isset($this->_mapping[$application])) {
            return $this->_data->getObject($this->_mapping[$application]);
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    'No preferences for application %s available',
                    $application
                )
            );
        }
    }

    /**
     * Return the applications for which preferences exist in the backend.
     *
     * @param string $application The application.
     *
     * @return array The applications.
     */
    public function getApplications()
    {
        $this->_initMapping();
        return array_keys($this->_mapping);
    }

    /**
     * Initialize the application <-> object mapping.
     *
     * @return NULL
     */
    private function _initMapping()
    {
        if ($this->_mapping === null) {
            foreach ($this->_data->getObjects() as $id => $data) {
                $this->_mapping[$data['application']] = $id;
            }
        }
    }

    /**
     * Synchronize the preferences information with the information from the
     * backend.
     *
     * @param array $params Additional parameters.
     *
     * @return NULL
     */
    public function synchronize($params = array())
    {
    }
}