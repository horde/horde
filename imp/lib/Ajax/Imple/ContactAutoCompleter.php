<?php
/**
 * Attach the contact auto completer to a javascript element.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Ajax_Imple_ContactAutoCompleter extends Horde_Ajax_Imple_Base
{
    /**
     * The URL to use in attach().
     *
     * @var string
     */
    protected $_url;

    /**
     * Has the address book been output to the browser?
     *
     * @var boolean
     */
    static protected $_listOutput = false;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     * <pre>
     * 'triggerId' => TODO (optional)
     * </pre>
     */
    public function __construct($params)
    {
        if (empty($params['triggerId'])) {
            $params['triggerId'] = $this->_randomid();
        }

        parent::__construct($params);
    }

    /**
     * Attach the object to a javascript event.
     */
    public function attach()
    {
        Horde::addScriptFile('effects.js', 'horde');
        Horde::addScriptFile('autocomplete.js', 'horde');
        Horde::addScriptFile('KeyNavList.js', 'horde');
        Horde::addScriptFile('liquidmetal.js', 'horde');

        $params = array(
            '"' . $this->_params['triggerId'] . '"'
        );

        $js_params = array(
            'indicator' => $this->_params['triggerId'] . '_loading_img',
            'onSelect' => 1,
            'onType' => 1,
            'tokens' => array(',', ';')
        );
        $ac_browser = empty($GLOBALS['conf']['compose']['ac_browser']) ? 0 : $GLOBALS['conf']['compose']['ac_browser'];

        if ($ac_browser && !isset($_SESSION['imp']['cache']['ac_ajax'])) {
            $success = $use_ajax = true;
            $sparams = IMP_Compose::getAddressSearchParams();
            foreach ($sparams['fields'] as $val) {
                array_map('strtolower', $val);
                sort($val);
                if ($val != array('email', 'name')) {
                    $success = false;
                    break;
                }
            }
            if ($success) {
                $addrlist = IMP_Compose::getAddressList();
                $use_ajax = count($addrlist) > $ac_browser;
            }
            $_SESSION['imp']['cache']['ac_ajax'] = $use_ajax;
        }

        if (!$ac_browser || $_SESSION['imp']['cache']['ac_ajax']) {
            $func = 'Ajax.Autocompleter';
            if (empty($this->_url)) {
                $this->_url = $this->_getUrl('ContactAutoCompleter', 'imp', array('input' => $this->_params['triggerId']));
            }
            $params[] = '"' . $this->_url . '"';

            $js_params['minChars'] = intval($GLOBALS['conf']['compose']['ac_threshold'] ? $GLOBALS['conf']['compose']['ac_threshold'] : 1);
        } else {
            if (!self::$_listOutput) {
                if (!isset($addrlist)) {
                    $addrlist = IMP_Compose::getAddressList();
                }
                Horde::addInlineScript('if (!IMP) { var IMP = {}; } IMP.ac_list = '. Horde_Serialize::serialize(array_map('htmlspecialchars', $addrlist), Horde_Serialize::JSON, Horde_Nls::getCharset()));
                self::$_listOutput = true;
            }

            $func = 'Autocompleter.Local';
            $params[] = 'IMP.ac_list';
            $js_params['partialSearch'] = 1;
            $js_params['fullSearch'] = 1;
            $js_params['score'] = 1;
        }

        // There is no support for storing functions in JSON.  Have to
        // hack around this a bit.
        $js_params = Horde_Serialize::serialize($js_params, Horde_Serialize::JSON);
        $js_params = str_replace('"onSelect":1', '"onSelect":function (v) { if (!v.endsWith(";")) { v += ","; } return v + " "; }', $js_params);
        $js_params = str_replace('"onType":1', '"onType":function (e) { return e.include("<") ? "" : e; }', $js_params);

        Horde::addInlineScript($this->_params['triggerId'] . '1 = new ' . $func . '(' . implode(',', $params) . ',' . $js_params . ')', 'dom');
    }

    /**
     * Perform the address search.
     *
     * @param array $args  Array with 1 key: 'input'.
     *
     * @return array  The data to send to the autocompleter JS code.
     */
    public function handle($args)
    {
        // Avoid errors if 'input' isn't set and short-circuit empty searches.
        if (empty($args['input']) ||
            !($input = Horde_Util::getPost($args['input']))) {
            return array();
        }

        return IMP_Compose::expandAddresses($input, array('levenshtein' => true));
    }

}
