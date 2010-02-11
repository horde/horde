<?php
/**
 * Horde_Cli:: API for basic command-line functionality/checks.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Cli
 */
class Horde_Cli
{
    /**
     * Are we running on a console?
     *
     * @var boolean
     */
    protected $_console;

    /**
     * The newline string to use.
     *
     * @var string
     */
    protected $_newline;

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
     * The string to mark the beginning of bold text.
     *
     * @var string
     */
    protected $_bold_start = '';

    /**
     * The string to mark the end of bold text.
     *
     * @var string
     */
    protected $_bold_end = '';

    /**
     * The strings to mark the beginning of coloured text.
     *
     * @var string
     */
    protected $_red_start    = '';
    protected $_green_start  = '';
    protected $_yellow_start = '';
    protected $_blue_start   = '';

    /**
     * The strings to mark the end of coloured text.
     *
     * @var string
     */
    protected $_red_end      = '';
    protected $_green_end    = '';
    protected $_yellow_end   = '';
    protected $_blue_end     = '';

    /**
     * Terminal foreground color codes. Not used yet.
     *
     * @var array
     */
    protected $_terminalForegrounds = array(
        'normal'        => "\x1B[0m",
        'black'         => "\x1B[0m",
        'bold'          => "\x1b[1m",
        'red'           => "\x1B[31m",
        'green'         => "\x1B[32m",
        'brown'         => "\x1B[33m",
        'blue'          => "\x1B[34m",
        'magenta'       => "\x1B[35m",
        'cyan'          => "\x1B[36m",
        'lightgray'     => "\x1B[37m",
        'white'         => "\x1B[1m\x1B[37m",
        'darkgray'      => "\x1B[1m\x1B[0m",
        'lightred'      => "\x1B[1m\x1B[31m",
        'lightgreen'    => "\x1B[1m\x1B[32m",
        'yellow'        => "\x1B[1m\x1B[33m",
        'lightblue'     => "\x1B[1m\x1B[34m",
        'lightmagenta'  => "\x1B[1m\x1B[35m",
        'lightcyan'     => "\x1B[1m\x1B[36m",
    );

    /**
     * Terminal background color codes. Not used yet.
     *
     * @var array
     */
    protected $_terminalBackgrounds = array(
        'normal'    => "\x1B[0m",
        'black'     => "\x1B[0m",
        'red'       => "\x1B[41m",
        'green'     => "\x1B[42m",
        'brown'     => "\x1B[43m",
        'blue'      => "\x1B[44m",
        'magenta'   => "\x1B[45m",
        'cyan'      => "\x1B[46m",
        'lightgray' => "\x1B[47m",
    );

