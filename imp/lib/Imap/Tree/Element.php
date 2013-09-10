<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A tree element.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read IMP_Imap_Tree_Account $account  Account object for this
 *                                                element.
 * @property-read boolean $base_elt  True if this is the base element.
 * @property-read boolean $children  True if this element has children.
 * @property-read array $child_list  The list of the element's children.
 * @property boolean $container  True if this element is a container.
 * @property-read boolean $inbox  True if this is the INBOX.
 * @property boolean $invisible  True if this element is invisible.
 * @property-read integer $level  The tree level of the current element.
 * @property-read IMP_Mailbox $mbox_ob  The IMP_Mailbox object for this
 *                                      element.
 * @property-read boolean $namespace  True if this is a namespace container
 *                                    element.
 * @property-read array $namespace_info  Namespace info.
 * @property boolean $needsort  True if this level needs a sort.
 * @property-read boolean $nochildren  True if this element doesn't allow
 *                                     children.
 * @property-read boolean $nonimap  True if this is a non-IMAP element.
 * @property boolean $open  True if this element is open (a/k/a expanded).
 * @property-read IMP_Imap_Tree_Element $parent  The parent element (null if
 *                                               not found).
 * @property boolean $polled  True if this element is polled.
 * @property-read boolean $remote  True if this is a remote container.
 * @property-read boolean $remote_auth  True if this is a remote account that
 *                                      has been authenticated.
 * @property-read boolean $remote_mbox  True if this is a remote mailbox.
 * @property boolean $subscribed  True if the element is subscribed.
 * @property-read boolean $vfolder  True if this element is a virtual folder.
 */
class IMP_Imap_Tree_Element
{
    /**
     * The element ID.
     *
     * @var string
     */
    protected $_id;

    /**
     * IMP tree object.
     *
     * @var IMP_Imap_Tree
     */
    protected $_tree;

    /**
     * Constructor.
     *
     * @param string $id           Element ID.
     * @param IMP_Imap_Tree $tree  The base tree object.
     */
    public function __construct($id, IMP_Imap_Tree $tree)
    {
        $this->_id = $id;
        $this->_tree = $tree;
    }

    /**
     */
    public function __sleep()
    {
        throw new LogicException('Object can not be serialized.');
    }

    /**
     * @return string  Element ID.
     */
    public function __toString()
    {
        return $this->_id;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'account':
            return $this->_tree->getAccount($this->_id);

        case 'base_elt':
            return ($this->_id == IMP_Imap_Tree::BASE_ELT);

        case 'child_list':
            return $this->_tree->getChildren($this->_id);

        case 'inbox':
            return ($this->_id == 'INBOX');

        case 'level':
            return ($this->base_elt)
                ? 0
                : substr_count($this->_id, $this->namespace_info['delimiter']);

        case 'mbox_ob':
            return IMP_Mailbox::get($this->_id);

        case 'namespace_info':
            return $this->mbox_ob->imp_imap->getNamespace($this->_id);

        case 'parent':
            return $this->_tree->getParent($this->_id);

        default:
            return $this->_tree->getAttribute($name, $this->_id);
        }
    }

    /**
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'container':
        case 'invisible':
        case 'needsort':
        case 'open':
        case 'polled':
        case 'subscribed':
            $this->_tree->setAttribute($name, $this->_id, $value);
            break;
        }
    }

}
