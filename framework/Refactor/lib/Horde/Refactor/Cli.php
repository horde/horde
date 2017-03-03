<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Refactor
 */

namespace Horde\Refactor;

use Horde\Refactor\Rule;
use Horde_Argv_Parser;
use Horde_Argv_Option;

/**
 * Command line tool for refactoring of PHP code.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Refactor
 */
class Cli
{
    /**
     * The main entry point for the application.
     */
    public static function main()
    {
        $parser = new Horde_Argv_Parser(
            array('usage' => '%prog [OPTIONS] RefactoringFile.php RefactoringClass')
        );
        $parser->addOptions(
            array(
                new Horde_Argv_Option(
                    '-f',
                    '--file',
                    array(
                        'action' => 'store',
                        'help'   => 'File to be refactored',
                    )
                ),
                new Horde_Argv_Option(
                    '-d',
                    '--directory',
                    array(
                        'action' => 'store',
                        'help'   => 'Directory to be recursively refactored',
                    )
                ),
                new Horde_Argv_Option(
                    '-u',
                    '--update',
                    array(
                        'action' => 'store_true',
                        'help'   => 'Overwrite the refatored files',
                    )
                ),
            )
        );
        list($options, $arguments) = $parser->parseArgs();

        if ((!$options->file && !$options->directory) ||
            count($arguments) != 2) {
            $parser->printHelp();
            return;
        }

        if (!$options->update) {
            $renderer = new \Horde_Text_Diff_Renderer_Unified();
        }
        if ($options->file) {
            $files = array($options->file);
        } else {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $options->directory,
                    \FilesystemIterator::CURRENT_AS_PATHNAME
                    | \FilesystemIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
        }

        require $arguments[0];
        $class = 'Horde\\Refactor\\Rule\\' . $arguments[1];

        foreach ($files as $file) {
            echo "Processing file $file\n";
            $rule = new $class($file);
            $rule->run();
            if ($rule->warnings) {
                echo "WARNING\n";
                foreach ($rule->warnings as $warning) {
                    echo "$warning\n";
                }
            }
            $original = file($file, FILE_IGNORE_NEW_LINES);
            $refactored = explode("\n", trim($rule->dump()));
            if (!array_diff($original, $refactored) &&
                !array_diff($refactored, $original)) {
                echo "Refactoring not necessary\n";
                continue;
            }
            if ($options->update) {
                file_put_contents($file, $rule->dump());
                echo "Updated file\n";
            } else {
                $diff = new \Horde_Text_Diff(
                    'auto',
                    array($original, $refactored)
                );
                echo $renderer->render($diff);
            }
        }
    }
}