<?php
/**
 * Luxor_Lang:: defines an API for the different programming languages Luxor
 * is able to parse.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @since   Luxor 0.1
 * @package Luxor
 */
class Luxor_Lang
{
    /**
     * Attempts to return a concrete Luxor_Lang instance based on $driver.
     *
     * @param string    $driver     The type of concrete Luxor_Lang subclass
     *                              to return.  The is based on the repository
     *                              driver ($driver).  The code is dynamically
     *                              included.
     * @param array     $params     (optional) A hash containing any additional
     *                              configuration or connection parameters a
     *                              subclass might need.
     *
     * @return mixed    The newly created concrete Luxor_Lang instance, or
     *                  false on an error.
     */
    function factory($driver, $params = array())
    {
        $driver = basename($driver);
        $class = 'Luxor_Lang_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return false;
        }
    }

    /**
     * Attempts to determine a files programming language and returns
     * a parser instance for this language.
     *
     * @param Luxor_Files $files  An instance of Luxor_Files to use for file
     *                            operations and path name resolution.
     * @param string $pathname    The path name of the file to create a
     *                            parser for.
     *
     * @return mixed    The created concrete Luxor_Lang instance, or false
     *                  on error.
     */
    function builder($files, $pathname)
    {
        global $languages;
        include LUXOR_BASE . '/config/languages.php';

        /* First, check the 'filetype' hash for a matching file extension. */
        foreach ($languages['filetype'] as $type) {
            if (preg_match('/' . $type[1] . '/', $pathname)) {
                return Luxor_Lang::factory($type[2], $type);
            }
        }

        /* Next, try to detect the shebang line. */
        $fh = $files->getFileHandle($pathname);
        if (!$fh || is_a($fh, 'PEAR_Error')) {
            return $fh;
        }
        $line = fgets($fh);
        if (!preg_match('/^\#!\s*(\S+)/s', $line, $match)) {
            return false;
        }
        if (isset($languages['interpreters'][$match[1]])) {
            $lang = $languages['filetype'][$languages['interpreters'][$match[1]]];
            return Luxor_Lang::factory($lang[2], $lang);
        }

        return false;
    }

    function processInclude($frag, $dir)
    {
        return preg_replace(array('/([\'"])(.*?)([\'"])/e',
                                  '/(\\0<)(.*?)(\\0>)/e'),
                            array('stripslashes(\'$1\') . Luxor::incRef(\'$2\', "fixed", \'$2\', $dir) . stripslashes(\'$3\')',
                                  'stripslashes(\'$1\') . Luxor::incRef(\'$2\', "fixed", \'$2\') . stripslashes(\'$3\')'),
                            $frag);
    }
}
