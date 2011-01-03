<?php
/**
 * @author  Jan Schneider <jan@horde.org>
 * @since   Luxor 0.1
 * @package Luxor
 */
class Luxor
{
    /**
     * Initial app setup code.
     */
    public static function initialize()
    {
        global $sources, $sourceid, $source, $files, $index, $pathname;

        require LUXOR_BASE . '/config/backends.php';

        /* Default to the first source; overridden elsewhere if necessary. */
        $sourceid = Horde_Util::getFormData('source');
        if (!isset($sources[$sourceid])) {
            $sourceid = key($sources);
        }
        $source = $sources[$sourceid];
        $files = Luxor_Files::factory($source['driver'], $source);
        $index = Luxor_Driver::factory($sourceid);
        $pathname = Luxor::fixPaths(Horde_Util::getFormData('f'));
    }

    /**
     * Generate a URL that links into Luxor.
     *
     * @param string $uri     The path being browsed.
     * @param array  $args    Key/value pair of any GET parameters to append
     * @param string $anchor  Anchor entity name
     */
    function url($uri = '', $args = array(), $anchor = '')
    {
        global $conf, $sourceid;

        $arglist = array_merge(array('source' => $sourceid), $args);

        if ($conf['options']['urls'] == 'rewrite') {
            if (substr($uri, 0, 1) == '/') {
                $uri = substr($uri, 1);
            }
        } else {
            $arglist['f'] = $uri;
            $uri = 'source.php';
        }

        $url = Horde_Util::addParameter(Horde::url($uri), $arglist);
        if (!empty($anchor)) {
            $url .= "#$anchor";
        }

        return $url;
    }

    /**
     * Generate a list of sources available from this installation
     * of Luxor.
     *
     * @return XHTML code representing links to the repositories
     */
    function sources()
    {
        global $source, $sources;

        $arr = array();
        foreach ($sources as $key => $val) {
            if ($val != $source) {
                $arr[] = Horde::link(Luxor::url('', array('source' => $key))) .
                    htmlspecialchars($val['name']) . '</a>';
            }
        }

        if (count($arr)) {
            return _("Other Sources") . ': ' . implode(', ', $arr);
        } else {
            return '';
        }
    }

    /**
     * Sanitizes path names passed by the user.
     *
     * @param string $node  The path name to clean up.
     *
     * @return string       The cleaned up path.
     */
    function fixPaths($node)
    {
        global $files;

        $node = '/' . $node;
        $node = preg_replace('|/[^/]+/\.\./|', '/', $node);
        $node = preg_replace('|/\.\./|', '/', $node);
        if ($files->isDir($node)) {
            $node .= '/';
        }

        return preg_replace('|//+|', '/', $node);
    }

    /**
     *
     */
    function outfun($str, $arr)
    {
        return str_replace("\n", "\n" . array_shift($arr), $str);
    }

