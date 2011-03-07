<?php
/**
 * The Horde_Form_VarRenderer:: class provides base functionality for
 * other Horde_Form elements.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 * Copyright 2005-2007 Matt Warden <mwarden@gmail.com>
 *
 * See the enclosed file LICENSE for license information (LGPL).
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Form
 */
class Horde_Form_VarRenderer {

    /**
     * Renders a variable.
     *
     * @param Horde_Form $form            Reference to a Horde_Form instance,
     *                                    or null if none is available.
     * @param Horde_Form_Variable $var    Reference to a Horde_Form_Variable.
     * @param Variables $vars             A Variables instance.
     * @param boolean $isInput            Whether this is an input field.
     */
    public function render($form, $var, $vars, $isInput = false)
    {
        if ($isInput) {
            $state = 'Input';
        } else {
            $state = 'Display';
        }
        $method = "_renderVar${state}_" . str_replace('Horde_Form_Type_', '', get_class($var->type));
        if (!method_exists($this, $method)) {
            $method = "_renderVar${state}Default";
        }
        return $this->$method($form, $var, $vars);
    }

    /**
     * Finishes rendering after all fields are output.
     */
    public function renderEnd()
    {
        return '';
    }

}
