<?php
/**
 * Horde Routes package
 *
 * This package is heavily inspired by the Python "Routes" library
 * by Ben Bangert (http://routes.groovie.org).  Routes is based
 * largely on ideas from Ruby on Rails (http://www.rubyonrails.org).
 *
 * @author  Maintainable Software, LLC. (http://www.maintainable.com)
 * @author  Mike Naberezny (mike@maintainable.com)
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * @package Horde_Routes
 */

/**
 * Pretty-print a listing of routes connected to a mapper.
 *
 * @package Horde_Routes
 */
class Horde_Routes_Printer
{
    /**
     * @var Horde_Routes_Mapper
     */
    protected $_mapper;

    /**
     * Constructor.
     *
     * @param  Horde_Routes_Mapper  $mapper  Mapper to analyze for printing
     */
    public function __construct($mapper)
    {
        $this->_mapper = $mapper;
    }

    /**
     * Pretty-print a listing of the routes connected to the mapper.
     *
     * @param  stream|null  $stream  Output stream for printing (optional)
     * @param  string|null  $eol     Line ending (optional)
     * @return void
     */
    public function printRoutes($stream = null, $eol = PHP_EOL)
    {
        $routes = $this->getRoutes();
        if (empty($routes)) { return; }

        if ($stream === null) {
            $stream = fopen('php://output', 'a');
        }
  
        // find the max $widths to size the output columns {'name'=>40, 'method'=>6, ...}
        $widths = array();
        foreach (array_keys($routes[0]) as $key) {
          $width = 0;
          foreach($routes as $r) { 
            $l = strlen($r[$key]);
            if ($l > $width) { $width = $l; }
          }
          $widths[$key] = $width;
        }

        // print the output
        foreach ($routes as $r) {
          fwrite($stream, str_pad($r['name'],   $widths['name'],   ' ', STR_PAD_LEFT)  . ' ');
          fwrite($stream, str_pad($r['method'], $widths['method'], ' ', STR_PAD_RIGHT) . ' ');
          fwrite($stream, str_pad($r['path'],   $widths['path'],   ' ', STR_PAD_RIGHT) . ' ');
          fwrite($stream, $r['hardcodes'] . $eol);
        }        
    }

    /**
     * Analyze the mapper and return an array of data about the
     * routes connected to the mapper.
     *
     * @return array
     */
    public function getRoutes()
    {
        /**
         * Traverse all routes connected to the mapper in match order, 
         * and assemble an array of $routes used to build the output
         */
        $routes = array();
        foreach ($this->_mapper->matchList as $route) {
          // name of this route, or empty string if anonymous
          $routeName = '';
          foreach ($this->_mapper->routeNames as $name => $namedRoute) {
              if ($route === $namedRoute) { $routeName = $name; break; }
          }

          // request_method types recognized by this route, or empty string for any
          $methods = array('');
          if (isset($route->conditions['method']) && is_array($route->conditions['method']) ) {
            $methods = $route->conditions['method'];
          }

          // hardcoded defaults that can't be overriden by the request path as {:key=>"value"}
          $hardcodes = array();
          foreach ($route->hardCoded as $key) {
            $value = isset($route->defaults[$key]) ? $route->defaults[$key] : 'NULL';
            $dump = ":{$key}=>\"{$value}\"";
            ($key == 'controller') ? array_unshift($hardcodes, $dump) : $hardcodes[] = $dump;
          }
          $hardcodes = empty($hardcodes) ? '' : '{'. implode(', ', $hardcodes) .'}';  

          // route data for output 
          foreach ($methods as $method) {
            $routes[] = array('name'      => $routeName,
                              'method'    => $method,
                              'path'      => '/' . $route->routePath,
                              'hardcodes' => $hardcodes);
          }
        }

        return $routes;
    }

}
