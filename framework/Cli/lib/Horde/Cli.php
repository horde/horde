<?php
/**
 * Copyright 2003-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Cli
 */

/**
 * Horde_Cli API for basic command-line functionality/checks.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2003-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Cli
 */
class Horde_Cli
{
    /**
     * The newline string to use.
     *
     * @var string
     */
    protected $_newline;

    /**
     * The formatted space string to use.
     *
     * @var string
     */
    protected $_space;

    /**
     * The string to use for clearing the screen.
     *
     * @var string
     */
    protected $_clearscreen = '';

    /**
     * The indent string to use.
     *
     * @var string
     */
    protected $_indent;

    /**
     * The color formatter.
     *
     * @var Horde_Cli_Color
     */
    protected $_color;

    /**
     * The terminal width.
     *
     * @var integer
     */
    protected $_width;

    /**
     * Detect the current environment (web server or console) and sets
     * internal values accordingly.
     *
     * Use init() if you also want to set environment variables that may be
     * missing in a CLI environment.
     */
    public function __construct()
    {
        $this->_color = new Horde_Cli_Color();
        $console = $this->runningFromCLI();

        if ($console) {
            $this->_newline = PHP_EOL;
            $this->_space = ' ';
            if (getenv('TERM')) {
                $this->_clearscreen  = "\x1b[2J\x1b[H";
            }
            $this->_setWidth();
        } else {
            $this->_newline = '<br />';
            $this->_space = '&nbsp;';
        }
        $this->_indent = str_repeat($this->_space, 4);

        // We really want to call this at the end of the script, not in the
        // destructor.
        if ($console) {
            register_shutdown_function(array($this, 'shutdown'));
        }
    }

    /**
     * Retuns the detected terminal screen width.
     *
     * Defaults to 80 if the width cannot be detected automatically.
     *
     * @since Horde_Cli 2.2.0
     *
     * @return integer  The terminal screen width or null if not a terminal.
     */
    public function getWidth()
    {
        return $this->_width;
    }

    /**
     * Prints $text on a single line.
     *
     * @param string $text  The text to print.
     * @param boolean $pre  If true the linebreak is printed before
     *                      the text instead of after it.
     */
    public function writeln($text = '', $pre = false)
    {
        if ($pre) {
            echo $this->_newline . $text;
        } else {
            echo $text . $this->_newline;
        }
    }

    /**
     * Clears the entire screen, if possible.
     */
    public function clearScreen()
    {
        echo $this->_clearscreen;
    }

    /**
     * Returns the indented string.
     *
     * @param string $text  The text to indent.
     *
     * @return string  The indented text.
     */
    public function indent($text)
    {
        return $this->_indent . $text;
    }

    /**
     * Returns a bold version of $text.
     *
     * @deprecated Use Horde_Cli_Color instead.
     *
     * @param string $text  The text to bold.
     *
     * @return string  The bolded text.
     */
    public function bold($text)
    {
        return $this->_color->bold($text);
    }

    /**
     * Returns a colored version of $text.
     *
     * @since Horde_Cli 2.1.0
     *
     * @param string $color  The color to use. @see $_foregroundColors
     * @param string $text   The text to print in this color.
     *
     * @return string  The colored text.
     */
    public function color($color, $text)
    {
        return $this->_color->color($color, $text);
    }

    /**
     * Returns a red version of $text.
     *
     * @deprecated Use Horde_Cli_Color or color() instead.
     *
     * @param string $text  The text to print in red.
     *
     * @return string  The red text.
     */
    public function red($text)
    {
        return $this->_color->red($text);
    }

    /**
     * Returns a green version of $text.
     *
     * @deprecated Use Horde_Cli_Color or color() instead.
     *
     * @param string $text  The text to print in green.
     *
     * @return string  The green text.
     */
    public function green($text)
    {
        return $this->_color->green($text);
    }

    /**
     * Returns a blue version of $text.
     *
     * @deprecated Use Horde_Cli_Color or color() instead.
     *
     * @param string $text  The text to print in blue.
     *
     * @return string  The blue text.
     */
    public function blue($text)
    {
        return $this->_color->blue($text);
    }

