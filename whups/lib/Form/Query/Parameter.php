<?php
/**
 * QueryParameterForm Class
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @package Whups
 */
class Whups_Form_Query_Parameter extends Horde_Form
{
    protected $_useFormToken = false;

    public function __construct($query, $vars)
    {
        parent::__construct($vars, _("Query Parameters"), 'Whups_Form_Query_Parameter');
        foreach ($query->parameters as $name) {
            $this->addVariable($name, $name, 'text', true);
        }
    }

}
