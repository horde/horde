<?php
/**
 * The pretty AJAX autocompleter.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Ajax_Imple_AutoCompleter_Pretty extends Horde_Core_Ajax_Imple_AutoCompleter_Ajax
{
    /**
     * @param array $params  Configuration options:
     *   - box
     *   - boxClass
     *   - deleteIcon
     *   - displayFilter
     *   - existing
     *   - filterCallback
     *   - growingInputClass
     *   - listClass
     *   - minTriggerWidth
     *   - onAdd
     *   - onRemove
     *   - requireSelection
     *   - trigger
     *   - triggerContainer
     *   - uri
     */
    public function __construct($search, array $params = array())
    {
        parent::__construct(array_merge(array(
            'deleteIcon' => strval(Horde_Themes::img('delete-small.png')),
            'triggerContainer' => strval(new Horde_Support_Randomid())
        )));

        $this->_raw = array_merge($this->_raw, array(
            'displayFilter',
            'filterCallback',
            'onAdd',
            'onRemove'
        ));

        $GLOBALS['page_output']->addScriptFile('prettyautocomplete.js', 'horde');
    }

    /**
     */
    public function generate(Horde_Core_Ajax_Imple_AutoCompleter $ac)
    {
        $dom_id = $ac->getDomId();

        if (!isset($this->params->trigger)) {
            $this->params['trigger'] = $dom_id;
        }
        if (!isset($this->params->uri)) {
            $this->params['uri'] = strval($ac->getImpleUrl()->setRaw(true));
        }

        return 'new PrettyAutocompleter(' .
            Horde_Serialize::serialize($dom_id, Horde_Serialize::JSON) . ',' .
            '{' . implode(',', $this->_getOpts($ac)) . '})';
    }

}
