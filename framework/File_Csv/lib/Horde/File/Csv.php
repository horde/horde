<?php
/**
 * Provide reading and creating of CSV data and files.
 *
 * Copyright 2002-2003 Tomas Von Veschler Cox <cox@idecnet.com>
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * This source file is subject to version 2.0 of the PHP license, that is
 * bundled with this package in the file LICENSE, and is available at through
 * the world-wide-web at http://www.php.net/license/2_02.txt.  If you did not
 * receive a copy of the PHP license and are unable to obtain it through the
 * world-wide-web, please send a note to license@php.net so we can mail you a
 * copy immediately.
 *
 * @author   Tomas Von Veschler Cox <cox@idecnet.com>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Horde_File_Csv
 */
class Horde_File_Csv
{
    /** Mode to use for reading from files */
    const MODE_READ = 'rb';

    /** Mode to use for truncating files, then writing */
    const MODE_WRITE = 'wb';

    /** Mode to use for appending to files */
    const MODE_APPEND = 'ab';

    /**
     * Discovers the format of a CSV file (the number of fields, the
     * separator, the quote string, and the line break).
     *
     * We can't use the auto_detect_line_endings PHP setting, because it's not
     * supported by fgets() contrary to what the manual says.
     *
     * @param string $file      The CSV file name
     * @param array $extraSeps  Extra separators that should be checked for.
     *
     * @return array  The format hash.
     * @throws Horde_File_Csv_Exception
     */
    static public function discoverFormat($file, $extraSeps = array())
    {
        if (!$fp = @fopen($file, 'r')) {
            throw new Horde_File_Csv_Exception('Could not open file: ' . $file);
        }

        $seps = array("\t", ';', ':', ',', '~');
        $seps = array_merge($seps, $extraSeps);
        $conf = $matches = array();
        $crlf = null;

        /* Take the first 10 lines and store the number of ocurrences for each
         * separator in each line. */
        for ($i = 0; ($i < 10) && ($line = fgets($fp));) {
            /* Do we have Mac line endings? */
            $lines = preg_split('/\r(?!\n)/', $line, 10);
            $j = 0;
            $c = count($lines);
            if ($c > 1) {
                $crlf = "\r";
            }
            while ($i < 10 && $j < $c) {
                $line = $lines[$j];
                if (!isset($crlf)) {
                    foreach (array("\r\n", "\n") as $c) {
                        if (substr($line, -strlen($c)) == $c) {
                            $crlf = $c;
                            break;
                        }
                    }
                }
                ++$i;
                ++$j;
                foreach ($seps as $sep) {
                    $matches[$sep][$i] = substr_count($line, $sep);
                }
            }
        }

        if (isset($crlf)) {
            $conf['crlf'] = $crlf;
        }

        /* Group the results by amount of equal occurrences. */
        $amount = $fields = array();
        foreach ($matches as $sep => $lines) {
            $times = array(0 => 0);
            foreach ($lines as $num) {
                if ($num > 0) {
                    $times[$num] = (isset($times[$num])) ? $times[$num] + 1 : 1;
                }
            }
            arsort($times);
            $fields[$sep] = key($times);
            $amount[$sep] = $times[key($times)];
        }
        arsort($amount);
        $sep = key($amount);

        $conf['fields'] = $fields[$sep] + 1;
        $conf['sep']    = $sep;

        /* Test if there are fields with quotes around in the first 10
         * lines. */
        $quotes = '"\'';
        $quote  = '';
        rewind($fp);
        for ($i = 0; ($i < 10) && ($line = fgets($fp)); ++$i) {
            if (preg_match("|$sep([$quotes]).*([$quotes])$sep|U", $line, $match)) {
                if ($match[1] == $match[2]) {
                    $quote = $match[1];
                    break;
                }
            }
            if (preg_match("|^([$quotes]).*([$quotes])$sep|", $line, $match) ||
                preg_match("|([$quotes]).*([$quotes])$sep\s$|Us", $line, $match)) {
                if ($match[1] == $match[2]) {
                    $quote = $match[1];
                    break;
                }
            }
        }
        $conf['quote'] = $quote;

        fclose($fp);

        // XXX What about trying to discover the "header"?
        return $conf;
    }

