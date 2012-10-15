<?php
/**
 * The cached variant for the Kolab storage handler [the default].
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
 * The cached variant for the Kolab storage handler [the default].
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Cached
extends Horde_Kolab_Storage_Base
{
    /**
     * Return a data handler for accessing data in the specified
     * folder.
     *
     * @param mixed                       $folder       The name of the folder or
     *                                                  an instance representing
     *                                                  the folder.
     * @param Horde_Kolab_Storage_Driver  $master       The primary connection
     *                                                  driver.
     * @param Horde_Kolab_Storage_Factory $factory      The factory.
     * @param string                      $object_type  The type of data we want
     *                                                  to access in the folder.
     * @param int                         $data_version Format version of the
     *                                                  object data.
     *
     * @return Horde_Kolab_Data The data object.
     */
    protected function _createData($folder,
                                   Horde_Kolab_Storage_Driver $master,
                                   Horde_Kolab_Storage_Factory $factory,
                                   $object_type = null,
                                   $data_version = 1)
    {
        return new Horde_Kolab_Storage_Data_Cached(
            $folder,
            $master,
            $factory,
            $this->_cache,
            $object_type,
            $data_version
        );
    }
}