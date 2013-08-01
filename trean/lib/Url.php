<?php
class Trean_Url
{
    protected $_url;

    public function __construct($url = '')
    {
        $this->_url = new Horde_Url($url);
        $this->_url->remove(array(
            'utm_source',
            'utm_medium',
            'utm_term',
            'utm_campaign',
            'utm_content',
        ));
    }

    public function __toString()
    {
        return (string)$this->_url;
    }
}
