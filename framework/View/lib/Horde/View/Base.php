<?php
/**
 * @category Horde
 * @package Horde_View
 */

/**
 * Abstract base class for Horde_View to get private constructs out of
 * template scope.
 *
 * @category Horde
 * @package Horde_View
 */
abstract class Horde_View_Base
{
    /**
     * @var string
     */
    public static $defaultFormBuilder = 'Horde_View_Helper_Form_Builder';

    /**
     * Path stack for templates.
     *
     * @var array
     */
    private $_templatePath = array('./');

    /**
     * Template to execute. Stored in a private variable to keep it
     * out of the public view scope.
     *
     * @var string
     */
    private $_file = null;

    /**
     * Cache of helper objects.
     *
     * @var array
     */
    private $_helpers = array();

    /**
     * Encoding to use in escaping mechanisms; defaults to UTF-8.
     * @var string
     */
    private $_encoding = 'UTF-8';

    /**
     * Should we throw an error if helper methods collide?
     * @var boolean
     */
    private $_throwOnHelperCollision = false;

    /**
     * Protected properties
     * @var array
     */
    private $_protectedProperties;

    /**
     * Constructor.
     *
     * @param array $config Configuration key-value pairs.
     */
    public function __construct($config = array())
    {
        // encoding
        if (!empty($config['encoding'])) {
            $this->setEncoding($config['encoding']);
        }

        // user-defined template path
        if (!empty($config['templatePath'])) {
            $this->addTemplatePath($config['templatePath']);
        }

        $this->_protectedProperties = get_class_vars(__CLASS__);
    }

    /**
     * Undefined variables return null.
     *
     * @return null
     */
    public function __get($name)
    {
        return null;
    }

    /**
     * Accesses a helper object from within a template.
     *
     * @param string $method The helper method.
     * @param array $args The parameters for the helper.
     *
     * @return string The result of the helper method.
     */
    public function __call($method, $args)
    {
        if (isset($this->_helpers[$method])) {
            return call_user_func_array(array($this->_helpers[$method], $method), $args);
        }

        throw new Horde_View_Exception('Helper for ' . $method . ' not found.');
    }

