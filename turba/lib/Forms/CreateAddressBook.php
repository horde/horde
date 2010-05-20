<?php
/**
 * Horde_Form for creating address books.
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @package Turba
 */

/** Horde_Form_Renderer */
require_once 'Horde/Form/Renderer.php';

/**
 * The Turba_CreateAddressBookForm class provides the form for
 * creating an address book.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Turba
 */
class Turba_CreateAddressBookForm extends Horde_Form {

    function Turba_CreateAddressBookForm(&$vars)
    {
        parent::Horde_Form($vars, _("Create Address Book"));

        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        $this->setButtons(array(_("Create")));
    }

    function execute()
    {
        // Need a clean cfgSources array
        include TURBA_BASE . '/config/sources.php';

        $driver = Turba_Driver::singleton($cfgSources[$GLOBALS['conf']['shares']['source']]);
        if ($driver instanceof PEAR_Error) {
            return $driver;
        }

        $params = array(
            'params' => array('source' => $GLOBALS['conf']['shares']['source']),
            'name' => $this->_vars->get('name'),
            'desc' => $this->_vars->get('description'),
        );
        return $driver->createShare(md5(mt_rand()), $params);
    }

}
