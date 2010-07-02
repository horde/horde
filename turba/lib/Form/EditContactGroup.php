<?php
/**
 * @package Turba
 */
class Turba_Form_EditContactGroup extends Turba_Form_EditContact
{
    public function __construct(&$vars, &$contact)
    {
        $this->addHidden('', 'objectkeys', 'text', false);
        $this->addHidden('', 'original_source', 'text', false);
        $this->addHidden('', 'actionID', 'text', false);

        parent::Turba_Form_EditContact($vars, $contact);
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

    function renderActive($renderer, &$vars, $action, $method)
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

    function execute()
    {
        $result = parent::execute();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $this->getInfo($this->_vars, $info);

        $next_page = Horde::applicationUrl('edit.php', true);
        $next_page = Horde_Util::addParameter($next_page,
                                        array('source' => $info['source'],
                                              'original_source' => $info['original_source'],
                                              'objectkeys' => $info['objectkeys'],
                                              'url' => $info['url'],
                                              'actionID' => 'groupedit'),
                                        null, false);
        $objectkey = array_search($info['source'] . ':' . $info['key'], $info['objectkeys']);

        $submitbutton = $this->_vars->get('submitbutton');
        if ($submitbutton == _("Finish")) {
            $next_page = Horde::url('browse.php', true);
            if ($info['original_source'] == '**search') {
                $next_page = Horde_Util::addParameter($next_page, 'key', $info['original_source'], false);
            } else {
                $next_page = Horde_Util::addParameter($next_page, 'source', $info['original_source'], false);
            }
        } elseif ($submitbutton == _("Previous") && $info['source'] . ':' . $info['key'] != $info['objectkeys'][0]) {
            /* Previous contact */
            list(, $previous_key) = explode(':', $info['objectkeys'][$objectkey - 1]);
            $next_page = Horde_Util::addParameter($next_page, 'key', $previous_key, false);
            if ($this->getOpenSection()) {
                $next_page = Horde_Util::addParameter($next_page, '__formOpenSection', $this->getOpenSection(), false);
            }
        } elseif ($submitbutton == _("Next") &&
                  $info['source'] . ':' . $info['key'] != $info['objectkeys'][count($info['objectkeys']) - 1]) {
            /* Next contact */
            list(, $next_key) = explode(':', $info['objectkeys'][$objectkey + 1]);
            $next_page = Horde_Util::addParameter($next_page, 'key', $next_key, false);
            if ($this->getOpenSection()) {
                $next_page = Horde_Util::addParameter($next_page, '__formOpenSection', $this->getOpenSection(), false);
            }
        }

        header('Location: ' . $next_page);
        exit;
    }

}
