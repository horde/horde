<?php
/**
 * This class provides a data structure for a search query.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 *
 * @property boolean $canEdit  Can this query be edited?
 * @property string $id  The query ID.
 * @property string $label  The query label.
 * @property array $mboxes  The list of mailboxes to query. This list
 *                          automatically expands subfolder searches.
 * @property array $mbox_list  The list of individual mailboxes to query (no
 *                             subfolder mailboxes).
 * @property string $mid  The query ID with the search mailbox prefix.
 * @property array $query  The list of IMAP queries that comprise this search.
 *                         Keys are mailbox names, values are
 *                         Horde_Imap_Client_Search_Query objects.
 * @property string $querytext  The textual representation of the query.
 * @property array $subfolder_list  The list of mailboxes to do subfolder
 *                                  queries for. The subfolders are not
 *                                  expanded.
 */
class IMP_Search_Query implements Serializable
{
    /* Serialized version. */
    const VERSION = 1;

    /* Prefix indicating subfolder search. */
    const SUBFOLDER = "sub\0";

    /**
     * Is this query enabled?
     *
     * @var boolean
     */
    public $enabled = true;

    /**
     * Cache results.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * Can this query be edited?
     *
     * @var boolean
     */
    protected $_canEdit = true;

    /**
     * The search criteria (IMP_Search_Element objects).
     *
     * @var array
     */
    protected $_criteria = array();

    /**
     * The search ID.
     *
     * @var string
     */
    protected $_id;

    /**
     * The virtual folder label.
     *
     * @var string
     */
    protected $_label;

    /**
     * The mailbox list.
     *
     * @var array
     */
    protected $_mboxes = array();

    /**
     * List of serialize entries not to save.
     *
     * @var array
     */
    protected $_nosave = array();

    /**
     * Constructor.
     *
     * @var array $opts  Options:
     * <pre>
     * add - (array) A list of criteria to add (Horde_Search_Element objects).
     *       DEFAULT: No criteria explicitly added.
     * disable - (boolean) Disable this query?
     *           DEFAULT: false
     * id - (string) Use this ID.
     *      DEFAULT: ID automatically generated.
     * label - (string) The label for this query.
     *         DEFAULT: Search Results
     * mboxes - (array) The list of mailboxes to search.
     *          DEFAULT: None
     * subfolders - (array) The list of mailboxes to do subfolder searches
     *              for.
     *              DEFAULT: None
     * </pre>
     */
    public function __construct(array $opts = array())
    {
        $this->enabled = empty($opts['disable']);
        if (isset($opts['add'])) {
            foreach ($opts['add'] as $val) {
                $this->add($val);
            }
        }

        $this->_id = isset($opts['id'])
            ? $opts['id']
            : strval(new Horde_Support_Randomid());

        $this->_label = isset($opts['label'])
            ? $opts['label']
            : _("Search Results");

        if (isset($opts['mboxes'])) {
            $this->_mboxes = $opts['mboxes'];
        }

        if (isset($opts['subfolders'])) {
            foreach ($opts['subfolders'] as $val) {
                $this->_mboxes[] = self::SUBFOLDER . $val;
            }
        }

        natsort($this->_mboxes);
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'canEdit':
            return $this->_canEdit;

        case 'criteria':
            $out = array();
            foreach ($this->_criteria as $elt) {
                $out[] = array(
                    'criteria' => $elt->getCriteria(),
                    'element' => get_class($elt)
                );
            }
            return $out;

        case 'id':
            return $this->_id;

        case 'label':
            return $this->_label;

        case 'mboxes':
            if (!isset($this->_cache['mboxes'])) {
                $out = $this->mbox_list;

                if ($s_list = $this->subfolder_list) {
                    $imp_folder = $GLOBALS['injector']->getInstance('IMP_Folder');
                    foreach ($s_list as $val) {
                        $out = array_merge($out, $val->subfolders);
                    }
                }

                // TODO: array_unique() for objects (requires 5.2.9)
                $this->_cache['mboxes'] = $out;
            }

            return $this->_cache['mboxes'];

        case 'mbox_list':
        case 'subfolder_list':
            if (!isset($this->_cache['mbox_list'])) {
                $mbox = $subfolder = array();

                foreach ($this->_mboxes as $val) {
                    if (strpos($val, self::SUBFOLDER) === 0) {
                        $subfolder[] = IMP_Mailbox::get(substr($val, strlen(self::SUBFOLDER)));
                    } else {
                        $mbox[] = IMP_Mailbox::get($val);
                    }
                }

                $this->_cache['mbox_list'] = $mbox;
                $this->_cache['subfolder_list'] = $subfolder;
            }

            return $this->_cache[$name];

        case 'mid':
            return IMP_Search::MBOX_PREFIX . $this->_id;

        case 'query':
            $qout = array();

            foreach ($this->mboxes as $mbox) {
                $query = new Horde_Imap_Client_Search_Query();
                foreach ($this->_criteria as $elt) {
                    $query = $elt->createQuery($mbox, $query);
                }
                $qout[strval($mbox)] = $query;
            }

            return $qout;

        case 'querytext':
            $text = array(_("Search"));

            foreach ($this->_criteria as $elt) {
                $text[] = $elt->queryText();
                if (!($elt instanceof IMP_Search_Element_Or)) {
                    $text[] = _("and");
                }
            }
            array_pop($text);

            $mbox_display = array();
            foreach ($this->mboxes as $val) {
                $mbox_display[] = $val->display;
            }

            return implode(' ', $text) . ' ' . _("in") . ' [' . implode(', ', $mbox_display) . ']';
        }
    }

    /**
     * String representation of this object: the mailbox ID.
     *
     * @return string  Mailbox ID.
     */
    public function __toString()
    {
        return $this->mid;
    }

    /**
     * Add a search query element.
     *
     * @param IMP_Search_Element $elt  The search element to add.
     */
    public function add(IMP_Search_Element $elt)
    {
        $this->_criteria[] = $elt;
    }

    /* Serializable methods. */

    /**
     * Serialization.
     *
     * @return string  Serialized data.
     */
    public function serialize()
    {
        $data = array_filter(array(
            'c' => $this->_criteria,
            'e' => intval($this->enabled),
            'i' => $this->_id,
            'l' => $this->_label,
            'm' => $this->_mboxes,
            'v' => self::VERSION
        ));

        foreach ($this->_nosave as $val) {
            unset($data[$val]);
        }

        return serialize($data);
    }

    /**
     * Unserialization.
     *
     * @param string $data  Serialized data.
     *
     * @throws Exception
     */
    public function unserialize($data)
    {
        $data = unserialize($data);
        if (!is_array($data) ||
            !isset($data['v']) ||
            ($data['v'] != self::VERSION)) {
            throw new Exception('Cache version change');
        }

        if (isset($data['c'])) {
            $this->_criteria = $data['c'];
        }
        $this->enabled = !empty($data['e']);
        if (isset($data['i'])) {
            $this->_id = $data['i'];
        }
        if (isset($data['l'])) {
            $this->_label = $data['l'];
        }
        if (isset($data['m'])) {
            $this->_mboxes = $data['m'];
        }
    }

}
