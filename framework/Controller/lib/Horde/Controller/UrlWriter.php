<?php
/**
 * URL generation utility for controllers
 *
 * @category Horde
 * @package  Horde_Controller
 * @author   Mike Naberezny <mike@maintainable.com>
 * @author   Derek DeVries <derek@maintainable.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */
class Horde_Controller_UrlWriter
{
    /**
     * Defaults to merge into route parameters when not using named routes.
     * @var array
     */
    protected $_defaults;

    /**
     * @var Horde_Routes_Util
     */
    protected $_utils;

    /**
     * Class constructor
     *
     * @param  Horde_Routes_Utils  $utils     Route utilities
     * @param  array               $defaults  Defaults to merge for urlFor()
     */
    public function __construct(Horde_Routes_Utils $utils, $defaults = array())
    {
        $this->_utils = $utils;
        $this->_defaults = $defaults;
    }

    /**
     * Generate a URL.  Same signature as Horde_Routes_Utils->urlFor().
     *
     * @param  $first   mixed
     * @param  $second  mixed
     * @return string
     */
    public function urlFor($first, $second = array())
    {
        // anonymous route: serialize to params & merge defaults
        //   urlFor(array('controller' => 'books'))
        if (is_array($first)) {
            $first = array_merge($this->_defaults,
                                 $this->_serializeToParams($first));
        }

        // named route: serialize to params only (no merge)
        //   urlFor('notes', array('action' => 'show', 'id' => 1))
        if (is_array($second)) {
            $second = $this->_serializeToParams($second);
        }

        // url generation "route memory" is not useful here
        $this->_utils->mapperDict = array();

        // generate url
        return $this->_utils->urlFor($first, $second);
    }

    /**
     * Serialize any objects in the collection supporting toParam() before
     * passing the collection to Horde_Routes.
     *
     * @param  array  $collection
     * @param  array
     */
    protected function _serializeToParams($collection)
    {
        foreach ($collection as &$value) {
            if (is_object($value) && method_exists($value, 'toParam')) {
                $value = $value->toParam();
            }
        }
        return $collection;
    }
}
