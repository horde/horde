<?php
/**
 * Luxor repository implementation for a simple filesystem hierarchy.
 *
 * $Horde: luxor/lib/Files/plain.php,v 1.18 2006/05/23 02:27:39 selsky Exp $
 *
 * @author  Jan Schneider <jan@horde.org>
 * @since   Luxor 0.1
 * @package Luxor
 */
class Luxor_Files_plain extends Luxor_Files {

    /**
     * Hash containing parameters.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Constructs a new filesystem handler.
     *
     * @param array  $params    A hash containing parameters.
     */
    function Luxor_Files_plain($params = array())
    {
        $this->_params = $params;
    }

    function getFiletime($filename)
    {
        return filemtime($this->toReal($filename));
    }

    function getFilesize($filename)
    {
        return filesize($this->toReal($filename));
    }

    /**
     * Returns a file handler.
     *
     * @param string $filename  The name of the file to open.
     *
     * @return ressource        A handler of the file or false on error.
     */
    function getFileHandle($filename)
    {
        return @fopen($this->toReal($filename), 'r');
    }

    /**
     * Creates a temporary copy of a file.
     *
     * @param string $filename  The name of the file to be copied.
     *
     * @return string           The file name of the temporary copy or false
     *                          if the file couldn't be copied.
     */
    function tmpFile($filename)
    {
        $tmp = Horde::getTempFile('luxor');
        if (!@copy($this->toReal($filename), $tmp)) {
            return false;
        }
        return $tmp;
    }

    /**
     * Returns a directory's content. Backup files are skipped and
     * directories suffixed with a slash.
     *
     * @param string $path  The directory to list.
     *
     * @return array        An array containing all directories and files of
     *                      the directory or PEAR_Error if the directory
     *                      couldn't be read.
     */
    function getDir($path, $release = '')
    {
        $path = $this->toReal($path);

        $dir = @opendir($path);
        if (!$dir) {
            return PEAR::raiseError(sprintf(_("Can't open directory %s"), $path));
        }

        $dirs = array();
        $files = array();
        while (($file = readdir($dir)) !== false) {
            if (preg_match('/^\.|~$|\.orig$|\.bak$/i', $file) ||
                (is_dir($path . $file) && $file == 'CVS')) {
                continue;
            }

            if (is_dir($path . $file)) {
                if (Luxor::isDirParsed($path . $file)) {
                    $dirs[] = $file . '/';
                }
            } else {
                if (Luxor::isFileParsed($path . $file)) {
                    $files[] = $file;
                }
            }
        }

        closedir($dir);
        natcasesort($dirs);
        natcasesort($files);
        return array_merge($dirs, $files);
    }

    /**
     * Returns the full path to a file.
     *
     * @param string $pathname  The internally used (relative) name of the file.
     *
     * @return string           The full path to the file.
     */
    function toReal($pathname)
    {
        return $this->_params['root'] . $pathname;
    }

    /**
     * Checks if the given path name is a directory.
     *
     * @param string $pathname  The path name to check.
     *
     * @return boolean  True if the path name was a directory.
     */
    function isDir($pathname)
    {
        return is_dir($this->toReal($pathname));
    }

    /**
     * Checks if the given path name is a file.
     *
     * @param string $pathname  The path name to check.
     *
     * @return boolean  True if the path name was a file.
     */
    function isFile($pathname)
    {
        return is_file($this->toReal($pathname));
    }

    function getIndex($pathname)
    {
        $indexname = $this->toReal($pathname) . '00-INDEX';
        if (file_exists($indexname)) {
            $index = file_get_contents($indexname);

            if (preg_match_all('/\n(\S*)\s*\n\t-\s*([^\n]*)/s', $index, $match)) {
                $list = array();
                $iMax = count($match[1]);
                for ($i = 0; $i < $iMax; $i++) {
                    $list[$match[1][$i]] = $match[2][$i];
                }
                return $list;
            }
        }
        return array();
    }

    function getAnnotations($pathname)
    {
        return array();
    }

}
