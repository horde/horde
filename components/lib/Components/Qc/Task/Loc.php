<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Components
 */

use SebastianBergmann\PHPLOC;
use SebastianBergmann\FinderFacade\FinderFacade;

/**
 * Measure the size and analyze the structure of a PHP component.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Components
 */
class Components_Qc_Task_Loc extends Components_Qc_Task_Base
{
    /**
     * Get the name of this task.
     *
     * @return string The task name.
     */
    public function getName()
    {
        return 'analysis/statistics of component code';
    }

    /**
     * Validate the preconditions required for this release task.
     *
     * @param array $options Additional options.
     *
     * @return array An empty array if all preconditions are met and a list of
     *               error messages otherwise.
     */
    public function validate($options)
    {
        if (!class_exists('SebastianBergmann\\PHPLOC\\Analyser')) {
            return array('PHPLOC is not available!');
        }
    }

    /**
     * Run the task.
     *
     * @param array &$options Additional options.
     *
     * @return integer Number of errors.
     */
    public function run(&$options)
    {
        $finder = new FinderFacade(array(
            realpath($this->_config->getPath())
        ));
        $files = $finder->findFiles();

        $analyser = new PHPLOC\Analyser();
        $count    = $analyser->countFiles($files, true);

        $this->_printResult($count);
    }

    /**
     * Prints a result set.
     *
     * @param array $count
     */
    protected function _printResult(array $count)
    {
        if ($count['directories'] > 0) {
            printf(
                "Directories                                 %10d\n" .
                "Files                                       %10d\n\n",

                $count['directories'],
                $count['files']
            );
        }

        $format = <<<END
Size
  Lines of Code (LOC)                       %10d
  Comment Lines of Code (CLOC)              %10d (%.2f%%)
  Non-Comment Lines of Code (NCLOC)         %10d (%.2f%%)
  Logical Lines of Code (LLOC)              %10d (%.2f%%)
    Classes                                 %10d (%.2f%%)
      Average Class Length                  %10d
      Average Method Length                 %10d
    Functions                               %10d (%.2f%%)
      Average Function Length               %10d
    Not in classes or functions             %10d (%.2f%%)

Complexity
  Cyclomatic Complexity / LLOC              %10.2f
  Cyclomatic Complexity / Number of Methods %10.2f

Dependencies
  Global Accesses                           %10d
    Global Constants                        %10d (%.2f%%)
    Global Variables                        %10d (%.2f%%)
    Super-Global Variables                  %10d (%.2f%%)
  Attribute Accesses                        %10d
    Non-Static                              %10d (%.2f%%)
    Static                                  %10d (%.2f%%)
  Method Calls                              %10d
    Non-Static                              %10d (%.2f%%)
    Static                                  %10d (%.2f%%)

Structure
  Namespaces                                %10d
  Interfaces                                %10d
  Traits                                    %10d
  Classes                                   %10d
    Abstract Classes                        %10d (%.2f%%)
    Concrete Classes                        %10d (%.2f%%)
  Methods                                   %10d
    Scope
      Non-Static Methods                    %10d (%.2f%%)
      Static Methods                        %10d (%.2f%%)
    Visibility
      Public Methods                        %10d (%.2f%%)
      Non-Public Methods                    %10d (%.2f%%)
  Functions                                 %10d
    Named Functions                         %10d (%.2f%%)
    Anonymous Functions                     %10d (%.2f%%)
  Constants                                 %10d
    Global Constants                        %10d (%.2f%%)
    Class Constants                         %10d (%.2f%%)

END;

        printf(
            $format,
            $count['loc'],
            $count['cloc'],
            $count['loc'] > 0 ? ($count['cloc'] / $count['loc']) * 100 : 0,
            $count['ncloc'],
            $count['loc'] > 0 ? ($count['ncloc'] / $count['loc']) * 100 : 0,
            $count['lloc'],
            $count['loc'] > 0 ? ($count['lloc'] / $count['loc']) * 100 : 0,
            $count['llocClasses'],
            $count['lloc'] > 0 ? ($count['llocClasses'] / $count['lloc']) * 100 : 0,
            $count['llocByNoc'],
            $count['llocByNom'],
            $count['llocFunctions'],
            $count['lloc'] > 0 ? ($count['llocFunctions'] / $count['lloc']) * 100 : 0,
            $count['llocByNof'],
            $count['llocGlobal'],
            $count['lloc'] > 0 ? ($count['llocGlobal'] / $count['lloc']) * 100 : 0,
            $count['ccnByLloc'],
            $count['ccnByNom'],
            $count['globalAccesses'],
            $count['globalConstantAccesses'],
            $count['globalAccesses'] > 0 ? ($count['globalConstantAccesses'] / $count['globalAccesses']) * 100 : 0,
            $count['globalVariableAccesses'],
            $count['globalAccesses'] > 0 ? ($count['globalVariableAccesses'] / $count['globalAccesses']) * 100 : 0,
            $count['superGlobalVariableAccesses'],
            $count['globalAccesses'] > 0 ? ($count['superGlobalVariableAccesses'] / $count['globalAccesses']) * 100 : 0,
            $count['attributeAccesses'],
            $count['instanceAttributeAccesses'],
            $count['attributeAccesses'] > 0 ? ($count['instanceAttributeAccesses'] / $count['attributeAccesses']) * 100 : 0,
            $count['staticAttributeAccesses'],
            $count['attributeAccesses'] > 0 ? ($count['staticAttributeAccesses'] / $count['attributeAccesses']) * 100 : 0,
            $count['methodCalls'],
            $count['instanceMethodCalls'],
            $count['methodCalls'] > 0 ? ($count['instanceMethodCalls'] / $count['methodCalls']) * 100 : 0,
            $count['staticMethodCalls'],
            $count['methodCalls'] > 0 ? ($count['staticMethodCalls'] / $count['methodCalls']) * 100 : 0,
            $count['namespaces'],
            $count['interfaces'],
            $count['traits'],
            $count['classes'],
            $count['abstractClasses'],
            $count['classes'] > 0 ? ($count['abstractClasses'] / $count['classes']) * 100 : 0,
            $count['concreteClasses'],
            $count['classes'] > 0 ? ($count['concreteClasses'] / $count['classes']) * 100 : 0,
            $count['methods'],
            $count['nonStaticMethods'],
            $count['methods'] > 0 ? ($count['nonStaticMethods'] / $count['methods']) * 100 : 0,
            $count['staticMethods'],
            $count['methods'] > 0 ? ($count['staticMethods'] / $count['methods']) * 100 : 0,
            $count['publicMethods'],
            $count['methods'] > 0 ? ($count['publicMethods'] / $count['methods']) * 100 : 0,
            $count['nonPublicMethods'],
            $count['methods'] > 0 ? ($count['nonPublicMethods'] / $count['methods']) * 100 : 0,
            $count['functions'],
            $count['namedFunctions'],
            $count['functions'] > 0 ? ($count['namedFunctions'] / $count['functions']) * 100 : 0,
            $count['anonymousFunctions'],
            $count['functions'] > 0 ? ($count['anonymousFunctions'] / $count['functions']) * 100 : 0,
            $count['constants'],
            $count['globalConstants'],
            $count['constants'] > 0 ? ($count['globalConstants'] / $count['constants']) * 100 : 0,
            $count['classConstants'],
            $count['constants'] > 0 ? ($count['classConstants'] / $count['constants']) * 100 : 0
        );

        printf(
            "\nTests\n" .
            "  Classes                                   %10d\n" .
            "  Methods                                   %10d\n",

            $count['testClasses'],
            $count['testMethods']
        );
    }
}
