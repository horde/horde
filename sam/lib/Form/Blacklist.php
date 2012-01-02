<?php
/**
 * Form class for blacklist management.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Max Kalika <max@horde.org>
 * @package Sam
 */
class Sam_Form_Blacklist extends Sam_Form_List
{
    public function __construct($vars)
    {
        $this->_attributes = array(
            'blacklist_from' => _("Blacklist From"),
            'blacklist_to' => _("Blacklist To")
        );
        parent::__construct($vars, _("Blacklist Manager"));
    }
}
