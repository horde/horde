<?php
/**
 * Class for setting up Horde Blocks using the Horde_Form:: classes.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
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
     * @var array
     */
    protected $_blocks = array();

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
     * Constructor.
     */
    public function __construct()
    {
        $this->_blocks = Horde_Block_Collection::singleton();
    }

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

        /* Field to select apps. */
        $apps = $this->_blocks->getBlocksList();
        $v = $this->_form->addVariable(Horde_Block_Translation::t("Application"), $field . '[app]', 'enum', true, false, null, array($apps));
        $v->setOption('trackchange', true);

        if (empty($value['app'])) {
            return;
        }

        /* If a block has been selected, output any options input. */
        list($app, $block) = explode(':', $value['app']);

        /* Get the options for the requested block. */
        $options = $this->_blocks->getParams($app, $block);

        /* Go through the options for this block and set up any required
         * extra input. */
        foreach ($options as $option) {
            $name = $this->_blocks->getParamName($app, $block, $option);
            $type = $this->_blocks->getOptionType($app, $block, $option);
            $required = $this->_blocks->getOptionRequired($app, $block, $option);
            $values = $this->_blocks->getOptionValues($app, $block, $option);
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
