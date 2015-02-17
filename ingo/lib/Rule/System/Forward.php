<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * The blacklist rule.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 *
 * @property boolean $keep  Keep messages that have been forwarded?
 */
class Ingo_Rule_System_Forward
extends Ingo_Rule_Addresses
implements Ingo_Rule_System
{
    /**
     * Keep messages that have been forwarded?
     *
     * @var boolean
     */
    protected $_keep = true;

    /**
     */
    protected $_perm = 'max_forward';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->name = _("Forward");
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'keep':
            return $this->_keep;

        default:
            return parent::__get($name);
        }
    }

    /**
     */
    public function __set($name, $data)
    {
        switch ($name) {
        case 'keep':
            $this->_keep = (bool)$data;
            break;

        default:
            parent::__set($name, $data);
            break;
        }
    }

    /**
     */
    protected function _setAddressesException($addr_count, $max)
    {
        return new Ingo_Exception(sprintf(
            _("Maximum number of forward addresses exceeded (Total addresses: %s, Maximum addresses: %s)."),
            $addr_count,
            $max
        ));
    }

}
