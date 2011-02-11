<?php
/**
 * @package Skeleton
 */
class Skeleton_Block_Example extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->_name = _("Example Block");
    }

    /**
     */
    protected function _params()
    {
        return array(
            'color' => array(
                'type' => 'text',
                'name' => _("Color"),
                'default' => '#ff0000'
            )
        );
    }

    /**
     */
    protected function _title()
    {
        return _("Color");
    }

    /**
     */
    protected function _content()
    {
        $html  = '<table width="100" height="100" bgcolor="%s">';
        $html .= '<tr><td>&nbsp;</td></tr>';
        $html .= '</table>';

        return sprintf($html, $this->_params['color']);
    }

}
