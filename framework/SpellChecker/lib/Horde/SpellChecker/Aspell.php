<?php
/**
 * The Horde_SpellChecker_aspell:: class provides a driver for the 'aspell'
 * program.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package SpellChecker
 */
class Horde_SpellChecker_Aspell extends Horde_SpellChecker
{
    /**
     * TODO
     *
     * @param string $text  TODO
     *
     * @return array  TODO
     * @throws Horde_Exception
     */
    public function spellCheck($text)
    {
        if ($this->_html) {
            $input = strtr($text, "\n", ' ');
        } else {
            $words = $this->_getWords($text);
            if (!count($words)) {
                return array('bad' => array(), 'suggestions' => array());
            }
            $input = implode(' ', $words);
        }

        // Descriptor array.
        $descspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );

        $process = proc_open($this->_cmd(), $descspec, $pipes);
        if (!is_resource($process)) {
            throw new Horde_Exception('Spellcheck failed. Command line: ' . $this->_cmd());
        }

        // The '^' character tells aspell to spell check the entire line.
        fwrite($pipes[0], '^' . $input);
        fclose($pipes[0]);

        // Read stdout.
        $out = '';
        while (!feof($pipes[1])) {
            $out .= fread($pipes[1], 8192);
        }
        fclose($pipes[1]);

        // Read stderr.
        $err = '';
        while (!feof($pipes[2])) {
            $err .= fread($pipes[2], 8192);
        }
        fclose($pipes[2]);

        // We can't rely on the return value of proc_close:
        // http://bugs.php.net/bug.php?id=29123
        proc_close($process);

        if (strlen($out) === 0) {
            throw new Horde_Exception('Spellcheck failed. Command line: ' . $this->_cmd());
        }

        // Parse output.
        $bad = $suggestions = array();
        $lines = explode("\n", $out);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            @list(,$word,) = explode(' ', $line, 3);

            if ($this->_inLocalDictionary($word) || in_array($word, $bad)) {
                continue;
            }

            switch ($line[0]) {
            case '#':
                // Misspelling with no suggestions.
                $bad[] = $word;
                $suggestions[] = array();
                break;

            case '&':
                // Suggestions.
                $bad[] = $word;
                $suggestions[] = array_slice(explode(', ', substr($line, strpos($line, ':') + 2)), 0, $this->_maxSuggestions);
                break;
            }
        }

        return array('bad' => $bad, 'suggestions' => $suggestions);
    }

    /**
     * Create the command line string.
     *
     * @return string  The command to run.
     */
    protected function _cmd()
    {
        $args = '--encoding=UTF-8';

        switch ($this->_suggestMode) {
        case self::SUGGEST_FAST:
            $args .= ' --sug-mode=fast';
            break;

        case self::SUGGEST_SLOW:
            $args .= ' --sug-mode=bad-spellers';
            break;

        default:
            $args .= ' --sug-mode=normal';
        }

        $args .= ' --lang=' . escapeshellarg($this->_locale);

        if ($this->_html) {
            $args .= ' -H';
        }

        return 'aspell -a ' . $args;
    }

}
