<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Whups
 */
class Whups_View_SavedQueries extends Whups_View_Base
{
    // Need title, results in params.
    public function html($header = true)
    {
        if (!count($this->_params['results'])) {
            return;
        }

        include WHUPS_TEMPLATES . '/view/savedqueries.inc';
    }
}
