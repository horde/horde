<?php
/**
 * Copyright 2005-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2005-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   SpellChecker
 */

/**
 * A spellcheck driver for the aspell/ispell binary.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2005-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   SpellChecker
 */
class Horde_SpellChecker_Aspell extends Horde_SpellChecker
{
    /**
     * @param array $args  Additional arguments:
     *   - path: (string) Path to the aspell binary.
     */
    public function __construct(array $args = array())
    {
        parent::__construct(array_merge(array(
            'path' => 'aspell'
        ), $args));
    }

    /**
     */
    public function spellCheck($text)
    {
        $ret = array(
            'bad' => array(),
            'suggestions' => array()
        );

        if ($this->_params['html']) {
            $input = strtr($text, "\n", ' ');
        } else {
            $words = $this->_getWords($text);
            if (!count($words)) {
                return $ret;
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
            throw new Horde_SpellChecker_Exception('Spellcheck failed. Command line: ' . $this->_cmd());
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
            throw new Horde_SpellChecker_Exception('Spellcheck failed. Command line: ' . $this->_cmd());
        }

        // Parse output.
        foreach (array_map('trim', explode("\n", $out)) as $line) {
            if (!strlen($line)) {
                continue;
            }

            @list(,$word,) = explode(' ', $line, 3);

            if ($this->_inLocalDictionary($word) ||
                in_array($word, $ret['bad'])) {
                continue;
            }

            switch ($line[0]) {
            case '#':
                // Misspelling with no suggestions.
                $ret['bad'][] = $word;
                $ret['suggestions'][] = array();
                break;

            case '&':
                // Suggestions.
                $ret['bad'][] = $word;
                $ret['suggestions'][] = array_slice(explode(', ', substr($line, strpos($line, ':') + 2)), 0, $this->_params['maxSuggestions']);
                break;
            }
        }

        return $ret;
    }

    /**
     * Create the command line string.
     *
     * @return string  The command to run.
     */
    protected function _cmd()
    {
        $args = array('-a', '--encoding=UTF-8');

        switch ($this->_params['suggestMode']) {
        case self::SUGGEST_FAST:
            $args[] = '--sug-mode=fast';
            break;

        case self::SUGGEST_SLOW:
            $args[] = '--sug-mode=bad-spellers';
            break;

        default:
            $args[] = '--sug-mode=normal';
        }

        $args[] = '--lang=' . escapeshellarg($this->_params['locale']);
        $args[] = '--ignore=' . escapeshellarg(max($this->_params['minLength'] - 1, 0));

        if ($this->_params['html']) {
            $args[] = '-H';
            $args[] = '--rem-html-check=alt';
        }

        return escapeshellcmd($this->_params['path']) . ' ' .
               implode(' ', $args);
    }

}