    /**
     * Reads a row from a CSV file and returns it as an array.
     *
     * This method normalizes linebreaks to single newline characters (0x0a).
     *
     * @param string $file  The name of the CSV file.
     * @param array $conf   The configuration for the CSV file.
     *
     * @return array|boolean  The CSV data or false if no more data available.
     * @throws Horde_File_Csv_Exception
     */
    static public function read($file, &$conf)
    {
        $fp = self::getPointer($file, $conf, self::MODE_READ);

        $line = fgets($fp);
        $line_length = strlen($line);

        /* Use readQuoted() if we have Mac line endings. */
        if (preg_match('/\r(?!\n)/', $line)) {
            fseek($fp, -$line_length, SEEK_CUR);
            return self::readQuoted($file, $conf);
        }

        /* Normalize line endings. */
        $line = str_replace("\r\n", "\n", $line);
        if (!strlen(trim($line))) {
            return false;
        }

        self::_line(self::_line() + 1);

        if ($conf['fields'] == 1) {
            return array($line);
        }

        $fields = explode($conf['sep'], $line);
        if ($conf['quote']) {
            $last = ltrim($fields[count($fields) - 1]);
            /* Fallback to read the line with readQuoted() if we assume that
             * the simple explode won't work right. */
            $last_len = strlen($last);
            if (($last_len &&
                 $last[$last_len - 1] == "\n" &&
                 $last[0] == $conf['quote'] &&
                 $last[strlen(rtrim($last)) - 1] != $conf['quote']) ||
                (count($fields) != $conf['fields'])
                // XXX perhaps there is a separator inside a quoted field
                // preg_match("|{$conf['quote']}.*{$conf['sep']}.*{$conf['quote']}|U", $line)
                ) {
                fseek($fp, -$line_length, SEEK_CUR);
                return self::readQuoted($file, $conf);
            } else {
                foreach ($fields as $k => $v) {
                    $fields[$k] = self::unquote(trim($v), $conf['quote']);
                }
            }
        } else {
            foreach ($fields as $k => $v) {
                $fields[$k] = trim($v);
            }
        }

        $dict = new Horde_Translation_Gettext('Horde_File_Csv', dirname(__FILE__) . '/../../../locale');
        if (count($fields) < $conf['fields']) {
            self::warning(sprintf($dict->t("Wrong number of fields in line %d. Expected %d, found %d."), self::_line(), $conf['fields'], count($fields)));
            $fields = array_merge($fields, array_fill(0, $conf['fields'] - count($fields), ''));
        } elseif (count($fields) > $conf['fields']) {
            self::warning(sprintf($dict->t("More fields found in line %d than the expected %d."), self::_line(), $conf['fields']));
            array_splice($fields, $conf['fields']);
        }

        return $fields;
    }

