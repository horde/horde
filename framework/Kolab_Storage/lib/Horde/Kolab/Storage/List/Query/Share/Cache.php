<?php
/**
 * Caches share parameters.
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
 * Caches share parameters.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_List_Query_Share_Cache
extends Horde_Kolab_Storage_List_Query_Share
implements Horde_Kolab_Storage_List_Manipulation_Listener,
Horde_Kolab_Storage_List_Synchronization_Listener
{
    /** The share description */
    const DESCRIPTIONS = 'SHARE_DESCRIPTIONS';

    /** The share parameters */
    const PARAMETERS = 'SHARE_PARAMETERS';

    /**
     * The underlying Share query.
     *
     * @param Horde_Kolab_Storage_List_Query_Share
     */
    private $_query;

    /**
     * The list cache.
     *
     * @var Horde_Kolab_Storage_Cache_List
     */
    private $_list_cache;

    /**
     * The cached share descriptions.
     *
     * @var array
     */
    private $_descriptions;

    /**
     * The cached share parameters.
     *
     * @var array
     */
    private $_parameters;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List_Query_Share $query The underlying share query.
     * @param Horde_Kolab_Storage_List_Cache $cache The list cache.
     */
    public function __construct(Horde_Kolab_Storage_List_Query_Share $query,
                                Horde_Kolab_Storage_List_Cache $cache)
    {
        $this->_query = $query;
        $this->_list_cache = $cache;
        if ($this->_list_cache->hasQuery(self::DESCRIPTIONS)) {
            $this->_descriptions = $this->_list_cache->getQuery(self::DESCRIPTIONS);
        } else {
            $this->_descriptions = array();
        }
        if ($this->_list_cache->hasLongTerm(self::PARAMETERS)) {
            $this->_parameters = $this->_list_cache->getLongTerm(self::PARAMETERS);
        } else {
            $this->_parameters = array();
        }
    }

    /**
     * Returns the share description.
     *
     * @param string $folder The folder name.
     *
     * @return string The folder/share description.
     */
    public function getDescription($folder)
    {
        if (!isset($this->_descriptions[$folder])) {
            $this->_descriptions[$folder] = $this->_query->getDescription($folder);
            $this->_list_cache->setQuery(self::DESCRIPTIONS, $this->_descriptions);
            $this->_list_cache->save();
        }
        return $this->_descriptions[$folder];
    }

    /**
     * Returns the share parameters.
     *
     * @param string $folder The folder name.
     *
     * @return string The folder/share parameters.
     */
    public function getParameters($folder)
    {
        if (!isset($this->_parameters[$folder])) {
            $this->_parameters[$folder] = $this->_query->getParameters($folder);
            //@todo: This would only be long term data in case the IMAP is made private on the IMAP server
            $this->_list_cache->setLongTerm(self::PARAMETERS, $this->_parameters);
            $this->_list_cache->save();
        }
        return $this->_parameters[$folder];
    }

    /**
     * Returns the share description.
     *
     * @param string $folder      The folder name.
     * @param string $description The share description.
     *
     * @return string The folder/share description.
     */
    public function setDescription($folder, $description)
    {
        $this->_query->setDescription($folder, $description);
        $this->_descriptions[$folder] = $description;
        $this->_list_cache->setQuery(self::DESCRIPTIONS, $this->_descriptions);
        $this->_list_cache->save();
    }

    /**
     * Returns the share parameters.
     *
     * @param string $folder     The folder name.
     * @param array  $parameters The share parameters.
     *
     * @return string The folder/share parameters.
     */
    public function setParameters($folder, array $parameters)
    {
        $this->_query->setParameters($folder, $parameters);
        $this->_parameters[$folder] = $parameters;
        $this->_list_cache->setLongTerm(self::PARAMETERS, $this->_parameters);
        $this->_list_cache->save();
    }

    /**
     * Update the listener after creating a new folder.
     *
     * @param string $folder The path of the folder that has been created.
     * @param string $type   An optional type for the folder.
     *
     * @return NULL
     */
    public function updateAfterCreateFolder($folder, $type = null)
    {
    }

    /**
     * Update the listener after deleting folder.
     *
     * @param string $folder The path of the folder that has been deleted.
     *
     * @return NULL
     */
    public function updateAfterDeleteFolder($folder)
    {
        unset($this->_descriptions[$folder]);
        unset($this->_parameters[$folder]);
        $this->_list_cache->setQuery(self::DESCRIPTIONS, $this->_descriptions);
        $this->_list_cache->setLongTerm(self::PARAMETERS, $this->_parameters);
        $this->_list_cache->save();
    }

    /**
     * Update the listener after renaming a folder.
     *
     * @param string $old The old path of the folder.
     * @param string $new The new path of the folder.
     *
     * @return NULL
     */
    public function updateAfterRenameFolder($old, $new)
    {
        if (isset($this->_descriptions[$old])) {
            $this->_descriptions[$new] = $this->_descriptions[$old];
            unset($this->_descriptions[$old]);
            $this->_list_cache->setQuery(self::DESCRIPTIONS, $this->_descriptions);
        }
        if (isset($this->_parameters[$old])) {
            $this->_parameters[$new] = $this->_parameters[$old];
            unset($this->_parameters[$old]);
            $this->_list_cache->setLongTerm(self::PARAMETERS, $this->_parameters);
        }
        $this->_list_cache->save();
    }

    /**
     * Synchronize the ACL information with the information from the backend.
     *
     * @param array $params Additional parameters.
     *
     * @return NULL
     */
    public function synchronize($params = array())
    {
        $this->_descriptions = array();
    }
}