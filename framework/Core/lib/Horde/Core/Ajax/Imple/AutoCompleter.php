<?php
/**
 * Attach an auto completer to a HTML element.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
abstract class Horde_Core_Ajax_Imple_AutoCompleter extends Horde_Core_Ajax_Imple
{
    /**
     * Since this is shared code, we need to keep global init status here.
     *
     * @var boolean
     */
    static protected $_initAc = false;

    /**
     */
    protected function _attach($init)
    {
        global $page_output;

        if (!self::$_initAc) {
            $page_output->addScriptFile('autocomplete.js', 'horde');
            $page_output->addScriptFile('liquidmetal.js', 'horde');
            $page_output->addScriptPackage('Keynavlist');

            $page_output->addInlineJsVars(array(
                'HordeImple.AutoCompleter' => new stdClass
            ));

            self::$_initAc = true;
        }

        $page_output->addInlineScript(array(
            'HordeImple.AutoCompleter["' . $this->getDomId() . '"]=' . $this->_getAutoCompleter()->generate($this)
        ), true);

        return false;
    }

    /**
     */
    protected function _handle(Horde_Variables $vars)
    {
        // Avoid errors if 'input' isn't set and short-circuit empty searches.
        if (!isset($vars->input)) {
            $result = array();
        } else {
            $input = $vars->get($vars->input);
            $result = strlen($input)
                ? $this->_handleAutoCompleter($input)
                : array();
        }

        return new Horde_Core_Ajax_Response_Prototypejs($result);
    }

    /**
     * Get the autocompleter object to use on the browser.
     *
     * @return Horde_Core_Ajax_Imple_AutoCompleter_Base  The autocompleter
     *                                                   object to use.
     */
    abstract protected function _getAutoCompleter();

    /**
     * Do the auto-completion on the server.
     *
     * @param string $input  Input received from the browser.
     *
     * @return mixed  Raw data to return to the javascript code.
     */
    abstract protected function _handleAutoCompleter($input);

}
