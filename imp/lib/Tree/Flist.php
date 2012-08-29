<?php
/**
 * The IMP_Tree_Flist class provides an IMP dropdown mailbox (folder tree)
 * list.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 GPL
 * @package  IMP
 */
class IMP_Tree_Flist extends Horde_Tree_Renderer_Select
{
    /**
     * Filter list.
     *
     * @var array
     */
    protected $_filter = array();

    /**
     * Constructor.
     *
     * @param Horde_Tree $tree  A tree object.
     * @param array $params     Additional parameters.
     *   - abbrev: (integer) Abbreviate long mailbox names by replacing the
     *             middle of the name with '...'? Value is the total length
     *             of the string.
     *             DEFAULT: 30
     *   - container_select: (boolean) Allow containers to be selected?
     *                       DEFAULT: false
     *   - customhtml: (string) Custom HTML to add to the beginning of the HTML
     *                 SELECT tag.
     *                 DEFAULT: ''
     *   - filter: (array) An array of mailboxes to ignore.
     *             DEFAULT: Display all
     *   - heading: (string) The label for an empty-value option at the top of
     *              the list.
     *              DEFAULT: ''
     *   - inc_notepads: (boolean) Include user's editable notepads in list?
     *                   DEFAULT: No
     *   - inc_tasklists: (boolean) Include user's editable tasklists in list?
     *                    DEFAULT: No
     *   - inc_vfolder: (boolean) Include user's virtual folders in list?
     *                  DEFAULT: No
     *   - new_mbox: (boolean) Display an option to create a new mailbox?
     *               DEFAULT: No
     */
    public function __construct(Horde_Tree $tree, array $params = array())
    {
        $params = array_merge(array(
            'abbrev' => 30
        ), $params);

        parent::__construct($tree, $params);
    }

    /**
     * @param boolean $static  Ignored in this driver.
     */
    public function getTree($static = false)
    {
        global $conf, $injector, $registry;

        $this->_nodes = $this->_tree->getNodes();

        $filter = $injector->createInstance('Horde_Text_Filter');
        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);
        $t->set('optgroup', $this->getOption('optgroup'));

        /* Custom HTML. */
        if ($customhtml = $this->getOption('customhtml')) {
            $t->set('customhtml', $customhtml);
        }

        /* Heading. */
        if (($heading = $this->getOption('heading')) &&
            (strlen($heading) > 0)) {
            $t->set('heading', $heading);
        }

        /* New mailbox entry. */
        if ($this->getOption('new_mbox') &&
            ($injector->getInstance('Horde_Core_Perms')->hasAppPermission('create_folders') &&
             $injector->getInstance('Horde_Core_Perms')->hasAppPermission('max_folders'))) {
            $t->set('new_mbox', true);
        }

        /* Virtual folders. */
        if ($this->getOption('inc_vfolder')) {
            $imp_search = $injector->getInstance('IMP_Search');
            $vfolder_list = array();

            $imp_search->setIteratorFilter(IMP_Search::LIST_VFOLDER);
            foreach ($imp_search as $val) {
                $form_to = IMP_Mailbox::formTo($val);
                $vfolder_list[] = array(
                    'l' => $filter->filter($val->label, 'space2html', array('encode' => true)),
                    'sel' => !empty($this->_nodes[$form_to]['selected']),
                    'v' => $form_to
                );
            }

            if (!empty($vfolder_list)) {
                $t->set('vfolder', $vfolder_list);
            }
        }

        /* Add the list of editable tasklists to the list. */
        if ($this->getOption('inc_tasklists') &&
            $GLOBALS['session']->get('imp', 'tasklistavail')) {
            try {
                $tasklists = $registry->call('tasks/listTasklists', array(false, Horde_Perms::EDIT));

                if (count($tasklists)) {
                    $tasklist_list = array();
                    foreach ($tasklists as $id => $tasklist) {
                        $tasklist_list[] = array(
                            'l' => $filter->filter($tasklist->get('name'), 'space2html', array('encode' => true)),
                            'v' => IMP_Mailbox::formTo(IMP::TASKLIST_EDIT . $id)
                        );
                    }
                    $t->set('tasklist', $tasklist_list);
                }
            } catch (Horde_Exception $e) {}
        }

        /* Add the list of editable notepads to the list. */
        if ($this->getOption('inc_notepads') &&
            $GLOBALS['session']->get('imp', 'notepadavail')) {
            try {
                $notepads = $registry->call('notes/listNotepads', array(false, Horde_Perms::EDIT));

                if (count($notepads)) {
                    foreach ($notepads as $id => $notepad) {
                        $notepad_list[] = array(
                            'l' => $filter->filter($notepad->get('name'), 'space2html', array('encode' => true)),
                            'v' => IMP_Mailbox::formTo(IMP::NOTEPAD_EDIT . $id)
                        );
                    }
                    $t->set('notepad', $notepad_list);
                }
            } catch (Horde_Exception $e) {}
        }

        /* Prepare filter list. */
        $this->_filter = ($filter = $this->getOption('filter'))
            ? array_flip($filter)
            : array();

        $tree = '';
        foreach ($this->_tree->getRootNodes() as $node_id) {
            $tree .= $this->_buildTree($node_id);
        }
        $t->set('tree', $tree);

        return $t->fetch(IMP_TEMPLATES . '/imp/flist/flist.html');
    }

    /**
     */
    protected function _buildTree($node_id)
    {
        if (isset($this->_filter[$node_id])) {
            return '';
        }

        $node = &$this->_nodes[$node_id];

        if ($abbrev = $this->getOption('abbrev')) {
            $orig_label = $node['label'];
            $node['label'] = Horde_String::abbreviate($node['orig_label'], $abbrev - ($node['indent'] * 2));
        } else {
            $orig_label = null;
        }

        /* Ignore container elements. */
        if (!$this->getOption('container_select') &&
            !empty($node['container'])) {
            if (!empty($node['vfolder'])) {
                return '';
            }
            $node_id = '';
            $this->_nodes[$node_id] = $node;
        }

        $out = parent::_buildTree($node_id);

        if ($orig_label) {
            $node['label'] = $orig_label;
        }

        return $out;
    }

}
