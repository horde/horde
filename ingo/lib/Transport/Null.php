<?php
/**
 * Ingo_Transport_Null implements a null api -- useful for just testing
 * the UI and storage.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Brent J. Nordquist <bjn@horde.org>
 * @package Ingo
 */

class Ingo_Transport_Null extends Ingo_Transport
{
    /**
     * Constructor.
     */
    public function __construct($params = array())
    {
        $this->_support_shares = true;
        parent::__construct($params);
    }

}
