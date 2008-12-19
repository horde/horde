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
class IMP_Imple_ContactAutoCompleter extends IMP_Imple
{
    /**
     * The URL to use in attach().
     *
     * @var string
     */
    protected $_url;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     * <pre>
     * 'triggerId' => TODO (optional)
     * 'resultsId' => TODO (optional)
     * </pre>
     */
    function __construct($params)
    {
        if (empty($params['triggerId'])) {
            $params['triggerId'] = $this->_randomid();
        }
        if (empty($params['resultsId'])) {
            $params['resultsId'] = $params['triggerId'] . '_results';
        }

        parent::__construct($params);
    }

    /**
     * Attach the object to a javascript event.
     */
    public function attach()
    {
        static $list_output = false;

        parent::attach();
        Horde::addScriptFile('autocomplete.js', 'imp', true);

        $params = array(
            '"' . $this->_params['triggerId'] . '"',
            '"' . $this->_params['resultsId'] . '"'
        );

        $js_params = array(
            'tokens: [",", ";"]',
            'indicator: "' . $this->_params['triggerId'] . '_loading_img"',
            'afterUpdateElement: function(f, t) { if (!f.value.endsWith(";")) { f.value += ","; } f.value += " "; }'
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
                $this->_url = Horde::url($GLOBALS['registry']->get('webroot', 'imp') . '/imple.php?imple=ContactAutoCompleter/input=' . rawurlencode($this->_params['triggerId']), true);
            }
            $params[] = '"' . $this->_url . '"';

            $js_params[] = 'minChars: ' . intval($GLOBALS['conf']['compose']['ac_threshold'] ? $GLOBALS['conf']['compose']['ac_threshold'] : 1);
        } else {
            if (!$list_output) {
                if (!isset($addrlist)) {
                    $addrlist = IMP_Compose::getAddressList();
                }
                IMP::addInlineScript('if (!IMP) { var IMP = {}; } IMP.ac_list = '. Horde_Serialize::serialize(array_map('htmlspecialchars', $addrlist), SERIALIZE_JSON, NLS::getCharset()));
                $list_output = true;
            }
            $func = 'Autocompleter.Local';
            $params[] = 'IMP.ac_list';
            $js_params[] = 'partialSearch: true';
            $js_params[] = 'fullSearch: true';
        }

        $params[] = '{' . implode(',', $js_params) . '}';
        IMP::addInlineScript('new ' . $func . '(' . implode(',', $params) . ')', 'dom');
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
            !($input = Util::getPost($args['input']))) {
            return array();
        }

        return array_map('htmlspecialchars', IMP_Compose::expandAddresses($input));
    }

}
