<?php
/**
 * Copyright 2005-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class Imple_SpellChecker extends Imple
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
    function __construct($params = array())
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
        if (empty($params['states'])) {
            $params['states'] = '""';
        } else {
            $params['states'] = Horde_Serialize::serialize($params['states'], SERIALIZE_JSON, NLS::getCharset());
        }
        if (empty($params['locales'])) {
            $params['locales'] = array();
            foreach (array_keys($GLOBALS['nls']['spelling']) as $lcode) {
                $params['locales'][$lcode] = $GLOBALS['nls']['languages'][$lcode];
            }
        }
        asort($params['locales'], SORT_LOCALE_STRING);
        $params['locales'] = Horde_Serialize::serialize($params['locales'], SERIALIZE_JSON, NLS::getCharset());

        parent::__construct($params);
    }

    /**
     */
    public function attach()
    {
        parent::attach();
        Horde::addScriptFile('KeyNavList.js', 'imp', true);
        Horde::addScriptFile('SpellChecker.js', 'imp', true);
        $url = Horde::url($GLOBALS['registry']->get('webroot', 'imp') . '/imple.php?imple=SpellChecker/input=' . rawurlencode($this->_params['targetId']), true);
        IMP::addInlineScript($this->_params['id'] . ' = new SpellChecker("' . $url . '", "' . $this->_params['targetId'] . '", "' . $this->_params['triggerId'] . '", ' . $this->_params['states'] . ', ' . $this->_params['locales'] . ', \'widget\');', 'dom');
    }

    /**
     */
    public function handle($args)
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
        $result = Horde::loadConfiguration('spelling.php', 'ignore_list');
        if (!is_a($result, 'PEAR_Error')) {
            $spellArgs['localDict'] = $result;
        }

        if (!empty($args['html'])) {
            $spellArgs['html'] = true;
        }

        $speller = Horde_SpellChecker::factory(
            $GLOBALS['conf']['spell']['driver'], $spellArgs);
        if ($speller === false) {
            return array();
        }

        $result = $speller->spellCheck(Util::getPost($args['input']));
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return array('bad' => array(), 'suggestions' => array());
        } else {
            return $result;
        }
    }

}
