<?php
/**
 * Configured event status to free/busy status mapper.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Configured event status to free/busy status mapper.
 *
 * Copyright 2011 Kolab Systems AG
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap_Config
extends Horde_Kolab_FreeBusy_Freebusy_Helper_StatusMap_Default
{
    /**
     * The status mapping.
     *
     * @var array
     */
    private $_map;

    /**
     * Constructor.
     *
     * @param array $map The status mapping.
     */
    public function __construct($map = array())
    {
        $this->_map = $map;
    }

    /**
     * Map the event status to a free/busy status.
     *
     * @param string $status The event status.
     *
     * @return string The corresponding free/busy status.
     */
    public function map($status)
    {
        if (isset($this->_map[$status])) {
            return $this->_map[$status];
        } else {
            return parent::map($status);
        }
    }
  
}