<?php
/**
 * Transmits a synchronization signal to all listeners caching information from
 * a Horde_Kolab_Storage_List.
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
 * Transmits a synchronization signal to all listeners caching information from
 * a Horde_Kolab_Storage_List.
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
class Horde_Kolab_Storage_List_Synchronization_Base
extends Horde_Kolab_Storage_List_Synchronization
{
    /**
     * The list of registered listeners.
     *
     * @var Horde_Kolab_Storage_List_Synchronization_Listener[]
     */
    private $_listeners = array();

    /**
     * Register a new synchronization listener.
     *
     * @param Horde_Kolab_Storage_List_Synchronization_Listener $listener The new listener.
     */
    public function registerListener(Horde_Kolab_Storage_List_Synchronization_Listener $listener)
    {
        $this->_listeners[] = $listener;
    }

    /**
     * Inform all listeners about the synchronization call.
     */
    public function synchronize()
    {
        foreach ($this->_listeners as $listener) {
            $listener->synchronize();
        }
    }
}
