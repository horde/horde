<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * The base class for all Ingo rule forms.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Form_Base extends Horde_Form
{
    /**
     * List of the supported form fields.  If empty, all features are supported.
     *
     * @var array
     */
    protected $_features;

    public function __construct($vars, $title = '', $name = null, $features = array())
    {
        parent::__construct($vars, $title, $name);
        $this->_features = $features;
    }

    public function hasFeature($what)
    {
        // either we support the feature or (if _features is empty) we support all
        return in_array($what, $this->_features) || empty($this->_features);
    }

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
