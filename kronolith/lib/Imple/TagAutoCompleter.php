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
        //$params['triggerId'] = $params['triggerId'] . '_' . $params['id'];
        if (empty($params['resultsId'])) {
            $params['resultsId'] = $params['triggerId'] . '_results';
        }

        parent::__construct($params);
    }

    /**
     * Attach the Imple object to a javascript event.
     */
    public function attach()
    {
        parent::attach();
        Horde::addScriptFile('autocomplete.js', 'kronolith', true);

        $params = array(
            '"' . $this->_params['triggerId'] . '"',
            '"' . $this->_params['resultsId'] . '"',
            '"' . Horde::url($GLOBALS['registry']->get('webroot', 'kronolith') . '/imple.php?imple=TagAutoCompleter/input=' . rawurlencode($this->_params['triggerId']), true) . '"'
        );

        $js_params = array(
            'tokens: [",", ";"]',
            'indicator: "' . $this->_params['triggerId'] . '_loading_img"',
            'afterUpdateElement: function(f, t) { if (!f.value.endsWith(";")) { f.value += ","; } f.value += " "; }'
        );

        $params[] = '{' . implode(',', $js_params) . '}';

        Kronolith::addInlineScript('new Ajax.Autocompleter(' . implode(',', $params) . ')', 'dom');
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
        $tagger = new Kronolith_Tagger();
        $tags = $tagger->listTags($search);



        return array_values($tags);
    }

}
