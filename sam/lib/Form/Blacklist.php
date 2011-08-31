<?php
/**
 * Form class for blacklist management.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Max Kalika <max@horde.org>
 * @package Sam
 */
class Sam_Form_Blacklist extends Sam_Form_List
{
    protected $_attributes = array(
        'blacklist_from' => _("Blacklist From"),
        'blacklist_to' => _("Blacklist To")
    );

    public function __construct($vars)
    {
        parent::__construct($vars, _("Blacklist Manager"));
    }
}
