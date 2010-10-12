<?php
/**
 * @package Horde_Block
 */
class Horde_Block_Horde_Scribd extends Horde_Block {

    var $_app = 'horde';

    /**
     * Translation provider.
     *
     * @var Horde_Translation
     */
    protected $_localDict;

    /**
     * Constructor.
     *
     * @param array|boolean $params  Any parameters the block needs. If false,
     *                               the default parameter will be used.
     * @param integer $row           The block row.
     * @param integer $col           The block column.
     * @param Horde_Translation $dict  A translation handler implementing
     *                                 Horde_Translation.
     */
    public function __construct($params = array(), $row = null, $col = null,
                                $dict = null)
    {
        parent::__construct($params, $row, $col, $dict);
        if ($dict) {
            $this->_localDict = $dict;
        } else {
            $this->_localDict = new Horde_Translation_Gettext('Horde_Service_Scribd', dirname(__FILE__) . '/../../../locale');
        }
    }

    function _params()
    {
    }

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return $this->_localDict->t("Scribd Documents");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        $docs = $this->_list();
        if (is_array($this->_docs)) {
            $html = '';
            $count = 0;
            foreach ($this->_feed as $entry) {
                if ($count++ > $this->_params['limit']) {
                    break;
                }
                $html .= '<a href="' . $entry->link. '"';
                if (empty($this->_params['details'])) {
                    $html .= ' title="' . htmlspecialchars(strip_tags($entry->description())) . '"';
                }
                $html .= '>' . htmlspecialchars($entry->title) . '</a>';
                if (!empty($this->_params['details'])) {
                    $html .= '<br />' .  htmlspecialchars(strip_tags($entry->description())). "<br />\n";
                }
                $html .= '<br />';
            }
            return $html;
        } elseif (is_string($docs)) {
            return $docs;
        } else {
            return '';
        }
    }

    function _list()
    {
    }

}
