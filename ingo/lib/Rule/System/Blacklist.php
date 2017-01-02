<?php
/**
 * Copyright 2015-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2015-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * The blacklist rule.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 *
 * @property string $mailbox  The blacklist mailbox.
 */
class Ingo_Rule_System_Blacklist
extends Ingo_Rule_Addresses
implements Ingo_Rule_System
{
    /**
     * String that can't be a valid folder name used to mark blacklisted email
     * as deleted.
     */
    const DELETE_MARKER = '++DELETE++';

    /**
     * Blacklist mailbox.
     *
     * @var string
     */
    protected $_mbox = '';

    /**
     */
    protected $_perm = 'max_blacklist';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->name = _("Blacklist");
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'mailbox':
            return $this->_mbox;

        default:
            return parent::__get($name);
        }
    }

    /**
     */
    public function __set($name, $data)
    {
        switch ($name) {
        case 'mailbox':
            $this->_mbox = $data;
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
            _("Maximum number of blacklisted addresses exceeded (Total addresses: %d, Maximum addresses: %d). Could not add new addresses to blacklist."),
            $addr_count,
            $max
        ));
    }

}
