<?php
/**
 * Horde Routes package
 *
 * This package is heavily inspired by the Python "Routes" library
 * by Ben Bangert (http://routes.groovie.org).  Routes is based
 * largely on ideas from Ruby on Rails (http://www.rubyonrails.org).
 *
 * @author  Maintainable Software, LLC. (http://www.maintainable.com)
 * @author  Mike Naberezny <mike@maintainable.com>
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * @package Routes
 */

/**
 * @package Routes
 */
class Horde_Routes_TestHelper
{
    /**
     * Update a Mapper instance with a new $environ.  If PATH_INFO
     * is present, try to match it and update mapperDict.
     * 
     * @param  Horde_Routes_Mapper  $mapper   Mapper instance to update
     * @param  array                $environ  Environ to set in Mapper
     * @return void
     */
    public static function updateMapper($mapper, $environ)
    {
        $mapper->environ = $environ;
        $mapper->utils->mapperdict = null;
        
        if (isset($environ['PATH_INFO'])) {
            $result = $mapper->routeMatch($environ['PATH_INFO']);
            $mapper->utils->mapperDict = isset($result) ? $result[0] : null;
        }
    }

}
