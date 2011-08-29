<?php
/**
 * Form Class for Blacklist Management.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Max Kalika <max@horde.org>
 * @package Sam
 */
class BlacklistForm extends Horde_Form {

    function BlacklistForm(&$vars)
    {
        global $sam_driver;

        parent::Horde_Form($vars, _("Blacklist Manager"));

        $this->setButtons(_("Save"), true);

        $attributes = array(
            'blacklist_from' => _("Blacklist From"),
            'blacklist_to' => _("Blacklist To"),
        );

        foreach ($attributes as $key => $attribute) {
            if ($sam_driver->hasCapability($key)) {
                $var = &$this->addVariable($attribute, $key, 'longtext',
                                           false, false, null,
                                           array('5', '40'));
                $var->setHelp($key);

                if (!$vars->exists($key)) {
                    $vars->set($key, $sam_driver->getListOption($key));
                }
            }
        }

        if ($sam_driver->hasCapability('global_defaults') && Horde_Auth::isAdmin()) {
            $this->addVariable('', '', 'spacer', false);
            $var = &$this->addVariable(_("Make Settings Global"),
                                       'global_defaults', 'boolean', false);
            $var->setHelp('global_defaults');
        }
    }

}