    /**
     * Adds to the stack of template paths in LIFO order.
     *
     * @param string|array The directory (-ies) to add.
     */
    public function addTemplatePath($path)
    {
        foreach ((array)$path as $dir) {
            // Attempt to strip any possible separator and append the
            // system directory separator.
            $dir = rtrim($dir, '\\/' . DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR;

            // Add to the top of the stack.
            array_unshift($this->_templatePath, $dir);
        }
    }

    /**
     * Resets the stack of template paths.
     *
     * To clear all paths, use Horde_View::setTemplatePath(null).
     *
     * @param string|array The directory (-ies) to set as the path.
     */
    public function setTemplatePath($path)
    {
        $this->_templatePath = array();
        $this->addTemplatePath($path);
    }

    /**
     * Return the template paths
     *
     * @return array
     */
    public function getTemplatePaths()
    {
        return $this->_templatePath;
    }

    /**
     * Adds to the stack of helpers in LIFO order.
     *
     * @param Horde_View_Helper $helper The helper instance to add. If this is a
     * string instead of a Helper instance, then it will be treated as a class
     * name. Names without "_" and that do not have "Helper" in them will be
     * prefixed with Horde_View_Helper_; other names will be treated as literal
     * class names. Examples:
     *
     *   $v->addHelper('Tag');        // Adds a new Horde_View_Helper_Tag to the view
     *   $v->addHelper('AppHelper');  // Adds a new AppHelper object to the view if
     *                                // it exists, otherwise throws an exception.
     *
     * @return Horde_View_Helper  Returns the helper object that was added.
     */
    public function addHelper($helper)
    {
        if (is_string($helper)) {
            if (strpos($helper, '_') === false && strpos($helper, 'Helper') === false) {
                $class = 'Horde_View_Helper_' . $helper;
            } else {
                $class = $helper;
            }
            if (!class_exists($class)) {
                throw new Horde_View_Exception('Helper class ' . $helper . ' not found');
            }
            $helper = new $class($this);
        }

        foreach (get_class_methods($helper) as $method) {
            if (isset($this->_helpers[$method])) {
                $msg = 'Helper method ' . get_class($this->_helpers[$method]) . '#' . $method . ' overridden by ' . get_class($helper) . '#' . $method;
                if ($this->_throwOnHelperCollision) { throw new Horde_View_Exception($msg); }
                if ($this->logger)                  { $this->logger->warn($msg); }
            }
            $this->_helpers[$method] = $helper;
        }

        return $helper;
    }

    /**
     * Assigns multiple variables to the view.
     *
     * The array keys are used as names, each assigned their
     * corresponding array value.
     *
     * @param array $array The array of key/value pairs to assign.
     *
     * @see __set()
     */
    public function assign($array)
    {
        foreach ($array as $key => $val) {
            if (isset($this->_protectedProperties[$key])) {
                throw new Horde_View_Exception("Can't overwrite internal variables in assign()");
            }
            $this->$key = $val;
        }
    }

    /**
     * Processes a template and returns the output.
     *
     * @param string $name The template to process.
     *
     * @return string The template output.
     */
    public function render($name, $locals = array())
    {
        // render partial
        if (is_array($name) && $partial = $name['partial']) {
            unset($name['partial']);
            return $this->renderPartial($partial, $name);
        }

        // Find the template file name.
        $this->_file = $this->_template($name);

        // remove $name from local scope
        unset($name);

        ob_start();
        $this->_run($this->_file, $locals);
        return ob_get_clean();
    }

    /**
     * Render a partial template. Partial template filenames are named with a
     * leading underscore, although this underscore is not used when specifying
     * the name of the partial.
     *
     * we would reference the file /views/shared/_sidebarInfo.html in our
     * template using:
     *
     * <code>
     *   <div>
     *   <?php echo $this->renderPartial('sidebarInfo') ?>
     *   </div>
     * </code>
     *
     * @param   string  $name
     * @param   array   $options
     *
     * @return  string  The template output
     */
    public function renderPartial($name, $options = array())
    {
        // pop name off of the path
        $parts = strstr($name, '/') ? explode('/', $name) : array($name);
        $name = array_pop($parts);
        $path = implode('/', $parts)."/";

        // check if they passed in a collection before validating keys
        $useCollection = array_key_exists('collection', $options);

        $valid = array('object' => null, 'locals' => array(), 'collection' => array());
        $options = array_merge($valid, $options);
        $locals = array($name => null);

        // set the object variable
        if ($options['object']) {
            $locals[$name] = $options['object'];
        }

        // set local variables to be used in the partial
        if (isset($options['locals']) && (is_array($options['locals']) || $options['locals'] instanceof Traversable)) {
            foreach ($options['locals'] as $key => $val) {
                $locals[$key] = $val;
            }
        }

        // collection
        if ($useCollection) {
            $rendered = '';
            if (is_array($options['collection'])) {
                $sz = count($options['collection']);
                for ($i = 0; $i < $sz; $i++) {
                    $locals["{$name}Counter"] = $i;
                    $locals[$name] = $options['collection'][$i];
                    $rendered .= $this->render("{$path}_{$name}", $locals);
                }
            }

        // single render
        } else {
            $rendered = $this->render("{$path}_{$name}", $locals);
        }
        return $rendered;
    }

    /**
     * Set encoding
     *
     * @param string $encoding
     */
    public function setEncoding($encoding)
    {
        $this->_encoding = $encoding;
    }

    /**
     * Return current encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * Control the behavior when a helper method is overridden by another helper
     *
     * @param boolean $throw  Throw an exception when helper methods collide?
     */
    public function throwOnHelperCollision($throw = true)
    {
        $this->_throwOnHelperCollision = $throw;
    }

    /**
     * Finds a template from the available directories.
     *
     * @param $name string The base name of the template.
     */
    protected function _template($name)
    {
        // append missing html
        if (!strstr($name, '.')) { $name .= '.html.php'; }

        if (!count($this->_templatePath)) {
            throw new Horde_View_Exception('No template directory set; unable to locate ' . $name);
        }

        foreach ($this->_templatePath as $dir) {
            if (is_readable($dir . $name)) {
                return $dir . $name;
            }
        }

        throw new Horde_View_Exception("\"$name\" not found in template path (\"" . implode(':', $this->_templatePath) . '")');
    }

    /**
     * Use to include the template in a scope that only allows public
     * members.
     *
     * @param string The template to execute. Not declared in the
     * function signature so it stays out of the view's public scope.
     *
     * @param array  Any local variables to declare.
     */
    abstract protected function _run();

}
