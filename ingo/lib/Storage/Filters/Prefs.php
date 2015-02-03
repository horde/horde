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
 * Horde_Prefs storage of the user-defined filter list.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Storage_Filters_Prefs extends Ingo_Storage_Filters
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        global $prefs;

        if ($rules = @unserialize($prefs->getValue('rules'))) {
            $this->_filters = $rules;
        }
    }

}