    /**
     * Returns a yellow version of $text.
     *
     * @deprecated Use Horde_Cli_Color or color() instead.
     *
     * @param string $text  The text to print in yellow.
     *
     * @return string  The yellow text.
     */
    public function yellow($text)
    {
        return $this->_color->yellow($text);
    }

    /**
     * Creates a header from a string by drawing character lines above or below
     * the header content.
     *
     * @since Horde_Cli 2.1.0
     *
     * @param string $message  A message to turn into a header.
     * @param string $below    Character to use for drawing the line below the
     *                         message.
     * @param string $above    Character to use for drawing the line above the
     *                         message.
     */
    public function header($message, $below = '-', $above = null)
    {
        $length = 0;
        foreach (explode($this->_newline, $this->_color->remove($message)) as $line) {
            $length = max($length, Horde_String::length($line));
        }
        if ($width = $this->getWidth()) {
            $length = min($length, $width);
        }
        if (strlen($above)) {
            $this->writeln(str_repeat($above, $length));
        }
        $this->writeln(wordwrap($message, $length, $this->_newline));
        if (strlen($below)) {
            $this->writeln(str_repeat($below, $length));
        }
    }

    /**
     * Displays a message.
     *
     * @param string $event  The message string.
     * @param string $type   The type of message: 'cli.error', 'cli.warning',
     *                       'cli.success', or 'cli.message'.
     */
    public function message($message, $type = 'cli.message')
    {
        if ($width = $this->getWidth()) {
            $indent = $this->_newline . str_repeat($this->_space, 11);
            $message = wordwrap(
                str_replace($this->_newline, $indent, $message),
                $width - 12,
                $indent,
                true
            );
        }

        switch ($type) {
        case 'cli.error':
            $this->writeln(
                $this->_color->lightgray(
                    $this->block(
                        '[' . $this->_space . 'ERROR!' . $this->_space . '] '
                            . $message,
                        'red'
                    )
                )
            );
            break;

        case 'cli.warning':
            $this->writeln(
                $this->_color->black(
                    $this->block(
                        '[' . $this->_space . $this->_space . 'WARN'
                            . $this->_space . $this->_space . '] '
                            . $message,
                        'brown'
                    )
                )
            );
            break;

        case 'cli.success':
            $this->writeln(
                $this->_color->black(
                    $this->block(
                        '[' . $this->_space . $this->_space . $this->_space
                            . 'OK' . $this->_space . $this->_space
                            . $this->_space . '] '
                            . $message,
                        'green'
                    )
                )
            );
            break;

        case 'cli.message':
            $this->writeln(
                $this->_color->lightgray(
                    $this->block(
                        '[' . $this->_space . $this->_space . 'INFO'
                            . $this->_space . $this->_space . '] '
                            . $message,
                        'blue'
                    )
                )
            );
            break;

        default:
            $this->writeln($message);
            break;
        }
    }

    /**
     * Displays a fatal error message.
     *
     * @param mixed $error  The error text to display, an exception or an
     *                      object with a getMessage() method.
     */
    public function fatal($error)
    {
        if ($error instanceof Throwable ||
            $error instanceof Exception) {
            $trace = $error;
        } else {
            $trace = debug_backtrace();
        }
        $backtrace = new Horde_Support_Backtrace($trace);
        $details = null;
        if (is_object($error)) {
            $tmp = $error;
            while (!isset($tmp->details) && isset($tmp->previous)) {
                $tmp = $tmp->previous;
            }
            if (isset($tmp->details)) {
                $details = $tmp->details;
            }
        }
        $location = '';
        if (is_object($error) && method_exists($error, 'getMessage')) {
            $first = $error;
            while (method_exists($first, 'getPrevious') &&
                   $previous = $first->getPrevious()) {
                $first = $previous;
            }
            $file = method_exists($first, 'getFile') ? $first->getFile() : null;
            $line = method_exists($first, 'getLine') ? $first->getLine() : null;
            if ($file) {
                $location .= sprintf(Horde_Cli_Translation::t("In %s"), $file);
            }
            if ($line) {
                $location .= sprintf(Horde_Cli_Translation::t(" on line %d"), $line);
            }
            $error = $error->getMessage();
        }

        $lines = array('');
        $lines = $this->_addLines(
            $lines, Horde_Cli_Translation::t("Fatal Error:")
        );
        $lines = $this->_addLines($lines, $error);
        if ($details) {
            $this->_addLines($lines, print_r($details, true));
        }
        if ($location) {
            $lines = $this->_addLines($lines, $location);
        }
        $lines = $this->_addLines($lines, '');
        $lines = $this->_addLines($lines, (string)$backtrace);
        $lines = $this->_addLines($lines, '');
        $this->writeln(
            $this->_color->lightgray(
                $this->block(implode($this->_newline, $lines), 'red', '  ')
            )
        );
        exit(1);
    }

