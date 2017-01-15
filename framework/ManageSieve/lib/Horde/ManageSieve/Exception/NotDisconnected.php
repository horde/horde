<?php
/**
 * Copyright 2015-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   ManageSieve
 */

namespace Horde\ManageSieve\Exception;
use Horde\ManageSieve;

/**
 * Exception thrown if the server should be disconnected but isn't.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2015-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   ManageSieve
 */
class NotDisconnected extends ManageSieve\Exception
{
    /**
     * Exception constructor.
     *
     * @param mixed $message  The exception message, or an Exception object.
     */
    public function __construct($message = 'Not currently in DISCONNECTED state')
    {
        parent::__construct($message);
    }
}
