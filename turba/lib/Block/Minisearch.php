<?php
/**
 * Allow searching of address books from the portal.
 */
class Turba_Block_Minisearch extends Horde_Core_Block
{
    /**
     * The available options for address book selection
     */
    protected $_options = array();

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
     * Select the address books where to search
     *
     * @var string
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
     * return the content
     */
    protected function _content()
    {
        if ($GLOBALS['browser']->hasFeature('iframes')) {
            if (!empty($this->_params['addressbooks'])) {
                $imploded_calendars = implode(';', $this->_params['addressbooks']);
            } else {
                $imploded_calendars = implode(';', array_keys($this->_options));
            }
            Horde::startBuffer();
            include TURBA_TEMPLATES . '/block/minisearch.inc';
            return Horde::endBuffer();
        }
        return '<em>' . _("A browser that supports iframes is required") . '</em>';
    }

}