    /**
     * Reads a row from a CSV file and returns it as an array.
     *
     * This method is able to read fields with multiline data and normalizes
     * linebreaks to single newline characters (0x0a).
     *
     * @param string $file  The name of the CSV file.
     * @param array $conf   The configuration for the CSV file.
     *
     * @return array|boolean  The CSV data or false if no more data available.
     * @throws Horde_File_Csv_Exception
     */
    static public function readQuoted($file, &$conf)
    {
        $fp = self::getPointer($file, $conf, self::MODE_READ);

        /* A buffer with all characters of the current field read so far. */
        $buff = '';
        /* The current character. */
        $c = null;
        /* The read fields. */
        $ret = false;
        /* The number of the current field. */
        $i = 0;
        /* Are we inside a quoted field? */
        $in_quote = false;
        /* Did we just process an escaped quote? */
        $quote_escaped = false;
        /* Is the last processed quote the first of a field? */
        $first_quote = false;

        while (($ch = fgetc($fp)) !== false) {
            /* Normalize line breaks. */
            if ($ch == $conf['crlf']) {
                $ch = "\n";
            } elseif (strlen($conf['crlf']) == 2 && $ch == $conf['crlf'][0]) {
                $next = fgetc($fp);
                if (!$next) {
                    break;
                }
                if ($next == $conf['crlf'][1]) {
                    $ch = "\n";
                }
            }

            /* Previous character. */
            $prev = $c;
            /* Current character. */
            $c = $ch;

            /* Simple character. */
            if ($c != $conf['quote'] &&
                $c != $conf['sep'] &&
                $c != "\n") {
                $buff .= $c;
                if (!$i) {
                    $i = 1;
                }
                $first_quote = $quote_escaped = false;
                continue;
            }

            if ($c == $conf['quote'] && !$in_quote) {
                /* Quoted field begins. */
                $in_quote = true;
                $buff = '';
                if (!$i) {
                    $i = 1;
                }
            } elseif ($in_quote) {
                /* We do NOT check for the closing quote immediately, but when
                 * we got the character AFTER the closing quote. */
                if ($c == $conf['quote'] && $prev == $conf['quote'] &&
                    !$quote_escaped) {
                    /* Escaped (double) quotes. */
                    $first_quote = $quote_escaped = true;
                    $prev = null;
                    /* Simply skip the second quote. */
                    continue;
                } elseif ($c == $conf['sep'] && $prev == $conf['quote']) {
                    /* Quoted field ends with a delimiter. */
                    $in_quote = $quote_escaped = false;
                    $first_quote = true;
                } elseif ($c == "\n") {
                    /* We have a linebreak inside the quotes. */
                    if (strlen($buff) == 1 &&
                        $buff[0] == $conf['quote'] &&
                        $quote_escaped && $first_quote) {
                        /* A line break after a closing quote of an empty
                         * field, field and row end here. */
                        $in_quote = false;
                    } elseif (strlen($buff) >= 1 &&
                        $buff[strlen($buff) - 1] == $conf['quote'] &&
                        !$quote_escaped && !$first_quote) {
                        /* A line break after a closing quote, field and row
                         * end here. This is NOT the closing quote if we
                         * either process an escaped (double) quote, or if the
                         * quote before the line break was the opening
                         * quote. */
                        $in_quote = false;
                    } else {
                        /* Only increment the line number. Line breaks inside
                         * quoted fields are part of the field content. */
                        self::_line(self::_line() + 1);
                    }
                    $quote_escaped = false;
                    $first_quote = true;
                }
            }

            if (!$in_quote &&
                ($c == $conf['sep'] || $c == "\n")) {
                /* End of line or end of field. */
                if ($c == $conf['sep'] &&
                    (count($ret) + 1) == $conf['fields']) {
                    /* More fields than expected. Forward the line pointer to
                     * the EOL and drop the remainder. */
                    while ($c !== false && $c != "\n") {
                        $c = fgetc($fp);
                    }
                    self::warning(sprintf('More fields found in line %d than the expected %d.', self::_line(), $conf['fields']));
                }

                if ($c == "\n" &&
                    $i != $conf['fields']) {
                    /* Less fields than expected. */
                    if ($i == 0) {
                        /* Skip empty lines. */
                        return $ret;
                    }
                    self::warning(sprintf('Wrong number of fields in line %d. Expected %d, found %d.', self::_line(), $conf['fields'], $i));

                    $ret[] = self::unquote($buff, $conf['quote']);
                    return array_merge($ret, array_fill(0, $conf['fields'] - $i, ''));
                }

                /* Remove surrounding quotes from quoted fields. */
                $ret[] = ($buff == '"')
                    ? ''
                    : self::unquote($buff, $conf['quote']);
                if (count($ret) == $conf['fields']) {
                    return $ret;
                }

                $buff = '';
                ++$i;
                continue;
            }
            $buff .= $c;
        }

        return $ret;
    }

