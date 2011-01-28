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
class QueryParameterForm extends Horde_Form {

    var $_useFormToken = false;

    function QueryParameterForm($query, $vars)
    {
        parent::Horde_Form($vars, _("Query Parameters"), 'queryparameters');
        foreach ($query->parameters as $name) {
            $this->addVariable($name, $name, 'text', true);
        }
    }

}
