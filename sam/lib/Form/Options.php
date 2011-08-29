<?php
/**
 * Form Class for SpamAssassin Options Management.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Max Kalika <max@horde.org>
 * @package Sam
 */
class Sam_Form_Options extends Horde_Form
{
    public function __construct(&$vars)
    {
        global $sam_driver;

        parent::__construct($vars, _("Spam Options"));

        $this->setButtons(_("Save"), true);

        foreach (Sam::getAttributes() as $key => $attribute) {
            if (Sam::infoAttribute($attribute['type']) ||
                $sam_driver->hasCapability($key)) {
                $var = &$this->addVariable($attribute['label'],
                                           $key, $attribute['type'],
                                           !empty($attribute['required']),
                                           !empty($attribute['readonly']),
                                           isset($attribute['description'])
                                               ? $attribute['description']
                                               : null,
                                           isset($attribute['params'])
                                               ? $attribute['params']
                                               : array());

                $var->setHelp($key);
                if (isset($attribute['default'])) {
                    $var->setDefault($attribute['default']);
                }

                if (!$vars->exists($key)) {
                    if (isset($attribute['basepref'])) {
                        /* If basepref is set, key is one of multiple multiple
                         * possible entries for basepref.
                         * Get all basepref entries from backend. */
                        $value = $sam_driver->getListOption($attribute['basepref']);

                        /* Split entries into individual elements */
                        $elements = preg_split('/\n/', $value, -1,
                                               PREG_SPLIT_NO_EMPTY);

                        foreach ($elements as $element) {
                            /* Split element into subtype and data
                             * e.g. 'Subject' and '***SPAM***' */
                            $pref = explode(' ', $element);

                            /* Find right subtype entry for this key */
                            if (isset($pref[0]) &&
                                $pref[0] == $attribute['subtype']) {
                                if (isset($pref[1])) {
                                    /* Set value for key to just the data */
                                    $vars->set($key, $pref[1]);
                                } else {
                                    $vars->set($key, '');
                                }
                                break;
                            }
                        }
                    } else {
                        $value = $sam_driver->getOption($key);
                        if (!is_null($value)) {
                            if ($attribute['type'] == 'boolean') {
                                $boolean = $sam_driver->optionToBoolean($value);
                                $vars->set($key, $boolean);
                            } else {
                                $vars->set($key, $value);
                            }
                        }
                    }
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