    /**
     * Writes a hash into a CSV file.
     *
     * @param string $file   The name of the CSV file.
     * @param array $fields  The CSV data.
     * @param array $conf    The configuration for the CSV file.
     *
     * @throws Horde_File_Csv_Exception
     */
    static public function write($file, $fields, &$conf)
    {
        $fp = self::getPointer($file, $conf, self::MODE_WRITE);

        if (count($fields) != $conf['fields']) {
            throw new Horde_File_Csv_Exception(sprintf('Wrong number of fields. Expected %d, found %d.', $conf['fields'], count($fields)));
        }

        $write = '';
        for ($i = 0; $i < count($fields); ++$i) {
            $write .= (!is_numeric($fields[$i]) && $conf['quote'])
               ? $conf['quote'] . $fields[$i] . $conf['quote']
               : $fields[$i];
            $write .= ($i < (count($fields) - 1))
                ? $conf['sep']
                : $conf['crlf'];
        }

        if (!fwrite($fp, $write)) {
            throw new Horde_File_Csv_Exception(sprintf('Cannot write to file "%s"', $file));
        }

        fclose($fp);
    }

    /**
     * Removes surrounding quotes from a string and normalizes linebreaks.
     *
     * @param string $field  The string to unquote.
     * @param string $quote  The quote character.
     * @param string $crlf   The linebreak character.
     *
     * @return string  The unquoted data.
     */
    static public function unquote($field, $quote, $crlf = null)
    {
        /* Skip empty fields (form: ;;) */
        if (!strlen($field)) {
            return $field;
        }

        if ($quote && $field[0] == $quote &&
            $field[strlen($field) - 1] == $quote) {
            /* Normalize only for BC. */
            if ($crlf) {
                $field = str_replace($crlf, "\n", $field);
            }
            return substr($field, 1, -1);
        }

        return $field;
    }

    /**
     * Sets or gets the current line being parsed.
     *
     * @param integer $line  If specified, the current line.
     *
     * @return integer  The current line.
     */
    static protected function _line($line = null)
    {
        static $current_line = 0;

        if (!is_null($line)) {
            $current_line = $line;
        }

        return $current_line;
    }

    /**
     * Adds a warning to or retrieves and resets the warning stack.
     *
     * @param string  A warning string.  If not specified, the existing
     *                warnings will be returned instead and the warning stack
     *                gets emptied.
     *
     * @return array  If no parameter has been specified, the list of existing
     *                warnings.
     */
    static public function warning($warning = null)
    {
        static $warnings = array();

        if (is_null($warning)) {
            $return = $warnings;
            $warnings = array();
            return $return;
        }

        $warnings[] = $warning;
    }

    /**
     * Returns or creates the file descriptor associated with a file.
     *
     * @param string $file  The name of the file
     * @param array $conf   The configuration
     * @param string $mode  The open mode. self::MODE_READ or
     *                      self::MODE_WRITE.
     *
     * @return resource  The file resource.
     *
     * @throws Horde_File_Csv_Exception
     */
    static public function getPointer($file, &$conf, $mode = self::MODE_READ)
    {
        static $resources = array();
        static $config = array();

        if (isset($resources[$file])) {
            $conf = $config[$file];
            return $resources[$file];
        }

        if (!is_array($conf)) {
            throw new Horde_File_Csv_Exception('Invalid configuration.');
        }

        if (!isset($conf['fields']) || !is_numeric($conf['fields'])) {
            throw new Horde_File_Csv_Exception('The number of fields must be numeric.');
        }

        if (isset($conf['sep'])) {
            if (strlen($conf['sep']) != 1) {
                throw new Horde_File_Csv_Exception('The separator must be one single character.');
            }
        } elseif ($conf['fields'] > 1) {
            throw new Horde_File_Csv_Exception('No separator specified.');
        }

        if (!empty($conf['quote'])) {
            if (strlen($conf['quote']) != 1) {
                throw new Horde_File_Csv_Exception('The quote character must be one single character.');
            }
        } else {
            $conf['quote'] = '';
        }

        if (!isset($conf['crlf'])) {
            $conf['crlf'] = "\n";
        }

        $config[$file] = $conf;

        $fp = @fopen($file, $mode);
        if (!is_resource($fp)) {
            throw new Horde_File_Csv_Exception(sprintf('Cannot open file "%s".', $file));
        }
        $resources[$file] = $fp;
        self::_line(0);

        if (($mode == self::MODE_READ) && !empty($conf['header'])) {
            self::read($file, $conf);
        }

        return $fp;
    }

}