    function dirExpand($dir)
    {
        global $files, $mime_drivers, $mime_drivers_map;

        $result = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), 'horde');
        extract($result);
        $result = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), 'luxor');
        if (isset($result['mime_drivers'])) {
            $mime_drivers = Horde_Array::replaceRecursive($mime_drivers, $result['mime_drivers']);
        }
        if (isset($result['mime_drivers_map'])) {
            $mime_drivers_map = Horde_Array::replaceRecursive($mime_drivers_map, $result['mime_drivers_map']);
        }

        $nodes = $files->getDir($dir);
        if (is_a($nodes, 'PEAR_Error')) {
            return $nodes;
        }
        $index = $files->getIndex($dir);
        if (is_a($index, 'PEAR_Error')) {
            return $index;
        }

        if ($dir != '/') {
            array_unshift($nodes, '../');
        }

        $list = array();
        foreach ($nodes as $node) {
            $link = Luxor::url($dir . $node);
            $modtime = $files->getFiletime($dir . $node);
            $modtime = $modtime ? gmdate('Y-m-d H:i:s', $modtime) : '-';
            $description = empty($index[$node]) ? '&nbsp;' : $index[$node];

            if (substr($node, -1) == '/') {
                $filesize = '-';
                $bytes = '';
                if ($node == '../') {
                    $icon = Horde::img('parent.png', _("Up to parent"));
                    $node = _("Parent Directory");
                } else {
                    $icon = Horde::img('folder.png', $node);
                }
            } else {
                if (preg_match('/^.*\.[oa]$|^core$|^00-INDEX$/', $node)) {
                    continue;
                }
                $icon = Horde::img($GLOBALS['injector']->getInstance('Horde_Core_Factory_MimeViewer')->getIcon(Horde_Mime_Magic::filenameToMime($node)), '', '', '');
                $filesize = $files->getFilesize($dir . $node);
                if ($filesize < 1 << 10) {
                    $bytes = _("bytes");
                } else {
                    $bytes = _("kbytes");
                    $filesize = $filesize >> 10;
                }
            }

            $list[] = array('icon'        => $icon,
                            'name'        => $node,
                            'link'        => $link,
                            'filesize'    => $filesize,
                            'bytes'       => $bytes,
                            'modtime'     => $modtime,
                            'description' => $description);
        }

        return $list;
    }

    /**
     * Prints a descriptive blurb at the end of directory listings.
     *
     * @param Luxor_File $files  An instance of Luxor_File.
     * @param string $path       The directory where to look for a README file.
     */
    function dirDesc($files, $path)
    {
        $table_head = '<br /><br /><table width="100%" cellpadding="5"><tr><td class="text"><span class="fixed">';
        $table_foot = '</span></td></tr></table>';
        if (file_exists($filename = $files->toReal($path . '/README')) ||
            file_exists($filename = $files->toReal($path . '/README.txt'))) {
            $contents = file_get_contents($filename);

            return $table_head . $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($contents, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) . $table_foot;
        } elseif ($filename = file_exists($files->toReal($path . '/README.html'))) {
            global $mime_drivers, $mime_drivers_map;
            $result = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), 'horde');
            extract($result);
            $result = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), 'luxor');
            $mime_drivers = Horde_Array::replaceRecursive($mime_drivers, $result['mime_drivers']);
            $mime_drivers_map = Horde_Array::replaceRecursive($mime_drivers_map, $result['mime_drivers_map']);

            $contents = file_get_contents($filename);

            $mime_part = new Horde_Mime_Part('text/plain', $contents);
            $mime_part->setName('README');

            return $table_head .
                $GLOBALS['injector']->getInstance('Horde_Core_Factory_MimeViewer')->create($mime_part)->render() .
                $table_foot;
        }
    }

    /**
     * Smaller version of the markupFile() function meant for marking up
     * the descriptions in source directory listings.
     *
     * @see markupFile()
     *
     * @todo most of this can be done by Horde_Text::toHtml()
     */
    function markupString($string, $virtp)
    {
        $string = htmlspecialchars($string);

        // HTMLify email addresses and urls.
        $string = preg_replace('#((ftp|http|nntp|snews|news)://(\w|\w\.\w|\~|\-|\/|\#)+(?!\.\b))#',
                               '<a href="$1">$1</a>', $string);

        // HTMLify file names, assuming file is in the current directory.
        $string = preg_replace('#\b(([\w\-_\/]+\.(c|h|cc|cp|hpp|cpp|java))|README)\b#e',
                               '"<a href=\" . Luxor::url("' . $virtp . '$1") . "$1</a>"', $string);

        return $string;
    }

    function whereMenu()
    {
        global $pathname;

        $res = '';
        $wherePath = '';
        foreach (explode('/', $pathname) as $dir) {
            $wherePath .= $dir ? "/$dir" : '';
            if (!empty($dir)) {
                $res .= ' :: ' . Horde::link(Luxor::url($wherePath)) .
                    htmlspecialchars($dir) . '</a>';
            }
        }
        return $res;
    }

    function fileRef($desc, $css, $path, $line = 0, $args = array())
    {
        if ($line > 0 && strlen($line) < 3) {
            $line = str_repeat('0', (3 - strlen($line))) . $line;
        }

        return '<a href="' . Luxor::url($path, $args, $line > 0 ? 'l' . $line : '') . '" class="' . htmlspecialchars($css) . '">' .
            htmlspecialchars($desc) . '</a>';
    }

    function incRef($name, $css, $file, $paths = array())
    {
        global $files;

        foreach ($paths as $dir) {
            $dir = preg_replace('|/+$|', '', $dir);
            $path = $dir . '/' . $file;
            if ($files->isFile($path)) {
                return Luxor::fileRef($name, $css, $path);
            }
        }

        return htmlspecialchars($name);
    }

    /**
     * Check if the given item is restricted from being shown.
     *
     * @param string $filename  The filename to check
     *
     * @return boolean  Whether or not the item is allowed to be displayed
     */
    function isRestricted($filename)
    {
        global $source;

        if ($GLOBALS['registry']->isAdmin()) {
            return false;
        }

        if (isset($source['restrictions']) && is_array($source['restrictions'])) {
            foreach ($source['restrictions'] as $restriction) {
                if (preg_match('|' . str_replace('|', '\|', $restriction) . '|', $filename)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if the given directory is filtered out.
     *
     * @param string $dir  The path to check.
     *
     * @return boolean  True if the directory should be shown/parsed, false otherwise.
     */
    function isDirParsed($dir)
    {
        global $source;

        if (isset($source['dirFilter']) && is_array($source['dirFilter'])) {
            foreach ($source['dirFilter'] as $filter) {
                if (preg_match('/' . str_replace('/', '\/', substr($filter, 1)) . '/', $dir)) {
                    return (substr($filter, 0, 1) == '+');
                }
            }
        }

        if (isset($source['dirUnmatched'])) {
            return $source['dirUnmatched'];
        }
        return true;
    }

    /**
     * Check if the given file should be parsed an/or displayed.
     *
     * @param string $file  The filename to check.
     *
     * @return boolean  True if the file should be shown/parsed, false otherwise.
     */
    function isFileParsed($file)
    {
        global $source;

        if (isset($source['fileFilter']) && is_array($source['fileFilter'])) {
            foreach ($source['fileFilter'] as $filter) {
                if (preg_match('/' . str_replace('/', '\/', substr($filter, 1)) . '/', $file, $matches)) {
                    return (substr($filter, 0, 1) == '+');
                }
            }
        }

        if (isset($source['fileUnmatched'])) {
            return $source['fileUnmatched'];
        }
        return true;
    }

    /**
     * Pre- and post-fix every line of a string with strings.
     */
    function fixString($string, $pre = '', $post = '')
    {
        $lines = preg_split('(\r\n|\n|\r)', $string);
        $res = '';
        foreach ($lines as $line) {
            $res .= !empty($res) ? "\n" : '';
            $res .= $pre . $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($line, 'space2html', array('encode' => true, 'encode_all' => true)) . $post;
        }
        return $res;
    }

    /**
     */
    function markupfile($pathname, $fileh, $ann = array())
    {
        global $files, $conf;

        preg_match_all('|^(.*/)|', $pathname, $dir);
        $dir = $dir[0];

        /* Determine the file's language and create a Luxor_Lang
         * instance. */
        $lang = &Luxor_Lang::builder($files, $pathname);
        if (is_a($lang, 'PEAR_Error')) {
            return $lang;
        }

        $html = '<table cellspacing="0" width="100%" class="text">';

        // A source code file.
        if (!$lang) {
            return false;
        }

        $parser = new Luxor_SimpleParse($fileh, 1, $lang->_langmap['spec']);
        $linenum = 1;

        list($btype, $frag) = $parser->nextFrag();
        $ofrag = '';
        while ($frag) {
            $frag = preg_replace('/([&<>])/', chr(0) . '$1', $frag);
            switch ($btype) {
            case 'comment':
                // Comment
                // Convert mail adresses to mailto:
                // &freetextmarkup($frag);
                // $lang->processComment(\$frag);
                $frag = Luxor::fixString($frag, '<span class="comment">', '</span>');
                break;

            case 'string':
                $frag = Luxor::fixString($frag, '<span class="string">', '</span>');
                break;

            case 'include':
                // Include directive
                $frag = $lang->processInclude($frag, $dir);
                break;

            case 'variable':
                if (!empty($conf['options']['use_show_var'])) {
                    $pre = sprintf('<span class="variable"><span class="var_%s" onmouseover="show_var(\'var_%s\');" onmouseout="unshow_var(\'var_%s\');">', substr($frag, 1), substr($frag, 1), substr($frag, 1));
                    $frag = Luxor::fixString($frag, $pre, '</span></span>');
                } else {
                    $frag = Luxor::fixString($frag, '<span class="variable">', '</span>');
                }
                break;

            default:
                // Code
                // somehow get $source['may_reference'] into the second parameter here.
                $frag = $lang->processCode($frag);
            }

            $frag = preg_replace('/\0([&<>])/', '$1', $frag);

            $ofrag .= $frag;
            list($btype, $frag) = $parser->nextFrag();
        }

        $lines = preg_split('(\r\n|\n|\r)', $ofrag);
        foreach ($lines as $line) {
            $html .= '<tr><td align="right" style="padding-left:10px; padding-right:10px;"><a id="l' . $linenum . '" class="fixed" style="color:black">' . $linenum++ . '</a></td><td width="100%" class="fixed">' . $line . "</td></tr>\n";
        }
        return $html . '</table>';
    }

    /**
     * Build Luxor's list of menu items.
     */
    function getMenu($returnType = 'object')
    {
        global $registry;

        $menu = new Horde_Menu(Horde_Menu::MASK_ALL);
        $menu->add(Horde::url('source.php'), _("_Browse"), 'luxor.png');

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }
}
