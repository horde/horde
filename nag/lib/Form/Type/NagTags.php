<?php
/**
 * The Horde_Form_Type_NagTags:: class provides a form field for autocompleting
 * tags.
 *
 * @copyright 2012 Horde LLC (http://www.horde.org)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package Nag
 */
class Nag_Form_Type_NagTags extends Horde_Form_Type
{
    public function getInfo(&$vars, &$var, &$info)
    {
        $info = $var->getValue($vars);
    }

    public function isValid(&$var, &$vars, $value, &$message)
    {
        return true;
    }

    public function getTypeName()
    {
        return 'NagTags';
    }

}