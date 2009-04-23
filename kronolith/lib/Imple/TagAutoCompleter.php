<?php
/**
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Kronolith
 */
class Kronolith_Imple_TagAutoCompleter extends Kronolith_Imple
{
    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     * <pre>
     * 'triggerId' => TODO (optional)
     * 'resultsId' => TODO (optional)
     * </pre>
     */
    public function __construct($params)
    {
        if (!count($params)) {
            return;
        }
        if (empty($params['resultsId'])) {
            $params['resultsId'] = $params['triggerId'] . '_results';
        }

        parent::__construct($params);
    }

    /**
     * Attach the Imple object to a javascript event.
     * If the 'pretty' parameter is empty then we want a
     * traditional autocompleter, otherwise we get a spiffy pretty one.
     *
     */
    public function attach()
    {
        global $registry;
        parent::attach();
        Horde::addScriptFile('autocomplete.js', 'horde', true);

        if ($pretty = !empty($this->_params['pretty'])) {
            Horde::addScriptFile('taggerAutoCompleter.js', 'kronolith', true);
            $this->_params['uri'] =  Horde::url($GLOBALS['registry']->get('webroot', 'kronolith') . '/imple.php?imple=TagAutoCompleter', true);
        } else {
            $this->_params['uri'] =  Horde::url($GLOBALS['registry']->get('webroot', 'kronolith') . '/imple.php?imple=TagAutoCompleter/input=' . rawurlencode($this->_params['triggerId']), true);
        }

        if (!$pretty) {
            $params = array(
                '"' . $this->_params['triggerId'] . '"',
                '"' . $this->_params['resultsId'] . '"',
                '"' . $this->_params['uri'] . '"'
            );
        }

        $js_params = array(
            'tokens: [","]',
            'indicator: "' . $this->_params['triggerId'] . '_loading_img"'
        );

        // The pretty version needs to set this callback itself...
        if (!$pretty && !empty($this->_params['updateElement'])) {
            $js_params[] = 'updateElement: ' . $this->_params['updateElement'];
        }

        $params[] = '{' . implode(',', $js_params) . '}';

        if ($pretty) {
            $js_vars = array('boxClass' => 'hordeACBox kronolithLongField',
                             'uri' => $this->_params['uri'],
                             'trigger' => $this->_params['triggerId'],
                             'URI_IMG_HORDE' => $registry->getImageDir('horde'),
                             'params' => $params);

            if (!empty($this->_params['existing'])) {
                $js_vars['existing'] = $this->_params['existing'];
            }

            $script = array('new KronolithTagger(' . Horde_Serialize::serialize($js_vars, Horde_Serialize::JSON, NLS::getCharset()) . ')');
        } else {
            $script = array('new Ajax.Autocompleter(' . implode(',', $params) . ')');
        }

        Kronolith::addInlineScript($script, 'dom');
    }

    /**
     * TODO
     *
     * @param array $args  TODO
     *
     * @return string  TODO
     */
    public function handle($args)
    {
        // Avoid errors if 'input' isn't set and short-circuit empty searches.
        if (empty($args['input']) ||
            !($input = Util::getFormData($args['input']))) {
            return array();
        }
        return array_map('htmlspecialchars', $this->getTagList($input));
    }

    /**
     * Get a list of existing, used tags that match the search term.
     *
     * @param string $search  The term to search by.
     *
     * @return array  All matching tags.
     */
    static public function getTagList($search = '')
    {
        $tagger = Kronolith::getTagger();
        $tags = $tagger->listTags($search);

        return array_values($tags);
    }

}
