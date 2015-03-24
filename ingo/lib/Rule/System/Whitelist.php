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
 * The whitelist rule.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Rule_System_Whitelist
extends Ingo_Rule_Addresses
implements Ingo_Rule_System
{
    /**
     */
    protected $_perm = 'max_whitelist';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->name = _("Whitelist");
    }

    /**
     */
    protected function _setAddressesException($addr_count, $max)
    {
        return new Ingo_Exception(sprintf(
            _("Maximum number of whitelisted addresses exceeded (Total addresses: %d, Maximum addresses: %d). Could not add new addresses to whitelist."),
            $addr_count,
            $max
        ));
    }

}
