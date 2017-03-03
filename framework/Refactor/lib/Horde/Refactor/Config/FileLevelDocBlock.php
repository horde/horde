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

namespace Horde\Refactor\Config;

/**
 * Configuration for the FileLevelDocBlock refactoring rule.
 *
 * The following placeholders are supported in the templates for new DocBlocks:
 * %year%, %license%, %licenseUrl%.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Refactor
 */
class FileLevelDocBlock extends Base
{
    /**
     * Default summary for new, empty file-level DocBlocks.
     *
     * @var string
     */
    public $fileSummary = 'Copyright %year% Horde LLC (http://www.horde.org/)';

    /**
     * Default description for new, empty file-level DocBlocks.
     *
     * @var string
     */
    public $fileDescription = 'See the enclosed file LICENSE for license information (%license%). If you
did not receive this file, see %licenseUrl%.';

    /**
     * Default tags for new, empty file-level DocBlocks.
     *
     * @var string
     */
    public $fileTags = array(
        'author' => '',
        'category' => 'Horde',
        'license' => '%licenseUrl% %license%',
        'package' => '',
    );

    /**
     * Default summary for new, empty class-level DocBlocks.
     *
     * @var string
     */
    public $classSummary = 'Summary';

    /**
     * Default description for new, empty class-level DocBlocks.
     *
     * @var string
     */
    public $classDescription = '';

    /**
     * Default tags for new, empty class-level DocBlocks.
     *
     * @var string
     */
    public $classTags = array(
        'author' => '',
        'category' => 'Horde',
        'copyright' => '%year% Horde LLC',
        'license' => '%licenseUrl% %license%',
        'package' => '',
    );

    /**
     * Default license name for new, empty DocBlocks.
     *
     * @var string
     */
    public $license = '...';

    /**
     * Default license URL for new, empty DocBlocks.
     *
     * @var string
     */
    public $licenseUrl = 'http://www.horde.org/licenses/...';

    /**
     * Default copyright year for new, empty DocBlocks.
     *
     * @var string
     */
    public $year;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     */
    public function __construct(array $params = array())
    {
        $params = array_merge(array('year' => date('Y')), $params);
        parent::__construct($params);
    }
}
