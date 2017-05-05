<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Backup
 * @subpackage UnitTests
 */

namespace Horde\Backup\Stub;

use ArrayIterator;
use Horde\Backup;

/**
 * \Horde\Backup\Collection stub.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Backup
 * @subpackage UnitTests
 */
class Collection extends Backup\Collection
{
    protected $_type;

    public function __construct(array $data, $user, $type)
    {
        parent::__construct(new ArrayIterator($data), $user);
        $this->_type = $type;
    }

    public function getType()
    {
        return $this->_type;
    }
}
