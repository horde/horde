#!@php_bin@
<?php
/**
 * This is a command line interface for the VFS package.
 *
 * `vfs.php help' shows some usage instructions.
 *
 * @package VFS
 */

/** PEAR */
require_once 'PEAR.php';

/** Console_Getopt */
require_once 'Console/Getopt.php';

/** DB */
require_once 'DB.php';

/** VFS */
require_once 'VFS.php';

/* Track errors. */
ini_set('track_errors', true);

/* Get command line options. */
$argv = Console_Getopt::readPHPArgv();
if (is_a($argv, 'PEAR_Error')) {
    usage($argv->getMessage());
}
$cmd = array_shift($argv);
$options = Console_Getopt::getopt2($argv, '', array());
if (is_a($options, 'PEAR_Error')) {
    usage($options->getMessage());
}

/* Show help? */
if (!count($options[1]) || in_array('help', $options[1])) {
    usage();
}

/* Get and execute the command. */
$command = array_shift($options[1]);
switch ($command) {

case 'ls':
    if (!count($options[1])) {
        usage($command);
    }
    $params = Console_Getopt::getopt2($options[1], 'alR');
    if (is_a($params, 'PEAR_Error')) {
        usage($params->getMessage());
    }
    $path = array_shift($params[1]);
    ls($path, mergeOptions($params[0]), $params[1]);
    break;

case 'cp':
    if (!count($options[1])) {
        usage($command);
    }
    $params = Console_Getopt::getopt2($options[1], 'arv');
    if (is_a($params, 'PEAR_Error')) {
        usage($params->getMessage());
    }
    $source = array_shift($params[1]);
    $target = array_shift($params[1]);
    cp($source, $target, mergeOptions($params[0]), $params[1]);
    break;

default:
    usage();
    break;

}

/**
 * Lists the contents of the specified directory.
 *
 * @param string $url     The URL of the VFS backend
 * @param array $argv     Additional options
 * @param string $filter  Additional parameters
 */
function ls($url, $argv, $filter)
{
    $params = url2params($url);
    $recursive = in_array('R', $argv);

    $vfs = vfs($params);
    try {
        $list = $vfs->listFolder($params['path'],
                                 count($filter) ? $filter[0] : null,
                                 in_array('a', $argv));
    } catch (VFS_Exception $e) {
        usage($e);
    }
    if (in_array('a', $argv)) {
        $list = array_merge(array('.' => array('name' => '.'),
                                  '..' => array('name' => '..')),
                            $list);
    }
    $list = array_keys($list);
    $max = max(array_map(create_function('$a', 'return strlen($a);'), $list)) + 2;

    $line = '';
    $dirs = array();
    if ($recursive) {
        echo $params['path'] . ":\n";
    }
    foreach ($list as $entry) {
        if ($vfs->isFolder($params['path'], $entry)) {
            $dirs[] = $entry;
        }
        $entry = sprintf('%-' . $max . 's', $entry);
        if (strlen($line . $entry) > 80 && !empty($line)) {
            echo $line . "\n";
            $line = '';
        }
        $line .= $entry;
    }
    if (!empty($line)) {
        echo $line . "\n";
    }

    if ($recursive && count($dirs)) {
        foreach ($dirs as $dir) {
            echo "\n";
            ls($url . '/' . $dir, $argv, $filter);
        }
    }
}

/**
 * Copies one or several files to a different location.
 *
 * @param string $source  The source file(s) or directory.
 * @param string $target  The target file or directory.
 * @param array $argv     Additional options
 * @param string $filter  Additional parameters
 */
function cp($source, $target, $argv, $filter)
{
    $source_params = url2params($source);
    $source_path = rtrim($source_params['path'], '/');
    unset($source_params['path']);

    $target_params = url2params($target);
    $target_path = rtrim($target_params['path'], '/');
    unset($target_params['path']);

    if ($source_params == $target_params) {
        // TODO: Shortcut with VFS::copy()
    }

    $source_vfs = vfs($source_params);
    $target_vfs = vfs($target_params);

    _cp($source_vfs, $target_vfs, $source_path, $target_path, $argv, $filter);
}

/**
 * Copies one or several files to a different location.
 *
 * @param VFS $source_vfs      The source VFS object.
 * @param VFS $target_vfs  The The target VFS object.
 * @param string $source_path  The source file(s) or directory.
 * @param string $target_path  The target file or directory.
 * @param array $argv          Additional options
 * @param string $filter       Additional parameters
 */
