<?php
/**
 * Cached access to the preferences data.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Cached access to the preferences data.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @since Horde_Kolab_Storage 1.1.0
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Data_Query_Preferences_Cache
implements Horde_Kolab_Storage_Data_Query_Preferences
{
    /** The preferences query data */
    const PREFS = 'PREFS';

    /**
     * The data cache.
     *
     * @var Horde_Kolab_Storage_Cache_Data
     */
    private $_data_cache;

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
    public function __construct(
        Horde_Kolab_Storage_Data $data,
        $params
    ) {
        $this->_data = $data;
        $this->_data_cache = $params['cache'];
        if ($this->_data_cache->hasQuery(self::PREFS)) {
            $this->_mapping = $this->_data_cache->getQuery(self::PREFS);
        } else {
            $this->_mapping = array();
        }
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
     * Synchronize the preferences information with the information from the
     * backend.
     *
     * @return NULL
     */
    public function synchronize()
    {
        foreach ($this->_data->getObjects() as $id => $data) {
            $this->_mapping[$data['application']] = $id;
        }
        $this->_data_cache->setQuery(self::PREFS, $this->_mapping);
        $this->_data_cache->save();
    }
}