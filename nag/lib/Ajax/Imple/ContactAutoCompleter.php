<?php
/**
 * Attach the contact autocompleter to a HTML element.
 *
 * Copyright 2005-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl.php BSD
 * @package  Nag
 */
class Nag_Ajax_Imple_ContactAutoCompleter extends Horde_Core_Ajax_Imple_ContactAutoCompleter
{
    /**
     */
    protected function _getAddressbookSearchParams()
    {
        $ob = new stdClass;
        $ob->fields = array();
        $ob->sources = array();

        return $ob;
    }

}