    /**
     * Adds content to a line buffer, wrapping long lines if necessary.
     *
     * @param array $buffer    The line buffer.
     * @param string $content  The lines to add.
     *
     * @return string  The updated line buffer.
     */
    protected function _addLines($buffer, $content)
    {
        $width = max(0, $this->getWidth() - 7);
        if ($width) {
            foreach (explode($this->_newline, $content) as $line) {
                $buffer[] = wordwrap($line, $width, "\n   ", true);
            }
        } else {
            $buffer[] = $content;
        }
        return $buffer;
    }

    /**
     * Formats text in a visual block with optional margin.
     *
     * @since Horde_Cli 2.2.0
     *
     * @param string $text    The block text.
     * @param string $color   The background color.
     * @param string $margin  The block margin string.
     * @param integer $width  The block width.
     *
     * @return string  The formatted block.
     */
    public function block($text, $color, $margin = '', $width = null)
    {
        $text = explode($this->_newline, $text);
        if (!$width) {
            $width = 0;
            foreach ($text as $line) {
                $width = max($width, strlen($line));
            }
            if ($maxWidth = $this->getWidth()) {
                $width = min($width, $maxWidth - 2 * strlen($margin));
            }
        }
        foreach ($text as &$line) {
            $line = $this->_color->background(
                $color,
                $margin . sprintf('%-' . $width . 's', $line) . $margin
            );
        }
        return implode($this->_newline, $text);
    }

    /**
     * Prompts for a user response.
     *
     * @todo Horde 5: switch $choices and $default
     *
     * @param string $prompt   The message to display when prompting the user.
     * @param array $choices   The choices available to the user or null for a
     *                         text input.
     * @param string $default  The default value if no value specified.
     *
     * @return mixed  The user's response to the prompt.
     */
    public function prompt($prompt, $choices = null, $default = null)
    {
        $width = $this->getWidth();

        if (!is_array($choices) || empty($choices)) {
            if ($default !== null) {
                $prompt .= ' [' . $default . ']';
            }
            if ($width) {
                $prompt = wordwrap($prompt, $width);
            }
            $this->writeln($prompt . ' ', true);
            @ob_flush();
            $response = trim(fgets(STDIN));
            if ($response === '' && $default !== null) {
                $response = $default;
            }
            return $response;
        }

        if ($width) {
            $prompt = wordwrap($prompt, $width);
        }

        // Main event loop to capture top level command.
        while (true) {
            // Print out the prompt message.
            $this->writeln($prompt . ' ', !is_array($choices));
            foreach ($choices as $key => $choice) {
                $this->writeln(
                    $this->indent('(' . $this->_color->bold($key) . ') ' . $choice)
                );
            }
            $question = Horde_Cli_Translation::t("Type your choice");
            if ($default !== null) {
                $question .= ' [' . $default . ']';
            }
            $this->writeln($question . ': ', true);
            @ob_flush();

            // Get the user choice.
            $response = trim(fgets(STDIN));
            if ($response === '' && $default !== null) {
                $response = $default;
            }
            if (isset($choices[$response])) {
                return $response;
            } else {
                $this->writeln($this->_color->red(sprintf(
                    Horde_Cli_Translation::t(
                        "\"%s\" is not a valid choice."
                    ),
                    $response
                )));
            }
        }
    }

