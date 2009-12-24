<?php
/**
 * QueryParameterForm Class
 *
 * $Horde: whups/lib/Forms/QueryParameterForm.php,v 1.4 2009/01/06 18:02:34 jan Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
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
