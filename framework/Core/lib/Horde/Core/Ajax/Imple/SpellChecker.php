<?php
/**
 * Attach the spellchecker to a javascript element.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Ajax_Imple_SpellChecker extends Horde_Core_Ajax_Imple
{
    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     * <pre>
     * 'id' => TODO (optional)
     * 'locales' => TODO (optional)
     * 'states' => TODO (optional)
     * 'targetId' => TODO (optional)
     * 'triggerId' => TODO (optional)
     * </pre>
     */
    public function __construct($params = array())
    {
        if (empty($params['id'])) {
            $params['id'] = $this->_randomid();
        }

        if (empty($params['targetId'])) {
            $params['targetId'] = $this->_randomid();
        }

        if (empty($params['triggerId'])) {
            $params['triggerId'] = $params['targetId'] . '_trigger';
        }

        if (empty($params['locales'])) {
            $key_list = array_keys($GLOBALS['registry']->nlsconfig['spelling']);
            asort($key_list, SORT_LOCALE_STRING);
            $params['locales'] = array();

            foreach ($key_list as $lcode) {
                $params['locales'][] = array('l' => $GLOBALS['registry']->nlsconfig['languages'][$lcode], 'v' => $lcode);
            }
        }

        parent::__construct($params);
    }

    /**
     */
    public function attach()
    {
        Horde::addScriptFile('prototype.js', 'horde');
        Horde::addScriptFile('effects.js', 'horde');
        Horde::addScriptFile('keynavlist.js', 'horde');
        Horde::addScriptFile('spellchecker.js', 'horde');

        $opts = array(
            'locales' => $this->_params['locales'],
            'sc' => 'widget',
            'statusButton' => $this->_params['triggerId'],
            'target' => $this->_params['targetId'],
            'url' => strval($this->_getUrl('SpellChecker', 'horde', array('input' => $this->_params['targetId'])))
        );
        if (isset($this->_params['states'])) {
            $opts['bs'] = $this->_params['states'];
        }

        Horde::addInlineScript(array(
            $this->_params['id'] . ' = new SpellChecker(' . Horde_Serialize::serialize($opts, Horde_Serialize::JSON, $GLOBALS['registry']->getCharset()) . ')'
        ), 'dom');
    }

    /**
     */
    public function handle($args, $post)
    {
        $spellArgs = array();

        if (!empty($GLOBALS['conf']['spell']['params'])) {
            $spellArgs = $GLOBALS['conf']['spell']['params'];
        }

        if (isset($args['locale'])) {
            $spellArgs['locale'] = $args['locale'];
        } elseif (isset($GLOBALS['language'])) {
            $spellArgs['locale'] = $GLOBALS['language'];
        }

        /* Add local dictionary words. */
        try {
            $result = Horde::loadConfiguration('spelling.php', 'ignore_list', 'horde');
            $spellArgs['localDict'] = $result;
        } catch (Horde_Exception $e) {}

        if (!empty($args['html'])) {
            $spellArgs['html'] = true;
        }

        try {
            $speller = Horde_SpellChecker::factory($GLOBALS['conf']['spell']['driver'], $spellArgs);
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return array();
        }

        try {
            return $speller->spellCheck(Horde_Util::getPost($args['input']));
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return array('bad' => array(), 'suggestions' => array());
        }
    }

}
