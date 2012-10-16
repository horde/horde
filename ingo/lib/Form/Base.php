<?php
/**
 * The base class for all Ingo rule forms.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Form_Base extends Horde_Form
{
    /**
     * Sets the form buttons.
     *
     * @param boolean $disabled  Whether the rule is currently disabled.
     */
    public function setCustomButtons($disabled)
    {
        $this->setButtons(_("Save"));
        if ($disabled) {
            $this->appendButtons(_("Save and Enable"));
        } else {
            $this->appendButtons(_("Save and Disable"));
        }
        $this->appendButtons(_("Return to Rules List"));
    }
}
