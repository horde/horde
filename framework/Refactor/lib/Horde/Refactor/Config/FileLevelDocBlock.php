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
     * Regular expression to verify summary for file-level DocBlocks.
     *
     * @var string
     */
    public $fileSummaryRegexp = '/Copyright \d{4}(-\d{4})? Horde LLC \(http:\/\/www\.horde\.org\/\)/';

    /**
     * Default description for new, empty file-level DocBlocks.
     *
     * @var string
     */
    public $fileDescription = 'See the enclosed file LICENSE for license information (%license%). If you
did not receive this file, see %licenseUrl%.';

    /**
     * Regular expression to verify description for file-level DocBlocks.
     *
     * @var string
     */
    public $fileDescriptionRegexp = '/See the enclosed file (LICENSE|COPYING) for license information \((GPL|LGPL(-21)?|BSD|ASL)\)\. If you
did not receive this file, see https?:\/\/www\.horde\.org\/licenses\/(gpl|lgpl(21)?|bsd|apache)\./';

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
     * Forbidden tags for file-level DocBlocks.
     *
     * @var string
     */
    public $fileForbiddenTags = array('copyright');

    /**
     * Default summary for new, empty class-level DocBlocks.
     *
     * @var string
     */
    public $classSummary = 'Summary';

    /**
     * Regular expression to verify summary for file-level DocBlocks.
     *
     * @var string
     */
    public $classSummaryRegexp = '/.+/';

    /**
     * Default description for new, empty class-level DocBlocks.
     *
     * @var string
     */
    public $classDescription = '';

    /**
     * Regular expression to verify description for file-level DocBlocks.
     *
     * @var string
     */
    public $classDescriptionRegexp = '/./';

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
     * Forbidden tags for class-level DocBlocks.
     *
     * @var string
     */
    public $classForbiddenTags = array();

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
