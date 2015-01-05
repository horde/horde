<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Whups
 */

/**
 * Form to confirm attribute deletions.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Whups
 */
class Whups_Form_Admin_DeleteAttribute extends Horde_Form
{
    public function __construct($vars)
    {
        parent::__construct($vars, _("Delete Attribute Confirmation"));

        $attribute = $vars->get('attribute');
        $info = $GLOBALS['whups_driver']->getAttributeDesc($attribute);

        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'attribute', 'int', true, true);
        $pname = $this->addVariable(
            _("Attribute Name"), 'attribute_name', 'text', false, true);
        $pname->setDefault($info['name']);
        $pdesc = $this->addVariable(
            _("Attribute Description"), 'attribute_description', 'text', false,
            true);
        $pdesc->setDefault($info['description']);
        $this->addVariable(
            _("Really delete this attribute? This may cause data problems!"),
            'yesno', 'enum', true, false, null,
            array(array(0 => _("No"), 1 => _("Yes"))));

        $this->setButtons(array(array('class' => 'horde-delete', 'value' => _("Delete Attribute"))));
    }
}
