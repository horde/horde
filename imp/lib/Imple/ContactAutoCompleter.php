<?php
/**
 * $Horde: imp/lib/Imple/ContactAutoCompleter.php,v 1.35 2008/07/29 20:29:17 slusarz Exp $
 *
 * Copyright 2005-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class Imple_ContactAutoCompleter extends Imple {

    /**
     * The URL to use in attach().
     *
     * @var string
     */
    var $_url;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     * <pre>
     * 'triggerId' => TODO (optional)
     * 'resultsId' => TODO (optional)
     * </pre>
     */
    function Imple_ContactAutoCompleter($params)
    {
        if (empty($params['triggerId'])) {
            $params['triggerId'] = $this->_randomid();
        }
        if (empty($params['resultsId'])) {
            $params['resultsId'] = $params['triggerId'] . '_results';
        }

        parent::Imple($params);
    }

    /**
     * Attach the Imple object to a javascript event.
     */
    function attach()
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

        if (!isset($_SESSION['imp']['cache']['ac_ajax'])) {
            $success = $use_ajax = true;
            require_once IMP_BASE . '/lib/Compose.php';
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
                $use_ajax = count($addrlist) > 200;
            }
            $_SESSION['imp']['cache']['ac_ajax'] = $use_ajax;
        }

        if ($_SESSION['imp']['cache']['ac_ajax']) {
            $func = 'Ajax.Autocompleter';
            if (empty($this->_url)) {
                $this->_url = Horde::url($GLOBALS['registry']->get('webroot', 'imp') . '/imple.php?imple=ContactAutoCompleter/input=' . rawurlencode($this->_params['triggerId']), true);
            }
            $params[] = '"' . $this->_url . '"';
        } else {
            if (!$list_output) {
                if (!isset($addrlist)) {
                    require_once IMP_BASE . '/lib/Compose.php';
                    $addrlist = IMP_Compose::getAddressList();
                }
                require_once 'Horde/Serialize.php';
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
    function handle($args)
    {
        // Avoid errors if 'input' isn't set and short-circuit empty searches.
        if (empty($args['input']) ||
            !($input = Util::getPost($args['input']))) {
            return array();
        }

        require_once IMP_BASE . '/lib/Compose.php';
        return array_map('htmlspecialchars', IMP_Compose::expandAddresses($input));
    }

}
