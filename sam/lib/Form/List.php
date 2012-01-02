<?php
/**
 * Form class for address list management.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Max Kalika <max@horde.org>
 * @package Sam
 */
class Sam_Form_List extends Horde_Form
{
    protected $_attributes = array();

    public function __construct($vars, $title)
    {
        parent::__construct($vars, $title);
        $this->setButtons(_("Save"), true);

        try {
            $sam_driver = $GLOBALS['injector']->getInstance('Sam_Driver');
        } catch (Sam_Exception $e) {
            return;
        }

        foreach ($this->_attributes as $key => $attribute) {
            if (!$sam_driver->hasCapability($key)) {
                continue;
            }

            $var = $this->addVariable($attribute, $key, 'longtext',
                                      false, false, null,
                                      array('5', '40'));
            $var->setHelp($key);

            if (!$vars->exists($key)) {
                $vars->set($key, $sam_driver->getListOption($key));
            }
        }

        if ($sam_driver->hasCapability('global_defaults') &&
            $GLOBALS['registry']->isAdmin()) {
            $this->addVariable('', '', 'spacer', false);
            $var = $this->addVariable(_("Make Settings Global"),
                                      'global_defaults', 'boolean', false);
            $var->setHelp('global_defaults');
        }
    }
}