function _cp(&$source_vfs, &$target_vfs, $source_path, $target_path, $argv,
             $filter)
{
    $source_object = basename($source_path);
    $source_parent_path = dirname($source_path);

    $target_object = basename($target_path);
    $target_parent_path = dirname($target_path);

    $recursive = in_array('r', $argv);

    if ($source_vfs->isFolder($source_parent_path, $source_object)) {
        if (!$recursive) {
            echo "Skipping directory $source_path\n";
            return;
        }
        if (!$target_vfs->isFolder($target_parent_path, $target_object)) {
            if ($target_vfs->exists($target_parent_path, $target_object)) {
                usage(PEAR::raiseError('You can\'t copy a folder on a file.'));
            } else {
                $target_vfs->createFolder($target_parent_path, $target_object);
            }
        }
        if (!$target_vfs->isFolder($target_path, $source_object)) {
            if ($target_vfs->exists($target_path, $source_object)) {
                usage(PEAR::raiseError('You can\'t copy a folder on a file.'));
            } elseif (!$target_vfs->exists($target_path, $source_object)) {
                $target_vfs->createFolder($target_path, $source_object);
            }
        }

        $list = $source_vfs->listFolder($source_path,
                                        count($filter) ? $filter[0] : null,
                                        in_array('a', $argv));
        foreach ($list as $item) {
            _cp($source_vfs, $target_vfs, $source_path . '/' . $item['name'],
                $target_path . '/' . $source_object, $argv, $filter);
        }
        return;
    }

    try {
        $data = &$source_vfs->read($source_parent_path, $source_object);
    } catch (VFS_Exception $e) {
        usage($e);
    }

    if ($target_vfs->isFolder($target_parent_path, $target_object)) {
        if (in_array('v', $argv)) {
            echo '`' . $source_path . '\' -> `' . $target_path . '/' .
                $source_object . "'\n";
        }

        try {
            $target_vfs->writeData($target_path, $source_object, $data, true);
        } catch (VFS_Exception $e) {
            usage($e);
        }
    } elseif ($target_vfs->isFolder(dirname($target_parent_path),
                                    basename($target_parent_path))) {
        if (in_array('v', $argv)) {
            echo '`' . $source_path . '\' -> `' . $target_path . "'\n";
        }

        try {
            $target_vfs->writeData($target_parent_path, $target_object, $data, true);
        } catch (VFS_Exception $e) {
            usage($e);
        }
    } else {
        usage(new VFS_Exception('"' . $target_parent_path . '" does not exist or is not a folder.'));
    }
}

/**
 * Shows some error and usage information.
 *
 * @param PEAR_Error $error  If specified its error messages will be displayed.
 */
function usage($error = null)
{
    if ($error instanceof VFS_Exception) {
        echo $error->getMessage() . "\n";
    } else {
        switch ($error) {
        case 'ls':
            echo 'Usage: vfs.php ls [-alR] <parameters>';
            break;
        case 'cp':
            echo 'Usage: vfs.php cp [-arv] <parameters> <parameters>';
            break;
        }
    }
    $cmd = basename($GLOBALS['cmd']);

    echo <<<USAGE
Usage: $cmd [options] command [command-options] <parameters> ...

Available commands:
    ls - lists a folders content.
    cp - copies a file or folder to a different location.

<parameters> can be paths specified like an URL, e.g.:
    file:///var/lib/horde/vfs/foo/bar
    ftp://john:secret@ftp.example.com/foo/bar
    ssh2://john:secret@ssh.example.com/foo/bar
    sql://john:secret@localhost/mysql/horde/horde_vfs/foo/bar

The SQL URL is build with the following scheme:
    sql://[<user>[:<password>]@]<hostname>/<dbtype>/<database>/<table>[/<path>]

USAGE;

    exit;
}

/**
 * Returns a VFS instance.
 *
 * @param array $params  A complete parameter set including the driver name
 *                       for the requested VFS instance.
 *
 * @return VFS  An instance of the requested VFS backend.
 */
function vfs($params)
{
    return VFS::factory($params['driver'], $params);
}

/**
 * Merges a set of options as returned by Console_Getopt::getopt2() into a
 * single array.
 *
 * @param array $options  A two dimensional array with the options.
 *
 * @return array  A flat array with the options.
 */
function mergeOptions($options)
{
    $result = array();
    foreach ($options as $param) {
        $result = array_merge($result, $param);
    }
    return $result;
}

/**
 * Parses a URL into a set of parameters that can be used to instantiate a
 * VFS object.
 *
 * @todo Document the possible URL formats.
 *
 * @param string $url  A URL with all necessary information for a VFS driver.
 *
 * @return array  A hash with the parsed information.
 */
function url2params($url)
{
    $params = array('path' => '');
    $url = @parse_url($url);
    if (!is_array($url)) {
        usage(PEAR::raiseError($php_errormsg));
    }

    $params['driver'] = $url['scheme'];
    if (isset($url['host'])) {
        $params['hostspec'] = $url['host'];
    }
    if (isset($url['port'])) {
        $params['port'] = $url['port'];
    }
    if (isset($url['user'])) {
        $params['username'] = $url['user'];
    }
    if (isset($url['pass'])) {
        $params['password'] = $url['pass'];
    }
    if (isset($url['path'])) {
        switch ($url['scheme']) {
        case 'ftp':
            $params['path'] = $url['path'];
            break;
        case 'file':
            $params['vfsroot'] = $url['path'];
            break;
        case 'sql':
            $path = explode('/', trim($url['path'], '/'));
            if (count($path) == 2) {
                usage(PEAR::raiseError('No table specified for SQL driver.'));
            }
            if (count($path) == 1) {
                usage(PEAR::raiseError('No database and no table specified for SQL driver.'));
            }
            if (!count($path)) {
                usage(PEAR::raiseError('No database type, database, and table specified for SQL driver.'));
            }
            $params['phptype'] = array_shift($path);
            $params['database'] = array_shift($path);
            $params['table'] = array_shift($path);
            $params['path'] = implode('/', $path);
            break;
        case 'ssh2':
            $params['path'] = $url['path'];
            break;
        default:
            usage(PEAR::raiseError('Only the SQL, File, and FTP drivers are supported at the moment.'));
            break;
        }
        if (isset($url['query'])) {
            $queries = explode('&', $url['query']);
            foreach ($queries as $query) {
                $pair = explode('=', $query);
                $params[$pair[0]] = isset($pair[1]) ? $pair[1] : true;
            }
        }
    }

    return $params;
}
