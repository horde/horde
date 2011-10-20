<?php
/**
 * Command line tool for pushing content to social networks.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Push_Cli
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/gpl GPL-2.0
 * @link     http://www.horde.org/components/Horde_Push_Cli
 */

/**
 * Command line tool for pushing content to social networks.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL-2.0). If you did
 * not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @package  Push_Cli
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/gpl GPL-2.0
 * @link     http://www.horde.org/components/Horde_Push_Cli
 */
class Horde_Push_Cli
{
    /**
     * The main entry point for the application.
     *
     * @param array $parameters A list of named configuration parameters.
     */
    static public function main(array $parameters = array())
    {
        $parser = new Horde_Argv_Parser(
            array('usage' => '%prog [OPTIONS] [SOURCE://ID]')
        );
        $parser->addOptions(
            array(
                new Horde_Argv_Option(
                    '-c',
                    '--config',
                    array(
                        'action' => 'store',
                        'help'   => Horde_Push_Cli_Translation::t('Path to the configuration file.'),
                    )
                ),
                new Horde_Argv_Option(
                    '-S',
                    '--summary',
                    array(
                        'action' => 'store',
                        'help'   => Horde_Push_Cli_Translation::t('A summary replacing the value provided by the source.'),
                    )
                ),
                new Horde_Argv_Option(
                    '-p',
                    '--pretend',
                    array(
                        'action' => 'store_true',
                        'help'   => Horde_Push_Cli_Translation::t('Do not push the content but display what would be done.'),
                    )
                ),
            )
        );
        list($options, $arguments) = $parser->parseArgs();

        global $conf;

        if (isset($options['config']) && file_exists($options['config'])) {
            include $options['config'];
        } else {
            $conf = array('recipients' => array('cli' => true));
        }

        $push_factory = new Horde_Push_Cli_Factory_Push();
        $pushes = $push_factory->create($arguments, $options, $conf);
        $fail = false;
        foreach ($pushes as $push) {
            if (isset($options['summary'])) {
                $push->setSummary($options['summary']);
            }

            $recipient_factory = new Horde_Push_Cli_Factory_Recipients();
            $recipients = $recipient_factory->create($conf);

            foreach ($recipients as $recipient) {
                $push->addRecipient($recipient);
            }

            $results = $push->push(isset($options['pretend']));

            $cli = Horde_Cli::init();
            foreach ($results as $result) {
                if ($result instanceOf Exception) {
                    $cli->red('ERROR: ' . $result->getMessage());
                    $fail = true;
                } else {
                    $cli->green('SUCCESS: ' . (string)$result);
                }
            }
        }
        if ($fail === true) {
            exit(1);
        } else {
            exit(0);
        }
    }
}