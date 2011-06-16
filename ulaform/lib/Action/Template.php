<?php
/**
 * Ulaform_Action_Template Class provides a Ulaform action driver to complete
 * a template provided by another Horde application replacing template data with
 * form informatino.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Ulaform
 */
class Ulaform_Action_Template extends Ulaform_Action {

    /**
     * Actually carry out the action.
     */
    function doAction($form_params, $form_data, $fields)
    {
        throw new Horde_Exception(_("Not Implemented."));
    }

    /**
     * Identifies this action driver and returns a brief description, used by
     * admin when configuring an action for a form and set up using Horde_Form.
     *
     * @return array  Array of required parameters.
     */
    function getInfo()
    {
        $info['name'] = _("Horde Template");
        $info['desc'] = _("This driver fills out a template provided by any of the available applications using its template.");

        return $info;
    }

    /**
     * Returns the required parameters for this action driver, used by admin
     * when configuring an action for a form and set up using Horde_Form.
     *
     * @return array  Array of required parameters.
     */
    function getParams()
    {
        global $registry;

        // Form parameters
        $params = array();

        $templates = array();
        foreach ($registry->listApps() as $app) {
            if (!$registry->hasMethod('listTemplates', $app)) {
                continue;
            }

            $result = $registry->callByPackage($app, 'listTemplates');
            if (is_a($result, 'PEAR_Error')) {
                global $notification;
                $notification->push(sprintf(_("Error retrieving templates from \"%s\": %s"), $registry->get('name', $app), $result->getMessage()), 'horde.error');
                continue;
            }

            foreach (array_keys($result) as $catkey) {
                foreach (array_keys($result[$catkey]['templates']) as $tkey){
                    $result[$catkey]['templates'][$tkey]['id'] = $app . ':' .
                        $result[$catkey]['templates'][$tkey]['id'];
                }
            }

            if ($app == $registry->getApp()) {
                $templates = array_merge($result, $templates);
            } else {
                $templates = array_merge($templates, $result);
            }
        }

        $tmplList = array('' => _("--- Choose a Template ---"));
        $counter = 0;
        foreach ($templates as $category) {
            Horde_Array::arraySort($category['templates'], 'name');
            $tmplList['category%' . $counter++] = sprintf('--- %s ---', $category['category']);
            foreach ($category['templates'] as $template) {
                $name = $template['name'];
                if (Horde_String::length($name) > 80) {
                    $name = Horde_String::substr($name, 0, 76) . ' ...';
                }
                $tmplList[$template['id']] = $name;
            }
        }
        $params['template'] = array('type' => 'enum',
                                    'label' => _("Template"),
                                    'params' => array($tmplList));

        return $params;
    }
}
