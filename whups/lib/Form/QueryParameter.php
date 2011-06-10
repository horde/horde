<?php
/**
 * QueryParameterForm Class
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @package Whups
 */
class Whups_Form_QueryParameter extends Horde_Form
{
    protected $_useFormToken = false;

    public function __construct($query, $vars)
    {
        parent::__construct($vars, _("Query Parameters"), 'queryparameters');
        foreach ($query->parameters as $name) {
            $this->addVariable($name, $name, 'text', true);
        }
    }

}
