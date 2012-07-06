<?php
/**
 * Allow searching of address books from the portal.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 */
class Turba_Block_Minisearch extends Horde_Core_Block
{
    /**
     * The available options for address book selection
     */
    protected $_options = array();

    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);
        foreach (Turba::getAddressBooks(Horde_Perms::READ) as $key => $addressbook) {
             $this->_options[$key] = $addressbook['title'];
        }
        $this->_name = _("Contact Search");
    }

    /**
     */
    protected function _title()
    {
        return Horde::url($GLOBALS['registry']->getInitialPage(), true)->link()
            . $this->getName() . '</a>';
    }

    /**
     */
    protected function _params()
    {
        return array(
            'addressbooks' => array(
                'type' => 'multienum',
                'name' => _("Address Books"),
                'values' => $this->_options
            )
        );
    }
    /**
     */
    protected function _content()
    {
        if (!$GLOBALS['browser']->hasFeature('iframes')) {
            return '<em>' . _("A browser that supports iframes is required") . '</em>';
        }

        $calendars = empty($this->_params['addressbooks'])
            ? implode(';', array_keys($this->_options))
            : implode(';', $this->_params['addressbooks']);

        $GLOBALS['page_output']->addScriptFile('minisearch.js');

        Horde::startBuffer();
        include TURBA_TEMPLATES . '/block/minisearch.inc';
        return Horde::endBuffer();
    }

}
