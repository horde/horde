<?php
/**
 * Handles a share parameters.
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
 * Handles a share parameters.
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
class Horde_Kolab_Storage_List_Query_Share_Base
extends Horde_Kolab_Storage_List_Query_Share
{
    /** The folder description */
    const ANNOTATION_DESCRIPTION = '/shared/comment';

    /** The share parameters */
    /** @todo Shouldn't this be private data? */
    const ANNOTATION_SHARE_PARAMETERS = '/shared/vendor/horde/share-params';

    /**
     * The driver for accessing the Kolab storage system.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Driver $driver The driver to access the backend.
     */
    public function __construct(Horde_Kolab_Storage_Driver $driver)
    {
        $this->_driver = $driver;
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
        return $this->_driver->getAnnotation(
            $folder, self::ANNOTATION_DESCRIPTION
        );
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
        $parameters = $this->_driver->getAnnotation(
                $folder, self::ANNOTATION_SHARE_PARAMETERS
        );
        if (!empty($parameters)) {
            return unserialize(base64_decode($parameters));
        } else {
            return array();
        }
    }

    /**
     * Set the share description.
     *
     * @param string $folder      The folder name.
     * @param string $description The share description.
     *
     * @return NULL
     */
    public function setDescription($folder, $description)
    {
        $this->_driver->setAnnotation(
            $folder, self::ANNOTATION_DESCRIPTION, $description
        );
    }

    /**
     * Set the share parameters.
     *
     * @param string $folder     The folder name.
     * @param array  $parameters The share parameters.
     *
     * @return string The encoded share parameters.
     */
    public function setParameters($folder, array $parameters)
    {
        $this->_driver->setAnnotation(
            $folder,
            self::ANNOTATION_SHARE_PARAMETERS,
            base64_encode(serialize($parameters))
        );
    }
}