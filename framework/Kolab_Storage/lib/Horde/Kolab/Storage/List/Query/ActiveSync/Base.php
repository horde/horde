<?php
/**
 * Handles a active sync parameters.
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
 * Handles a active sync parameters.
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
class Horde_Kolab_Storage_List_Query_ActiveSync_Base
extends Horde_Kolab_Storage_List_Query_ActiveSync
{
    /** The active sync parameters */
    /** @todo Shouldn't this be private data? */
    const ANNOTATION_ACTIVE_SYNC = '/priv/vendor/kolab/activesync';

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
     * Returns the active sync settings.
     *
     * @param string $folder The folder name.
     *
     * @return array The folder active sync parameters.
     */
    public function getActiveSync($folder)
    {
        return json_decode(
            base64_decode(
                $this->_driver->getAnnotation(
                    $folder, self::ANNOTATION_ACTIVE_SYNC
                )
            ),
            true
        );
    }

    /**
     * Set the active sync settings.
     *
     * @param string $folder The folder name.
     * @param array  $data   The active sync settings.
     *
     * @return string The encoded share parameters.
     */
    public function setActiveSync($folder, array $data)
    {
        $this->_driver->setAnnotation(
            $folder,
            self::ANNOTATION_ACTIVE_SYNC,
            base64_encode(json_encode($data))
        );
    }
}