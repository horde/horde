<?php
/**
 * A generic implementation of the Luxor_Lang API to handle all programming
 * languages that don't have a specific driver.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @since   Luxor 0.1
 * @package Luxor
 */
class Luxor_Lang_Generic extends Luxor_Lang
{
    /**
     * The current language.
     *
     * @var string
     */
    var $_language;

    /**
     * This language's copy of the 'langmap' hash from the $languages array.
     *
     * @var array
     */
    var $_langmap;

    /**
     * Constructs a new generic language parser.
     *
     * @param array  $params    A hash containing necessary parameters.
     */
    public function __construct($params)
    {
        global $languages;

        $this->_language = $params[0];
        $this->_langmap = $languages['langmap'][$this->_language];
    }

    /**
     * Indexes a file.
     *
     * @param string $path  The full path name of the file to index.
     * @param int $fileId   The file's unique ID.
     *
     * @return mixed        A PEAR_Error on error.
     */
    function indexFile($path, $fileId)
    {
        global $conf, $index;

        $typemap = $this->_langmap['typemap'];
        include LUXOR_BASE . '/config/languages.php';

        if (isset($languages['eclangnamemapping'][$this->_language])) {
            $langforce = $languages['eclangnamemapping'][$this->_language];
        } else {
            $langforce = $this->_language;
        }

        $version = shell_exec($conf['paths']['ectags'] . ' --version');
        if (!preg_match('/Exuberant ctags +(\d+)/i', $version, $match) ||
            $match[1] < 5) {
            return PEAR::raiseError(sprintf(_("Exuberant ctags version 5 or above required, found version %s"), $version));
        }

        if (file_exists($conf['paths']['ectags'])) {
            /* Call excuberant ctags. */
            $ectags = @popen($conf['paths']['ectags'] . ' ' . $languages['ectagsopts'] .
                             ' --excmd=number --language-force=' . $langforce . ' -f - ' .
                             $path, 'r');

            if (!$ectags) {
                return PEAR::raiseError(_("Can't run ectags."));
            }
            while ($fgets = trim(fgets($ectags))) {
                @list($sym, $file, $line, $type, $ext) = explode("\t", $fgets);
                $line = preg_replace('/;"$/', '', $line);
                preg_match('/language:(\w+)/', $ext, $match);
                $ext = @$match[1];
                if (!isset($typemap[$type])) {
                    continue;
                }
                $type = $typemap[$type];
                if (!empty($ext) && preg_match('/^(struct|union|class|enum):(.*)/', $ext, $match)) {
                    $ext = str_replace('::<anonymous>', '', $match[2]);
                } else {
                    $ext = '';
                }

                /* Build index. */
                $result = $index->index($sym, $fileId, $line, $this->_langmap['langid'], $type);
                if (is_a($result, 'PEAR_Error')) {
                    pclose($ectags);
                    return $result;
                }
            }
            pclose($ectags);
        }
    }

    /**
     * References a file.
     *
     * @param string $path  The full path name of the file to reference.
     * @param int $fileId   The file's unique ID.
     *
     * @return mixed        A PEAR_Error on error.
     */
    function referenceFile($path, $fileId)
    {
        global $conf, $index;

        $fp = @fopen($path, 'r');
        if (!$fp) {
            return PEAR::raiseError(sprintf(_("Can't open file %s."), $path));
        }

        /* Instantiate parser. */
        $parser = new Luxor_SimpleParse($fp, 1, $this->_langmap['spec']);

        $linenum = 1;
        list($btype, $frag) = $parser->nextFrag();
        while ($frag) {
            $lines = array();
            if (preg_match_all('/(.*?\\n)/', $frag, $match)) {
                $lines = $match[1];
            }
            if (preg_match('/([^\\n]*)$/', $frag, $match)) {
                $lines[] = $match[1];
            }

            if ($btype) {
                /* Skip comments, strings and includes. */
                if ($btype == 'comment' || $btype == 'string' || $btype == 'include') {
                    $linenum += count($lines) - 1;
                }
            } else {
                foreach ($lines as $l) {
                    /* Strip symbol name. */
                    preg_match_all('/(?:^|[^a-zA-Z_\#])(\\~?_*[a-zA-Z][a-zA-Z0-9_]*)\b/x', $l, $match);
                    foreach ($match[1] as $string) {
                        /* Create references only for known symbols and not reserved words. */
                        if (!in_array($string, $this->_langmap['reserved']) &&
                            $index->isSymbol($string)) {
                            $result = $index->reference($string, $fileId, $linenum);
                            if (is_a($result, 'PEAR_Error')) {
                                return $result;
                            }
                        }
                    }
                    $linenum++;
                }
                $linenum--;
            }
            list($btype, $frag) = $parser->nextFrag();
        }
    }

    /**
     * Process a chunk of code
     *
     * Basically, look for anything that looks like a symbol, and if
     * it is then make it a hyperlink, unless it's a reserved word in this
     * language.
     *
     * @param string $code Reference to the code to markup.
     */
    function processCode($code, $altsources = array())
    {
        global $index, $sourceid;

        // Make sure spacing is correct.
        $code = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($code, 'space2html', array('encode' => true, 'encode_all' => true));

        // Split all the symbols.
        preg_match_all('/(^|[^\w\#&])([\w~][\w]*)\b/', $code, $match);

        // Replace symbol by link unless it's a reserved word.
        $replaced = array();
        foreach ($match[2] as $id => $string) {
            if (!in_array($string, $this->_langmap['reserved']) &&
                !in_array($match[0][$id], $replaced) && $idx = $index->isSymbol($string, $altsources)) {

                $link = Horde::applicationUrl(Horde_Util::addParameter('symbol.php', 'i', $idx));
                $link = Horde_Util::addParameter($link, 'source', $sourceid);
                $match0 = str_replace($string, '<a href="' . $link . '" class="fixed"><span class="symbol">' . $string . "</span></a>", $match[0][$id]);
                $code = str_replace($match[0][$id], $match0, $code);
                $replaced[] = $match[0][$id];
            } elseif (in_array($string, $this->_langmap['reserved']) && !in_array($match[0][$id], $replaced)) {
                $match0 = str_replace($string, '<span class="reserved">' . $string . "</span>", $match[0][$id]);
                $code = str_replace($match[0][$id], $match0, $code);
                $replaced[] = $match[0][$id];
            }
        }

        return $code;
    }
}
