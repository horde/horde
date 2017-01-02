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
 * Storage iterator filter that returns objects that do not match the filter.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Storage_FilterIterator_Skip
extends Ingo_Storage_FilterIterator
{
    /**
     */
    public function accept()
    {
        $ob = $this->current();

        foreach ($this->_filters as $val) {
            if ($ob instanceof $val) {
                return false;
            }
        }

        return true;
    }

}
