<?php
/**
 * The IMP_Tree_Flist class provides an IMP dropdown folder list.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html GPL
 * @package  IMP
 */
class IMP_Tree_Flist extends Horde_Tree_Select
{
    /**
     * Allowed parameters for nodes.
     *
     * @var array
     */
    protected $_allowed = array(
        'orig_label',
        'selected'
    );

    /**
     * Filter list.
     *
     * @var array
     */
    protected $_filter = array();

    /**
     * Constructor.
     *
     * @param string $name   The name of this tree instance.
     * @param array $params  Additional parameters.
     * <pre>
     * 'abbrev' - (integer) Abbreviate long mailbox names by replacing the
     *            middle of the name with '...'? Value is the total length
     *            of the string.
     *            DEFAULT: 30
     * 'filter' - (array) An array of mailboxes to ignore.
     *            DEFAULT: Display all
     * 'heading' - (string) The label for an empty-value option at the top of
     *             the list.
     *             DEFAULT: ''
     * 'inc_notepads' - (boolean) Include user's editable notepads in list?
     *                   DEFAULT: No
     * 'inc_tasklists' - (boolean) Include user's editable tasklists in list?
     *                   DEFAULT: No
     * 'inc_vfolder' - (boolean) Include user's virtual folders in list?
     *                   DEFAULT: No
     * 'new_folder' - (boolean) Display an option to create a new folder?
     *                DEFAULT: No
     * </pre>
     */
    public function __construct($name, array $params = array())
    {
        $params = array_merge(array(
            'abbrev' => 30
        ), $params);

        parent::__construct($name, $params);
    }

    /**
     * Returns the tree.
     *
     * @param boolean $static  If true the tree nodes can't be expanded and
     *                         collapsed and the tree gets rendered expanded.
     *                         This option has no effect in this driver.
     *
     * @return string  The HTML code of the rendered tree.
     */
    public function getTree($static = false)
    {
        global $conf, $injector, $registry;

        $this->_buildIndents($this->_root_nodes);

        $filter = $injector->createInstance('Horde_Text_Filter');
        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        /* Heading. */
        if (($heading = $this->getOption('heading')) &&
            (strlen($heading) > 0)) {
            $t->set('heading', $heading);
        }

        /* New folder entry. */
        if ($this->getOption('new_folder') &&
            (!empty($conf['hooks']['permsdenied']) ||
             ($injector->getInstance('Horde_Perms')->hasAppPermission('create_folders') &&
              $injector->getInstance('Horde_Perms')->hasAppPermission('max_folders')))) {
            $t->set('new_mbox', true);
        }

        /* Virtual folders. */
        if ($this->getOption('inc_vfolder')) {
            $imp_search = $injector->getInstance('IMP_Search');
            $vfolder_list = array();

            $imp_search->setIteratorFilter(IMP_Search::LIST_VFOLDER);
            foreach ($imp_search as $val) {
                $vfolder_list[] = array(
                    'l' => $filter->filter($val->label, 'space2html', array('encode' => true)),
                    'sel' => (IMP::$mailbox == strval($val)),
                    'v' => IMP::formMbox(strval($val), true)
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
                            'v' => IMP::formMbox(IMP::TASKLIST_EDIT . $id, true)
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
                    $notepad_list[] = array();
                    foreach ($notepads as $id => $notepad) {
                        $notepad_list[] = array(
                            'l' => $filter->filter($notepad->get('name'), 'space2html', array('encode' => true)),
                            'v' => IMP::formMbox(IMP::NOTEPAD_EDIT . $id, true)
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
        foreach ($this->_root_nodes as $node_id) {
            $tree .= $this->_buildTree($node_id);
        }
        $t->set('tree', $tree);

        return $t->fetch(IMP_TEMPLATES . '/imp/flist/flist.html');
    }

    /**
     * Recursive function to walk through the tree array and build the output.
     *
     * @param string $node_id  The Node ID.
     *
     * @return string  The tree rendering.
     */
    protected function _buildTree($node_id)
    {
        if (isset($this->_filter[$node_id])) {
            return '';
        }

        if ($abbrev = $this->getOption('abbrev')) {
            $node = &$this->_nodes[$node_id];
            $orig_label = $node['label'];
            $node['label'] = Horde_String::abbreviate($node['orig_label'], $abbrev - ($node['indent'] * 2));
        } else {
            $orig_label = null;
        }

        $out = parent::_buildTree($node_id);

        if ($orig_label) {
            $node['label'] = $orig_label;
        }

        return $out;
    }

}
