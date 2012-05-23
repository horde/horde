<?php
/**
 * Class to attach PHP actions to javascript elements.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
abstract class Horde_Core_Ajax_Imple
{
    /**
     * DOM ID incrementer. Shared by all Imple instances.
     *
     * @var integer
     */
    static protected $_domid = 0;

    /**
     * Does this imple require authentication?
     *
     * @var boolean
     */
    protected $_auth = true;

    /**
     * Has this imple been initialized?
     *
     * @var boolean
     */
    protected $_init = false;

    /**
     * Parameters needed by the subclasses.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * The javascript event to observe.
     *
     * @var string
     */
    protected $_observe = 'click';

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     *   - id: (string) [OPTIONAL] The DOM ID to attach to.
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['id'])) {
            $params['id'] = 'horde_imple' . self::$_domid++;
        }

        $this->_params = $params;
    }

    /**
     * Attach the object to a javascript event.
     */
    public function attach()
    {
        global $page_output;

        $result = $this->_attach(!$this->_init);

        if (!$this->_init) {
            $page_output->ajax = true;
            $page_output->addScriptFile('imple.js', 'horde');
            $this->_init = true;
        }

        if ($result === false) {
            return;
        }

        $args = array(
            'id' => $this->getDomId(),
            'observe' => $this->_observe
        );

        $args['params'] = is_array($result)
            ? array_merge($result, $this->_impleParams())
            : $result;

        $page_output->addInlineScript(array(
            'HordeImple.add(' .
                Horde_Serialize::serialize($args, Horde_Serialize::JSON) .
            ')'
        ), true);
    }

    /**
     * Imple handler.
     *
     * @param Horde_Variables $vars  A variables object.
     *
     * @return mixed  Data to return to the browser.
     */
    public function handle(Horde_Variables $vars)
    {
        return ($this->_auth && !$GLOBALS['registry']->getAuth())
            ? false
            : $this->_handle($vars);
    }

    /**
     * Return the DOM ID this Imple is attached to.
     *
     * @return string DOM ID.
     */
    public function getDomId()
    {
        return $this->_params['id'];
    }

    /**
     * The URL to the imple AJAX endpoint. This should only be used if the
     * javascript code.
     *
     * @return Horde_Url  URL to the AJAX endpoint.
     */
    final public function getImpleUrl()
    {
        $url = $GLOBALS['registry']->getServiceLink('ajax')->add($this->_impleParams());
        $url->url .= 'imple';

        return $url;
    }

    /**
     * Add the necessary parameters to the imple AJAX request.
     *
     * @return array  URL parameter hash.
     */
    final protected function _impleParams()
    {
        return array(
            'app' => $GLOBALS['registry']->getApp(),
            'imple' => get_class($this)
        );
    }

    /**
     * Javascript code to run before the action is sent to the AJAX endpoint.
     * e.memo contains the list of URL parameters.
     *
     * @param string $js  JS code to run.
     */
    protected function _jsOnDoAction($js)
    {
        $GLOBALS['page_output']->addInlineScript(array(
            'document.observe("' . get_class($this) . ':do", function(e) {' .
            $js . '});'
        ));
    }

    /**
     * Javascript code to run on a successfult AJAX response.
     * e.memo contains the AJAX response.
     *
     * @param string $js  JS code to run.
     */
    protected function _jsOnComplete($js)
    {
        $GLOBALS['page_output']->addInlineScript(array(
            'document.observe("' . get_class($this) . ':complete", function(e) {' .
            $js . '});'
        ));
    }

    /**
     * Attach the object to a javascript event.
     *
     * @param boolean $init  Is this the first time this imple has been
     *                       initialized?
     *
     * @return mixed  An array of javascript parameters. If false, the imple
     *                handler will ignore this instance (calling code will be
     *                responsible for calling imple endpoint).
     */
    abstract protected function _attach($init);

    /**
     * Imple handler.
     *
     * @param Horde_Variables $vars  A variables object.
     *
     * @return mixed  Data to return to the browser.
     */
    abstract protected function _handle(Horde_Variables $vars);

}
