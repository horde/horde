<?php
/**
 * @package Turba
 */
class Turba_Form_EditContactGroup extends Turba_Form_EditContact
{
    public function __construct($vars, $contact)
    {
        $this->addHidden('', 'objectkeys', 'text', false);
        $this->addHidden('', 'original_source', 'text', false);
        $action = $this->addHidden('', 'actionID', 'text', false);
        $action->setDefault('groupedit');
        Horde_Form::__construct($vars, $contact);
        $vars->set('actionID', 'groupedit');

        $objectkeys = $vars->get('objectkeys');
        $source = $vars->get('source');
        $key = $vars->get('key');
        if ($source . ':' . $key == $objectkeys[0]) {
            /* First contact */
            $this->setButtons(_("Next"));
        } elseif ($source . ':' . $key == $objectkeys[count($objectkeys) - 1]) {
            /* Last contact */
            $this->setButtons(_("Previous"));
        } else {
            /* In between */
            $this->setButtons(_("Previous"));
            $this->appendButtons(_("Next"));
        }
        $this->appendButtons(_("Finish"));
    }

    public function renderActive($renderer, $vars, $action, $method)
    {
        parent::renderActive($renderer, $vars, $action, $method);

        /* Read the columns to display from the preferences. */
        $source = $vars->get('source');
        $sources = Turba::getColumns();
        $columns = isset($sources[$source]) ? $sources[$source] : array();

        $results = new Turba_List($vars->get('objectkeys'));
        $listView = new Turba_View_List($results, array('Group' => true), $columns);
        echo '<br />' . $listView->getPage($numDisplayed);
    }

    public function execute()
    {
        $result = parent::execute();

        $this->getInfo($this->_vars, $info);

        $next_page = Horde::url('edit.php', true)->add(array(
            'source' => $info['source'],
            'original_source' => $info['original_source'],
            'objectkeys' => $info['objectkeys'],
            'url' => $info['url'],
            'actionID' => 'groupedit'
        ));

        $objectkey = array_search($info['source'] . ':' . $info['key'], $info['objectkeys']);

        $submitbutton = $this->_vars->get('submitbutton');
        if ($submitbutton == _("Finish")) {
            $next_page = Horde::url('browse.php', true);
            if ($info['original_source'] == '**search') {
                $next_page->add('key', $info['original_source']);
            } else {
                $next_page->add('source', $info['original_source']);
            }
        } elseif ($submitbutton == _("Previous") && $info['source'] . ':' . $info['key'] != $info['objectkeys'][0]) {
            /* Previous contact */
            list(, $previous_key) = explode(':', $info['objectkeys'][$objectkey - 1]);
            $next_page->add('key', $previous_key);
            if ($this->getOpenSection()) {
                $next_page->add('__formOpenSection', $this->getOpenSection());
            }
        } elseif ($submitbutton == _("Next") &&
                  $info['source'] . ':' . $info['key'] != $info['objectkeys'][count($info['objectkeys']) - 1]) {
            /* Next contact */
            list(, $next_key) = explode(':', $info['objectkeys'][$objectkey + 1]);
            $next_page->add('key', $next_key);
            if ($this->getOpenSection()) {
                $next_page->add('__formOpenSection', $this->getOpenSection());
            }
        }

        $next_page->redirect();
    }

}
