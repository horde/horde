<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Robert E. Coyle <robertecoyle@hotmail.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Whups
 */

/**
 * No idea what this form does.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Robert E. Coyle <robertecoyle@hotmail.com>
 * @category  Horde
 * @copyright 2001-2002 Robert E. Coyle
 * @copyright 2001-2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Whups
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
