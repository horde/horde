<?php
/**
 * Horde_Core_Ui_TagCloud:: for creating and displaying tag clouds.
 *
 * Based on a striped down version of Pear's HTML_TagCloud
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Ui_TagCloud
{
    /**
     * @var integer
     */
    public $basefontsize = 24;

    /**
     * @var integer
     */
    public $fontsizerange = 12;

    /**
     * @var string
     */
    public $css_class = 'tagcloud';

    /**
     * @var    string
     * mm,cm,in,pt,pc,px,em
     */
    public $size_suffix = 'px';

    /**
     * @var integer
     */
    public $factor;

    /**
     * @var array
     */
    public $epoc_level = array(
        'earliest',
        'earlier',
        'later',
        'latest'
    );

    /**
     * @var array
     */
    protected $_elements = array();

    /**
     * @var integer
     */
    protected $_max = 0;

    /**
     * @var integer
     */
    protected $_min = 0;

    /**
     * @var integer
     */
    protected $_max_epoc;

    /**
     * @var integer
     */
    protected $_min_epoc;

    /**
     * @var array
     */
    protected $_map = array();

    /**
     * Constructor
     *
     * @param integer $basefontsize   Base font size of output tag (option).
     * @param integer $fontsizerange  Font size range.
     */
    public function __construct($basefontsize = 24, $fontsizerange = 12)
    {
        $this->basefontsize = $basefontsize;
        $this->minfontsize = max($this->basefontsize - $this->fontsizerange, 0);
        $this->maxfontsize = $this->basefontsize + $this->fontsizerange;
    }

    /**
     * Add a Tag Element to build Tag Cloud.
     *
     * @param string $tag         TODO
     * @param string $url         TODO
     * @param integer $count      TODO
     * @param integer $timestamp  UNIX timestamp.
     * @param string $onclick     Javascript onclick event handler.
     */
    public function addElement($name, $url ='', $count = 0, $timestamp = null,
                               $onclick = null)
    {

        if (isset($this->_map[$name])) {
            $i = $this->_map[$name];
            // Increase the count
            $this->_elements[$i]['count'] += $count;

            // Keep the latest timestamp
            if (!empty($timestamp) &&
                $timestamp > $this->_elements[$i]['timestamp']) {
                $this->_elements[$i]['timestamp'] = $timestamp;
            }
            // For onclick and url we will simply overwrite the existing values
            // instead of checking if they are empty, then overwriting.
            $this->_elements[$i]['onclick'] = $onclick;
            $this->elements[$i]['url'] = $url;
        } else {
            $i = count($this->_elements);
            $this->_elements[$i]['name'] = $name;
            $this->_elements[$i]['url'] = $url;
            $this->_elements[$i]['count'] = $count;
            $this->_elements[$i]['timestamp'] = $timestamp == null ? time() : $timestamp;
            $this->_elements[$i]['onclick'] = $onclick;
            $this->_map[$name] = $i;
        }
    }

    /**
     * Add a Tag Element to build Tag Cloud.
     *
     * @param array $tags  Associative array to $this->_elements.
     */
    public function addElements($tags)
    {
        $this->_elements = array_merge($this->_elements, $tags);
    }

    /**
     * Clear Tag Elements.
     */
    public function clearElements()
    {
        $this->_elements = array();
    }

    /**
     * Build HTML part.
     *
     * @param array $param  'limit' => int limit of generation tag num.
     *
     * @return string   HTML
     */
    public function buildHTML($param = array())
    {
        return $this->_wrapDiv($this->_buidHTMLTags($param));
    }

    /**
     * Calc Tag level and create whole HTML of each Tags.
     *
     * @param array $param  Limit of Tag Number.
     *
     * @return string  HTML
     */
    protected function _buidHTMLTags($param)
    {
        $this->total = count($this->_elements);
        // no tags elements
        if ($this->total == 0) {
            return '';
        } elseif ($this->total == 1) {
            $tag = $this->_elements[0];
            return $this->_createHTMLTag($tag, 'latest', $this->basefontsize);
        }

        $limit = array_key_exists('limit', $param) ? $param['limit'] : 0;
        $this->_sortTags($limit);
        $this->_calcMumCount();
        $this->_calcMumEpoc();

        $range = $this->maxfontsize - $this->minfontsize;
        $this->factor = ($this->_max == $this->_min)
            ? 1
            : $range / (sqrt($this->_max) - sqrt($this->_min));
        $this->epoc_factor = ($this->_max_epoc == $this->_min_epoc)
            ? 1
            : count($this->epoc_level) / (sqrt($this->_max_epoc) - sqrt($this->_min_epoc));
        $rtn = array();
        foreach ($this->_elements as $tag){
            $count_lv = $this->_getCountLevel($tag['count']);
            if (!isset($tag['timestamp']) || empty($tag['timestamp'])) {
                $epoc_lv = count($this->epoc_level) - 1;
            } else {
                $epoc_lv = $this->_getEpocLevel($tag['timestamp']);
            }
            $color_type = $this->epoc_level[$epoc_lv];
            $font_size  = $this->minfontsize + $count_lv;
            $rtn[] = $this->_createHTMLTag($tag, $color_type, $font_size);
        }
        return implode('', $rtn);
    }

    /**
     * Create a Element of HTML part.
     *
     * @param array $tag         TODO
     * @param string $type       CSS class of time line param.
     * @param integer $fontsize  TODO
     *
     * @return  string a Element of Tag HTML
     */
    protected function _createHTMLTag($tag, $type, $fontsize)
    {
        return sprintf('<a style="font-size:%d%s" class="%s" href="%s"%s>%s</a>' . "\n",
                       $fontsize,
                       $this->size_suffix,
                       $type,
                       $tag['url'],
                       (empty($tag['onclick']) ? '' : ' onclick="' . $tag['onclick'] . '"'),
                       htmlspecialchars($tag['name']));
    }

    /**
     * Sort tags by name.
     *
     * @param integer $limit  Limit element number of create TagCloud.
     */
    protected function _sortTags($limit = 0)
    {
        usort($this->_elements, array($this, 'cmpElementsName'));
        if ($limit != 0){
            $this->_elements = array_splice($this->_elements, 0, $limit);
        }
    }

    /**
     * Using for usort().
     *
     * @return integer  TODO
     */
    public function cmpElementsName($a, $b)
    {
        return ($a['name'] == $b['name'])
            ? 0
            : (($a['name'] < $b['name']) ? -1 : 1);
    }

    /**
     * Calc max and min tag count of use.
     */
    protected function _calcMumCount()
    {
        foreach($this->_elements as $item){
            $array[] = $item['count'];
        }
        $this->_min = min($array);
        $this->_max = max($array);
    }

    /**
     * Calc max and min timestamp.
     */
    protected function _calcMumEpoc()
    {
        foreach($this->_elements as $item){
            $array[] = $item['timestamp'];
        }
        $this->_min_epoc = min($array);
        $this->_max_epoc = max($array);
    }

    /**
     * Calc Tag Level of size.
     *
     * @param integer $count  TODO
     *
     * @return integer  Level.
     */
    protected function _getCountLevel($count = 0)
    {
        return (int)((sqrt($count) - sqrt($this->_min)) * $this->factor);
    }

    /**
     * Calc timeline level of Tag.
     *
     * @param integer $timestamp  TODO
     *
     * @return integer  Level of timeline.
     */
    protected function _getEpocLevel($timestamp = 0)
    {
        return (int)((sqrt($timestamp) - sqrt($this->_min_epoc)) * $this->epoc_factor);
    }

    /**
     * Wrap div tag.
     *
     * @param string $html  TODO
     *
     * @return string  TODO
     */
    protected function _wrapDiv($html)
    {
        return ($html == '')
            ? ''
            : sprintf("<div class=\"%s\">\n%s</div>\n", $this->css_class, $html);
    }

}
