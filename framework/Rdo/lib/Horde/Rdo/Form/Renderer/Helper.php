<?php
/**
 * Renderer for the advanced search
 */
class Horde_Form_Renderer_Form_Helper extends Horde_Form_Renderer
{
    function _renderVarInputBegin($form, $var, $vars)
    {
        if ($var->description != 'cases' && $var->varName != 'horde_helper_add') {
           return;
        }

        parent::_renderVarInputBegin($form, $var, $vars);
    }

    function _renderVarInputEnd($form, $var, $vars)
    {
        if ($var->description == 'cases' && $var->varName != 'horde_helper_add') {
           echo ' ';
           return;
        }

        parent::_renderVarInputEnd($form, $var, $vars);
    }

}
