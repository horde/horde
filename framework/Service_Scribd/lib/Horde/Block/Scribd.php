<?php
/**
 * @package Horde_Block
 */
class Horde_Block_Horde_Scribd extends Horde_Block {

    var $_app = 'horde';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return Horde_Service_Scribd_Translation::t("Scribd Documents");
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
