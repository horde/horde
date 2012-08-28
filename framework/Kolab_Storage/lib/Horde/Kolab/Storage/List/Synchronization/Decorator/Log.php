<?php
/**
 * Logs list synchronization requests.
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
 * Logs list synchronization requests.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_List_Synchronization_Decorator_Log
extends Horde_Kolab_Storage_List_Synchronization
{
    /**
     * Decorated synchronization handler.
     *
     * @var Horde_Kolab_Storage_List_Synchronization
     */
    private $_synchronization;

    /**
     * A log handler.
     *
     * @var mixed
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List_Synchronization $synchronization The synchronization handler.
     * @param mixed $logger The log handler. This instance must provide the debug() method.
     */
    public function __construct(Horde_Kolab_Storage_List_Synchronization $synchronization, $logger)
    {
        $this->_synchronization = $synchronization;
        $this->_logger = $logger;
    }

    /**
     * Inform all listeners about the synchronization call.
     */
    public function synchronize()
    {
        $result = $this->_synchronization->synchronize();
        $this->_logger->debug(
            sprintf('Synchronized the Kolab folder list!')
        );
    }

    /**
     * Register a new synchronization listener.
     *
     * @param Horde_Kolab_Storage_List_Synchronization_Listener $listener The new listener.
     */
    public function registerListener(Horde_Kolab_Storage_List_Synchronization_Listener $listener)
    {
        $this->_synchronization->registerListener($listener);
    }

}
