<?php
class Horde_Ui_Layout
{
    protected $_view;
    protected $_layoutName;

    public function setView(Horde_View $view)
    {
        $this->_view = $view;
    }

    public function setLayoutName($layoutName)
    {
        $this->_layoutName = $layoutName;
    }

    public function render($name)
    {
        $this->_view->contentForLayout = $this->_view->render($name);
        return $this->_view->render($this->_layoutName);
    }
}
