<?php
/**
 */
class Horde_Scribd_Block_Scribd extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->_name = Horde_Service_Scribd_Translation::t("Scribd Documents");
    }

    /**
     */
    public function _content()
    {
        if (!is_array($this->_docs)) {
            return '';
        }

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
    }

}
