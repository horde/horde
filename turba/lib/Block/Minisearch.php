<?php
/**
 * Allow searching of address books from the portal.
 *
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 */
class Turba_Block_Minisearch extends Horde_Core_Block
{
    /**
     * The available options for address book selection
     *
     * @var array
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
        global $page_output, $registry;

        $abooks = empty($this->_params['addressbooks'])
            ? array_keys($this->_options)
            : $this->_params['addressbooks'];

        $page_output->addInlineJsVars(array(
            'TurbaMinisearch.abooks' => $abooks,
            'TurbaMinisearch.URI_AJAX' => $registry->getServiceLink('ajax', 'turba')->url
        ));
        $page_output->addScriptFile('minisearch.js');

        Horde::startBuffer();
        include TURBA_TEMPLATES . '/block/minisearch.inc';
        return Horde::endBuffer();
    }

}
