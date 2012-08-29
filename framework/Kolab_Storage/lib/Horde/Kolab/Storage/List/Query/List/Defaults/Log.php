<?php
/**
 * Protects against more than one default folder per type by logging an error.
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
 * Protects against more than one default folder per type by logging an error.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_List_Query_List_Defaults_Log
extends Horde_Kolab_Storage_List_Query_List_Defaults
{
    /**
     * The logger.
     *
     * @var Horde_Log_Logger
     */
    private $_logger;

    /**
     * Constructor
     *
     * @param Horde_Log_Logger $logger The logger. Must provide an err() method.
     */
    public function __construct($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * React on detection of more than one default folder.
     *
     * @param string  $first  The first default folder name.
     * @param string  $second The second default folder name.
     * @param string  $type   The folder type.
     * @param string  $owner  The folder owner.
     */
    protected function doubleDefault($first, $second, $owner, $type)
    {
        $this->_logger->err(
            sprintf(
                'Both folders "%s" and "%s" of owner "%s" are marked as default folder of type "%s"!',
                $first,
                $second,
                $owner,
                $type
            )
        );
    }
}