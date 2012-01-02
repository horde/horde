<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */
class Whups_Form_DeleteListener extends Horde_Form
{
    public function __construct(&$vars, $title = '')
    {
        parent::__construct($vars, $title);

        $this->addHidden('', 'id', 'int', true, true);
        $this->addVariable(_("Email address to remove"), 'del_listener', 'email', true);
    }

}
