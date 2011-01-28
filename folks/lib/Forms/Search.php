<?php
/**
 * $Id: Search.php 1247 2009-01-30 15:01:34Z duck $
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Search_Form extends Horde_Form {

    function __construct($vars, $title = '', $name = null)
    {
        parent::__construct($vars, $title, $name);

        $this->addVariable(_("Word"), 'word', 'text', false);
        $this->addVariable(_("Search by"), 'by', 'set', false, false, null, array(array('uid' => _("Name"), 'city' => _("City"), 'description' => _("Description")), true));
        $this->addVariable(_("Gender"), 'user_gender', 'radio', false, false, null, array(array(1 => _("Male"), 2 => _("Female")), true));
        $this->addVariable(_("City"), 'user_city', 'text', false);
        $this->addVariable(_("Age from"), 'age_from', 'number', false);
        $this->addVariable(_("Age to"), 'age_to', 'number', false);
        $this->addVariable(_("Mast have"), 'has', 'set', false , false, null, array(array('picture' => _("Picture"), 'videos' => _("Video"))));
        $this->addVariable(_("Is online"), 'online', 'boolean', false);
        $this->setButtons(array(_("Search")));
    }

    /**
     * Fetch the field values of the submitted form.
     *
     * @param Variables $vars  A Variables instance, optional since Horde 3.2.
     * @param array $info      Array to be filled with the submitted field
     *                         values.
     */
    function getInfo($vars, &$info)
    {
        $this->_getInfoFromVariables($this->getVariables(), $this->_vars, $info);
    }

    /**
     * Fetch the field values from a given array of variables.
     *
     * @access private
     *
     * @param array  $variables  An array of Horde_Form_Variable objects to
     *                           fetch from.
     * @param object $vars       The Variables object.
     * @param array  $info       The array to be filled with the submitted
     *                           field values.
     */
    function _getInfoFromVariables($variables, &$vars, &$info)
    {
        foreach ($variables as $var) {
            $value = $var->getValue($vars);
            if (empty($value)) {
                continue;
            }

            require_once 'Horde/Array.php';
            if (Horde_Array::getArrayParts($var->getVarName(), $base, $keys)) {
                if (!isset($info[$base])) {
                    $info[$base] = array();
                }
                $pointer = &$info[$base];
                while (count($keys)) {
                    $key = array_shift($keys);
                    if (!isset($pointer[$key])) {
                        $pointer[$key] = array();
                    }
                    $pointer = &$pointer[$key];
                }
                $var->getInfo($vars, $pointer);
            } else {
                $var->getInfo($vars, $info[$var->getVarName()]);
            }

        }
    }
}