    /**
     * Interactively prompts for input without echoing to the terminal.
     * Requires a bash shell or Windows and won't work with safe_mode settings
     * (uses shell_exec).
     *
     * From: http://www.sitepoint.com/blogs/2009/05/01/interactive-cli-password-prompt-in-php/
     *
     * @param string $prompt  The message to display when prompting the user.
     *
     * @return string  The user's response to the prompt.
     */
    public function passwordPrompt($prompt)
    {
        $prompt .= ' ';

        if (preg_match('/^win/i', PHP_OS)) {
            $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
            file_put_contents(
                $vbscript,
                'wscript.echo(InputBox("' . addslashes($prompt)
                . '", "", "password here"))'
            );
            $command = "cscript //nologo " . escapeshellarg($vbscript);
            $password = rtrim(shell_exec($command));
            unlink($vbscript);
        } else {
            $command = '/usr/bin/env bash -c "echo OK"';
            if (rtrim(shell_exec($command)) !== 'OK') {
                /* Cannot spawn shell, fall back to standard prompt. */
                return $this->prompt($prompt);
            }
            $command = '/usr/bin/env bash -c "read -s -p '
                . escapeshellarg($prompt) . ' mypassword && echo \$mypassword"';
            $password = rtrim(shell_exec($command));
            echo $this->_newline;
        }

        return $password;
    }

    /**
     * Reads everything that is sent through standard input and returns it as a
     * single string.
     *
     * @return string  The contents of the standard input.
     */
    public function readStdin()
    {
        $in = '';
        while (!feof(STDIN)) {
            $in .= fgets(STDIN, 1024);
        }
        return $in;
    }

    /**
     * CLI scripts shouldn't timeout, so try to set the time limit to
     * none. Also initialize a few variables in $_SERVER that aren't present
     * from the CLI.
     *
     * @return Horde_Cli  A Horde_Cli instance.
     */
    public static function init()
    {
        /* Run constructor now because it requires $_SERVER['SERVER_NAME'] to
         * be empty if called with a CGI SAPI. */
        $cli = new static();

        @set_time_limit(0);
        ob_implicit_flush(true);
        ini_set('html_errors', false);
        set_exception_handler(array($cli, 'fatal'));
        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = '127.0.0.1';
        }
        if (!isset($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = '127.0.0.1';
        }
        if (!isset($_SERVER['SERVER_PORT'])) {
            $_SERVER['SERVER_PORT'] = '';
        }
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            $_SERVER['REMOTE_ADDR'] = '';
        }
        $_SERVER['PHP_SELF'] = isset($argv) ? $argv[0] : '';
        if (!defined('STDIN')) {
            define('STDIN', fopen('php://stdin', 'r'));
        }
        if (!defined('STDOUT')) {
            define('STDOUT', fopen('php://stdout', 'r'));
        }
        if (!defined('STDERR')) {
            define('STDERR', fopen('php://stderr', 'r'));
        }

        return $cli;
    }

    /**
     * Make sure we're being called from the command line, and not via
     * the web.
     *
     * @return boolean  True if we are, false otherwise.
     */
    public static function runningFromCLI()
    {
        return (PHP_SAPI == 'cli') ||
               (((PHP_SAPI == 'cgi') || (PHP_SAPI == 'cgi-fcgi')) &&
                empty($_SERVER['SERVER_NAME']));
    }

    /**
     * Destroys any session on script end.
     *
     * @todo Rely on session_status() in H6.
     */
    public function shutdown()
    {
        if ((function_exists('session_status') &&
             session_status() == PHP_SESSION_ACTIVE) ||
            (!function_exists('session_status') &&
             session_id())) {
            session_destroy();
        }
    }

    /**
     * Detects the terminal screen width.
     */
    protected function _setWidth()
    {
        $this->_width = getenv('COLUMNS');
        if (!$this->_width) {
            $this->_width = @exec('tput cols 2> /dev/null');
        }
        if (!$this->_width) {
            $size = explode(' ', @exec('stty size 2> /dev/null'));
            if (count($size) == 2) {
                $this->_width = $size[1];
            }
        }
        if (!$this->_width) {
            $this->_width = 80;
        }
    }
}
