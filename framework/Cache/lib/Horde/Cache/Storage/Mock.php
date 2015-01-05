<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Cache
 * @package   Cache
 */

/**
 * Cache storage in PHP memory.
 * It persists only during a script run and ignores the object lifetime
 * because of that.
 *
 * @author    Gunnar Wrobel <wrobel@pardus.de>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @deprecated Use Memory driver instead.
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Cache
 * @package   Cache
 */
class Horde_Cache_Storage_Mock extends Horde_Cache_Storage_Memory
{
}