    /**
     * Detect the current environment (web server or console) and sets
     * internal values accordingly.
     *
     * The constructor must not be called after init().
     */
    public function __construct()
    {
        $this->_console = $this->runningFromCLI();

        if ($this->_console) {
            $this->_newline = "\n";
            $this->_indent  = '    ';

            $term = getenv('TERM');
            if ($term) {
                if (preg_match('/^(xterm|vt220|linux)/', $term)) {
                    $this->_clearscreen  = "\x1b[2J\x1b[H";
                    $this->_bold_start   = "\x1b[1m";
                    $this->_red_start    = "\x1b[01;31m";
                    $this->_green_start  = "\x1b[01;32m";
                    $this->_yellow_start = "\x1b[01;33m";
                    $this->_blue_start   = "\x1b[01;34m";
                    $this->_bold_end = $this->_red_end = $this->_green_end = $this->_yellow_end = $this->_blue_end = "\x1b[0m";
                } elseif (preg_match('/^vt100/', $term)) {
                    $this->_clearscreen  = "\x1b[2J\x1b[H";
                    $this->_bold_start = "\x1b[1m";
                    $this->_bold_end   = "\x1b[0m";
                }
            }
        } else {
            $this->_newline = '<br />';
            $this->_indent  = str_repeat('&nbsp;', 4);

            $this->_bold_start   = '<strong>';
            $this->_bold_end     = '</strong>';
            $this->_red_start    = '<span style="color:red">';
            $this->_green_start  = '<span style="color:green">';
            $this->_yellow_start = '<span style="color:yellow">';
            $this->_blue_start   = '<span style="color:blue">';
            $this->_red_end = $this->_green_end = $this->_yellow_end = $this->_blue_end = '</span>';
        }

        // We really want to call this at the end of the script, not in the
        // destructor.
        if ($this->_console) {
            register_shutdown_function(array($this, 'shutdown'));
        }
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
     * @param string $text  The text to bold.
     *
     * @return string  The bolded text.
     */
    public function bold($text)
    {
        return $this->_bold_start . $text . $this->_bold_end;
    }

    /**
     * Returns a red version of $text.
     *
     * @param string $text  The text to print in red.
     *
     * @return string  The red text.
     */
    public function red($text)
    {
        return $this->_red_start . $text . $this->_red_end;
    }

    /**
     * Returns a green version of $text.
     *
     * @param string $text  The text to print in green.
     *
     * @return string  The green text.
     */
    public function green($text)
    {
        return $this->_green_start . $text . $this->_green_end;
    }

    /**
     * Returns a blue version of $text.
     *
     * @param string $text  The text to print in blue.
     *
     * @return string  The blue text.
     */
    public function blue($text)
    {
        return $this->_blue_start . $text . $this->_blue_end;
    }

    /**
     * Returns a yellow version of $text.
     *
     * @param string $text  The text to print in yellow.
     *
     * @return string  The yellow text.
     */
    public function yellow($text)
    {
        return $this->_yellow_start . $text . $this->_yellow_end;
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
        $message = wordwrap(str_replace("\n", "\n           ", $message),
                            68, "\n           ", true);

        switch ($type) {
        case 'cli.error':
            $type_message = $this->red('[ ERROR! ] ');
            break;

        case 'cli.warning':
            $type_message = $this->yellow('[  WARN  ] ');
            break;

        case 'cli.success':
            $type_message = $this->green('[   OK   ] ');
            break;

        case 'cli.message':
            $type_message = $this->blue('[  INFO  ] ');
            break;
        }

        $this->writeln($type_message . $message);
    }

    /**
     * Displays a fatal error message.
     *
     * @param string $error  The error text to display.
     */
    public function fatal($error)
    {
        $this->writeln($this->red('===================='));
        $this->writeln();
        $this->writeln($this->red(_("Fatal Error:")));
        $this->writeln($this->red($error));
        $this->writeln();
        $this->writeln($this->red('===================='));
        exit(1);
    }

    /**
     * Prompts for a user response.
     *
     * @todo Horde 4: switch $choices and $default
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
        if ($default !== null) {
            $prompt .= ' [' . $default . ']';
        }

        // Main event loop to capture top level command.
        while (true) {
            // Print out the prompt message.
            $this->writeln($prompt . ' ', !is_array($choices));
            if (is_array($choices) && !empty($choices)) {
                foreach ($choices as $key => $choice) {
                    $this->writeln($this->indent('(' . $this->bold($key) . ') ' . $choice));
                }
                $this->writeln(_("Type your choice: "), true);
                @ob_flush();

                // Get the user choice.
                $response = trim(fgets(STDIN));
                if ($response === '' && $default !== null) {
                    $response = $default;
                }
                if (isset($choices[$response])) {
                    return $response;
                } else {
                    $this->writeln($this->red(sprintf(_("\"%s\" is not a valid choice."), $response)));
                }
            } else {
                @ob_flush();
                $response = trim(fgets(STDIN));
                if ($response === '' && $default !== null) {
                    $response = $default;
                }
                return $response;
            }
        }

        return true;
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
            file_put_contents($vbscript, 'wscript.echo(InputBox("' . addslashes($prompt) . '", "", "password here"))');
            $command = "cscript //nologo " . escapeshellarg($vbscript);
            $password = rtrim(shell_exec($command));
            unlink($vbscript);
        } else {
            $command = '/usr/bin/env bash -c "echo OK"';
            if (rtrim(shell_exec($command)) !== 'OK') {
                trigger_error("Can't invoke bash");
                return;
            }
            $command = '/usr/bin/env bash -c "read -s -p ' . escapeshellarg($prompt) . ' mypassword && echo \$mypassword"';
            $password = rtrim(shell_exec($command));
            echo "\n";
        }

        return $password;
    }

    /**
     * Reads everything that is sent through standard input and returns it
     * as a single string.
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
     * You must not call init() statically before calling the constructor.
     * Either use the singleton() method to retrieve a Horde_Cli object after
     * calling init(), or don't call init() statically.
     *
     * @return Horde_Cli  A Horde_Cli instance.
     */
    static public function init()
    {
        /* Run constructor now because it requires $_SERVER['SERVER_NAME'] to
         * be empty if called with a CGI SAPI. */
        $cli = new self();

        @set_time_limit(0);
        ob_implicit_flush(true);
        ini_set('html_errors', false);
        $_SERVER['HTTP_HOST'] = '127.0.0.1';
        $_SERVER['SERVER_NAME'] = '127.0.0.1';
        $_SERVER['SERVER_PORT'] = '';
        $_SERVER['REMOTE_ADDR'] = '';
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
    static public function runningFromCLI()
    {
        return (PHP_SAPI == 'cli') ||
               (((PHP_SAPI == 'cgi') || (PHP_SAPI == 'cgi-fcgi')) &&
                empty($_SERVER['SERVER_NAME']));
    }

    /**
     * Destroys any session on script end.
     */
    public function shutdown()
    {
        @session_destroy();
    }

}
