<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Generate a select form HTML input tag.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_Select
{
    /**
     * Generated tree.
     *
     * @var Horde_Tree
     */
    protected $_tree;

    /**
     * Constructor.
     *
     * @param array $opts  Optional parameters:
     *   - abbrev: (boolean) Abbreviate long mailbox names by replacing the
     *             middle of the name with '...'?
     *             DEFAULT: Yes
     *   - basename: (boolean)  Use raw basename instead of abbreviated label?
     *               DEFAULT: false
     *   - heading: (string) The label for an empty-value option at the top of
     *              the list.
     *              DEFAULT: ''
     *   - inc_notepads: (boolean) Include user's editable notepads in list?
     *                   DEFAULT: No
     *   - inc_tasklists: (boolean) Include user's editable tasklists in list?
     *                    DEFAULT: No
     *   - inc_vfolder: (boolean) Include user's virtual folders in list?
     *                  DEFAULT: No
     *   - iterator: (Iterator) Tree iterator to use.
     *   - new_mbox: (boolean) Display an option to create a new mailbox?
     *               DEFAULT: No
     *   - optgroup: (boolean) Whether to use <optgroup> elements to group
     *               mailbox types.
     *               DEFAULT: false
     *   - selected: (string) The mailbox to have selected by default.
     *               DEFAULT: None
     */
    public function __construct(array $opts = array())
    {
        global $injector;

        $this->_tree = $injector->getInstance('IMP_Ftree')->createTree(strval(new Horde_Support_Randomid()), array(
            'basename' => !empty($opts['basename']),
            'iterator' => empty($opts['iterator']) ? null : $opts['iterator'],
            'render_type' => 'IMP_Tree_Flist'
        ));

        if (!empty($opts['selected'])) {
            $this->_tree->addNodeParams(IMP_Mailbox::formTo($opts['selected']), array('selected' => true));
        }

        $this->_tree->setOption($opts);
    }

    /**
     * The string tree representation.
     * NOTE: The &lt;select&gt; and &lt;/select&gt; tags are NOT included in
     * the output.
     *
     * @return string  A string containing <option> elements for each mailbox
     *                 in the list.
     */
    public function __toString()
    {
        return $this->_tree->getTree();
    }

}
