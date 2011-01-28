<?php
/**
 * @package Turba
 */
class Turba_Form_AddContact extends Turba_Form_Contact
{
    protected $_contact = null;

    public function __construct($vars, Turba_Object $contact)
    {
        // @TODO: $addSources should be injected
        global $addSources, $notification;

        parent::__construct($vars, $contact, true, _("Add Contact"));
        $this->_contact = $contact;

        $this->setButtons(_("Add"));
        $this->addHidden('', 'url', 'text', false);
        $this->addHidden('', 'key', 'text', false);

        /* Check if a source selection box is required. */
        if (count($addSources) > 1) {
            /* Multiple sources, show a selection box. */
            $options = array();
            foreach ($addSources as $key => $config) {
                $options[$key] = $config['title'];
            }
            $v = $this->addVariable(_("Choose an address book"), 'source', 'enum', true, false, null, array($options, true));
            $action = Horde_Form_Action::factory('submit');
            $v->setAction($action);
            $v->setOption('trackchange', true);
            if (is_null($vars->get('formname')) &&
                $vars->get($v->getVarName()) != $vars->get('__old_' . $v->getVarName())) {
                $notification->push(sprintf(_("Selected address book \"%s\"."), $addSources[$vars->get('source')]['title']), 'horde.message');
            }
        } else {
            /* One source, no selection box but store the value in a
             * hidden field. */
            $this->addHidden('', 'source', 'text', true);
        }

        if ($this->_contact) {
            parent::_addFields($this->_contact);
        }
    }

    public function validate()
    {
        if (!$this->_vars->get('source')) {
            return false;
        }
        return parent::validate($this->_vars);
    }

    public function execute()
    {
        // @TODO $driver should be injected, or at the very least, obtained
        //       via the injector
        global $driver, $notification;

        /* Form valid, save data. */
        $this->getInfo($this->_vars, $info);
        $source = $info['source'];
        foreach ($info['object'] as $info_key => $info_val) {
            if ($GLOBALS['attributes'][$info_key]['type'] == 'image' && !empty($info_val['file'])) {
                $this->_contact->setValue($info_key, file_get_contents($info_val['file']));
                $this->_contact->setValue($info_key . 'type', $info_val['type']);
            } else {
                $this->_contact->setValue($info_key, $info_val);
            }
        }
        $contact = $this->_contact->attributes;
        unset($contact['__owner']);

        /* Create Contact. */
        try {
            $key = $driver->add($contact);
        } catch (Turba_Exception $e) {
            Horde::logMessage($e, 'ERR');
            $key = null;
        }

        if ($key) {
            // Try 3 times to get the new entry. We retry to allow setups like
            // LDAP replication to work.
            for ($i = 0; $i < 3; ++$i) {
                try {
                    $ob = $driver->getObject($key);
                    $notification->push(sprintf(_("%s added."), $ob->getValue('name')), 'horde.success');
                    $url = empty($info['url'])
                        ? $ob->url('Contact', true)
                        : new Horde_Url($info['url']);
                    $url->redirect();
                } catch (Turba_Exception $e) {}
                sleep(1);
            }
        }

        $notification->push(_("There was an error adding the new contact. Contact your system administrator for further help."), 'horde.error');
    }

}
