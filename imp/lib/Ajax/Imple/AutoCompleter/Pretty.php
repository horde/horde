<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * The pretty AJAX autocompleter. Extended in IMP to use the IMP-specific
 * autocompleter library instead of the Horde_Core library.
 *
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class IMP_Ajax_Imple_AutoCompleter_Pretty
extends Horde_Core_Ajax_Imple_AutoCompleter_Ajax
{
    /**
     */
    public function __construct(array $params = array())
    {
        global $page_output;

        parent::__construct(array_merge(
            array(
                'boxClass' => 'hordeACBox impACBox',
                'boxClassFocus' => 'impACBoxFocus',
                'deleteIcon' => strval(Horde_Themes::img('delete-small.png')),
                'displayFilter' => 'new Function("t", "return t.sub(/<[^>]*>$/, \"\").strip().escapeHTML()")',
                'growingInputClass' => 'hordeACTrigger impACTrigger',
                'listClass' => 'hordeACList impACList',
                'processValueCallback' => 'ImpComposeBase.autocompleteValue.bind(ImpComposeBase)',
                'removeClass' => 'hordeACItemRemove impACItemRemove',
                'triggerContainer' => strval(new Horde_Support_Randomid())
            ), $params)
        );

        $this->_raw = array_merge($this->_raw, array(
            'displayFilter',
            'filterCallback',
            'onAdd',
            'onRemove',
            'processValueCallback'
        ));

        $page_output->addScriptFile('compose-base.js');
        $page_output->addScriptFile('prettyautocomplete.js');
    }

    /**
     */
    public function generate(Horde_Core_Ajax_Imple_AutoCompleter $ac)
    {
        $dom_id = $ac->getDomId();

        if (!isset($this->params['trigger'])) {
            $this->params['trigger'] = $dom_id;
        }
        if (!isset($this->params['uri'])) {
            $this->params['uri'] = strval($ac->getImpleUrl()->add(array('input' => $this->params['trigger']))->setRaw(true));
        }

        return 'new IMP_PrettyAutocompleter(' .
            json_encode($dom_id) . ',' .
            '{' . implode(',', $this->_getOpts($ac)) . '})';
    }

}
