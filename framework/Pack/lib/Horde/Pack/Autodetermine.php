<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pack
 */

/**
 * Auto-determine whether data contains PHP objects (an object that must be
 * serialized to be preserved).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pack
 *
 * @property-read boolean $phpob  True if data contains at least one PHP
 *                                object.
 */
class Horde_Pack_Autodetermine
{
    /**
     * Cached results.
     *
     * @var boolean
     */
    protected $_result;

    /**
     * Constructor.
     *
     * @param mixed $data   Data to scan.
     */
    public function __construct($data)
    {
        $this->_result = $this->_scanData($data);
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'phpob':
            return $this->_result;
        }
    }

    /**
     */
    protected function _scanData($data)
    {
        if (is_object($data)) {
            return ($data instanceof stdClass)
                ? $this->_scanData((array)$data)
                : true;
        }

        if (is_array($data)) {
            foreach ($data as $val) {
                if ($this->_scanData($val)) {
                    return true;
                }
            }
        }

        return false;
    }

}
