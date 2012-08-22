<?php
/**
 * Imple to attach the spellchecker to an HTML element.
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
     * @param array $params  OPTIONAL configuration parameters:
     *   - locales: (array) List of supported locales.
     *   - states: (array) TODO
     *   - targetId: (string) TODO
     */
    public function __construct(array $params = array())
    {
        global $registry;

        if (!isset($params['targetId'])) {
            $params['targetId'] = strval(new Horde_Support_Randomid());
        }

        if (!isset($params['locales'])) {
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
    protected function _attach($init)
    {
        global $page_output;

        if ($init) {
            $page_output->addScriptFile('spellchecker.js', 'horde');
            $page_output->addScriptPackage('Keynavlist');

            $page_output->addInlineJsVars(array(
                'HordeImple.SpellChecker' => new stdClass
            ));
        }

        $dom_id = $this->getDomId();

        $opts = array(
            'locales' => $this->_params['locales'],
            'statusButton' => $dom_id,
            'target' => $this->_params['targetId'],
            'url' => strval($this->getImpleUrl()->setRaw(true)->add(array('input' => $this->_params['targetId'])))
        );
        if (isset($this->_params['states'])) {
            $opts['bs'] = $this->_params['states'];
        }

        $page_output->addInlineScript(array(
            'HordeImple.SpellChecker.' . $dom_id . '=new SpellChecker(' . Horde_Serialize::serialize($opts, Horde_Serialize::JSON) . ')'
        ), true);

        return false;
    }

    /**
     * Form variables used:
     *   - input
     */
    protected function _handle(Horde_Variables $vars)
    {
        global $conf, $injector, $language;

        $args = Horde::getDriverConfig('spell', null);
        $input = $vars->get($vars->input);

        if (isset($vars->locale)) {
            $args['locale'] = $vars->locale;
        } elseif (empty($args['locale'])) {
            try {
                $args['locale'] = $injector->getInstance('Horde_Core_Factory_LanguageDetect')->getLanguageCode($input);
            } catch (Horde_Exception $e) {}
        }

        if (empty($args['locale']) && isset($language)) {
            $args['locale'] = $language;
        }

        /* Add local dictionary words. */
        try {
            $result = Horde::loadConfiguration('spelling.php', 'ignore_list', 'horde');
            $args['localDict'] = $result;
        } catch (Horde_Exception $e) {}

        try {
            return new Horde_Core_Ajax_Response_Prototypejs(
                Horde_SpellChecker::factory($conf['spell']['driver'], $args)->spellCheck($input)
            );
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return array(
                'bad' => array(),
                'suggestions' => array()
            );
        }
    }

}
