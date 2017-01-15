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
 * Exception thrown if connecting to the server failed.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2015-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   ManageSieve
 */
class ConnectionFailed extends ManageSieve\Exception
{
    /**
     * Exception constructor.
     *
     * @param Exception $e  An Exception object.
     */
    public function __construct(\Exception $e)
    {
        parent::__construct('Failed to connect, server said: ' . $e->getMessage());
    }
}
