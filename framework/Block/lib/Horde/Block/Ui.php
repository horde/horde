<?php
/**
 * Class for setting up Horde Blocks using the Horde_Form:: classes.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @category Horde
 * @package  Horde_Block
 */
class Horde_Block_UI
{
    /**
     * TODO
     *
     * @var Horde_Form
     */
    protected $_form = null;

    /**
     * TODO
     *
     * @var Horde_Variables
     */
    protected $_vars = null;

    /**
     * TODO
     */
    public function setForm($form)
    {
        $this->_form = $form;
    }

    /**
     * TODO
     */
    public function setVars($vars)
    {
        $this->_vars = $vars;
    }

    /**
     * TODO
     */
    public function setupEditForm($field = 'block')
    {
        if (is_null($this->_vars)) {
            /* No existing vars set, get them now. */
            $this->setVars(Horde_Variables::getDefaultVariables());
        }

        if (!($this->_form instanceof Horde_Form)) {
            /* No existing valid form object set so set up a new one. */
            $this->setForm(new Horde_Form($this->_vars, Horde_Block_Translation::t("Edit Block")));
        }

        /* Get the current value of the block selection. */
        $value = $this->_vars->get($field);

        $blocks = $GLOBALS['injector']->getInstance('Horde_Core_Factory_BlockCollection')->create();

        /* Field to select apps. */
        $apps = $blocks->getBlocksList();
        $v = $this->_form->addVariable(Horde_Block_Translation::t("Application"), $field . '[app]', 'enum', true, false, null, array($apps));
        $v->setOption('trackchange', true);

        if (empty($value['app'])) {
            return;
        }

        /* If a block has been selected, output any options input. */
        list($app, $block) = explode(':', $value['app']);

        /* Get the options for the requested block. */
        $options = $blocks->getParams($app, $block);

        /* Go through the options for this block and set up any required
         * extra input. */
        foreach ($options as $option) {
            $name = $blocks->getParamName($app, $block, $option);
            $type = $blocks->getOptionType($app, $block, $option);
            $required = $blocks->getOptionRequired($app, $block, $option);
            $values = $blocks->getOptionValues($app, $block, $option);
            /* TODO: the setting 'string' should be changed in all blocks
             * to 'text' so that it conforms with Horde_Form syntax. */
            if ($type == 'string') {
                $type = 'text';
            }
            $params = array();
            if ($type == 'enum' || $type == 'mlenum') {
                $params = array($values, true);
            }
            $this->_form->addVariable($name, $field . '[options][' . $option . ']', $type, $required, false, null, $params);
        }
    }

}
