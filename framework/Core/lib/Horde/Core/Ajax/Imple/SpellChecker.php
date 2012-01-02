<?php
/**
 * Attach the spellchecker to a javascript element.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Ajax_Imple_SpellChecker extends Horde_Core_Ajax_Imple
{
    /**
     * Constructor.
     *
     * @param array $params  OPTIONAL configuration parameters:
     *   - id: (string) DOM ID string.
     *   - locales: (array) List of supported locales.
     *   - states: (array) TODO
     *   - targetId: (string) TODO
     *   - triggerId: (string) TODO
     */
    public function __construct(array $params = array())
    {
        global $registry;

        if (!isset($params['id'])) {
            $params['id'] = $this->_randomid();
        }

        if (!isset($params['targetId'])) {
            $params['targetId'] = $this->_randomid();
        }

        if (!isset($params['triggerId'])) {
            $params['triggerId'] = $params['targetId'] . '_trigger';
        }

        if (empty($params['locales'])) {
            $key_list = array_keys($registry->nlsconfig->spelling);
            asort($key_list, SORT_LOCALE_STRING);
            $params['locales'] = array();

            foreach ($key_list as $lcode) {
                $params['locales'][] = array(
                    'l' => $registry->nlsconfig->languages[$lcode],
                    'v' => $lcode
                );
            }
        }

        parent::__construct($params);
    }

    /**
     */
    public function attach()
    {
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
            $this->_params['id'] . ' = new SpellChecker(' . Horde_Serialize::serialize($opts, Horde_Serialize::JSON) . ')'
        ), 'dom');
    }

    /**
     */
    public function handle($args, $post)
    {
        $spellArgs = empty($GLOBALS['conf']['spell']['params'])
            ? array()
            : $GLOBALS['conf']['spell']['params'];

        $spellArgs['html'] = !empty($args['html']);

        $input = Horde_Util::getPost($args['input']);

        if (isset($args['locale'])) {
            $spellArgs['locale'] = $args['locale'];
        }
        if (empty($spellArgs['locale']) &&
            class_exists('Text_LanguageDetect')) {
            $spellArgs['locale'] = $GLOBALS['injector']->getInstance('Text_LanguageDetect')->create()->getLanguageCode($input);
        }
        if (empty($spellArgs['locale']) && isset($GLOBALS['language'])) {
            $spellArgs['locale'] = $GLOBALS['language'];
        }

        /* Add local dictionary words. */
        try {
            $result = Horde::loadConfiguration('spelling.php', 'ignore_list', 'horde');
            $spellArgs['localDict'] = $result;
        } catch (Horde_Exception $e) {}

        try {
            return Horde_SpellChecker::factory($GLOBALS['conf']['spell']['driver'], $spellArgs)->spellCheck($input);
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return array('bad' => array(), 'suggestions' => array());
        }
    }

}
