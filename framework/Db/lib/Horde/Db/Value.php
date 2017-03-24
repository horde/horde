<?php
/**
 * Copyright 2006-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd
 * @package  Db
 */

/**
 * Interface for values with specific quoting rules.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2006-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd
 * @package   Db
 */
interface Horde_Db_Value
{
    /**
     * @param Horde_Db_Adapter $db
     */
    public function quote(Horde_Db_Adapter $db);
}
