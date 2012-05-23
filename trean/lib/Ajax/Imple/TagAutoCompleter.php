<?php
/**
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl.php BSD
 * @package  Trean
 */
class Trean_Ajax_Imple_TagAutoCompleter extends Horde_Core_Ajax_Imple_AutoCompleter
{
    const DOMID = 'treanBookmarkTags';

    /**
     */
    protected function _getAutoCompleter()
    {
        $GLOBALS['page_output']->addInlineScript(array(
            'HordeImple.AutoCompleter.' . self::DOMID . '.init()'
        ), true);

        return new Horde_Core_Ajax_Imple_AutoCompleter_Pretty(array(
            'box' => 'treanEventACBox',
            'existing' => $this->_params['existing'],
            'id' => self::DOMID
        ));
    }

    /**
     */
    protected function _handleAutoCompleter($input)
    {
        $tagger = new Trean_Tagger();
        return array_values($tagger->listTags($input));
    }

}
