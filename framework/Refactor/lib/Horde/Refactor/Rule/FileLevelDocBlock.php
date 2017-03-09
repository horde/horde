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

namespace Horde\Refactor\Rule;

use Horde\Refactor\Config;
use Horde\Refactor\Exception;
use Horde\Refactor\Rule;
use Horde\Refactor\TagFactory;
use Horde\Refactor\Translation;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Serializer;

/**
 * Refactors a file to contain a (correct) file-level DocBlock.
 *
 * If there is no file-level DocBlock, the first DocBlock is used as a template
 * to create one.
 *
 * If a file-level DocBlock exists, this one and the following the following
 * first element-level DocBlock are checked for correct content.
 *
 * If no DocBlock exists at all, a default is created. If you want your own
 * defaults, extend this class and overwrite __construct().
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Refactor
 */
class FileLevelDocBlock extends Rule
{
    /**
     * Position of the first DocBlock.
     *
     * @var integer
     */
    protected $_first;

    /**
     * Position of the second DocBlock.
     *
     * @var integer
     */
    protected $_second;

    /**
     * The first DocBlock.
     *
     * @var \phpDocumentor\Reflection\DocBlock
     */
    protected $_firstBlock;

    /**
     * The second DocBlock.
     *
     * @var \phpDocumentor\Reflection\DocBlock
     */
    protected $_secondBlock;

    /**
     * Autoload necessary libraries.
     */
    static public function autoload()
    {
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        } else {
            require_once __DIR__ . '/../../../../bundle/vendor/autoload.php';
        }
    }

    /**
     * Constructor.
     *
     * @param string $file                        Name of the file to parse and
     *                                            refactor.
     * @param Horde\Refactor\Config\Base $config  The rule configuration.
     */
    public function __construct($file, Config\Base $config)
    {
        self::autoload();
        parent::__construct($file, $config);
    }

    /**
     * Applies the actual refactoring to the tokenized code.
     */
    public function run()
    {
        $this->_tokens->rewind();

        // Check if we have DocBlocks at all.
        if (!$this->_tokens->find(
                T_DOC_COMMENT,
                null,
                array('allowed' => array(T_WHITESPACE, T_OPEN_TAG))
            )) {
            $this->_addEmptyBlocks();
            return;
        }

        $this->_first = $this->_tokens->key();
        $this->_firstBlock = new DocBlock($this->_tokens->current()[1]);
        $this->_processDocBlock($this->_firstBlock);
        $this->_tokens->skipWhitespace();
        while ($this->_tokens->matches(T_NAMESPACE) ||
               $this->_tokens->matches(T_USE) ||
               $this->_tokens->matches(T_INCLUDE) ||
               $this->_tokens->matches(T_INCLUDE_ONCE) ||
               $this->_tokens->matches(T_REQUIRE) ||
               $this->_tokens->matches(T_REQUIRE_ONCE)) {
            $this->_tokens->find(';');
            $this->_tokens->skipWhitespace();
        }

        // We have two DocBlocks, check for correctness.
        if ($this->_tokens->matches(T_DOC_COMMENT)) {
            $this->_second = $this->_tokens->key();
            $this->_secondBlock = new DocBlock($this->_tokens->current()[1]);
            $this->_processDocBlock($this->_secondBlock);
            $this->_checkDocBlocks();
            return;
        }

        // The file-level DocBlock is missing, create one.
        $this->_createFileLevelBlock();
        $this->_checkDocBlock('class');
    }

    /**
     * Adds two default DocBlocks at the top of the file.
     *
     * @see $_defaultContent
     */
    protected function _addEmptyBlocks()
    {
        $this->_warnings[] = Translation::t(
            "No DocBlocks found, adding default DocBlocks"
        );
        $this->_tokens->rewind();
        if (!$this->_tokens->find(T_OPEN_TAG)) {
            throw new Exception\NotFound(T_OPEN_TAG);
        }
        $new = '';
        if (strpos($this->_tokens->current()[1], "\n") === false) {
            $new .= "\n";
        }
        $this->_tokens->next();
        $tags = array();
        foreach ($this->_config->fileTags as $key => $value) {
            if (!is_array($value)) {
                $value = array($value);
            }
            foreach ($value as $v) {
                $tags[] = TagFactory::create($key, $this->_fillTemplate($v));
            }
        }
        $serializer = new Serializer();
        $new .= $serializer->getDocComment($this->_getFileLevelDocBlock($tags));
        $docblock = new DocBlock('');
        $docblock->setText(
            $this->_fillTemplate($this->_config->classSummary)
            . "\n\n" . $this->_fillTemplate($this->_config->classDescription)
        );
        foreach ($this->_config->classTags as $key => $value) {
            if (!is_array($value)) {
                $value = array($value);
            }
            foreach ($value as $v) {
                $docblock->appendTag(
                    TagFactory::create($key, $this->_fillTemplate($v))
                );
            }
        }
        $new .= "\n\n" . $serializer->getDocComment($docblock) . "\n";
        $this->_tokens = $this->_tokens->insert(array($new));
    }

    /**
     * Verifies the existing DocBlocks.
     */
    protected function _checkDocBlocks()
    {
        $this->_checkDocBlock('file');
        $this->_checkDocBlock('class');
    }

    /**
     * Verifies one of the existing DocBlocks.
     *
     * @param string $which  Which DocBlock to verify, either 'file' or 'class'.
     */
    protected function _checkDocBlock($which)
    {
        switch ($which) {
        case 'file';
            $warn = Translation::t("file-level");
            $pos = $this->_first;
            $docblock = $this->_firstBlock;
            $otherPos = $this->_second;
            $otherBlock = $this->_secondBlock;
            break;
        case 'class':
            $warn = Translation::t("class-level");
            $pos = $this->_second;
            $docblock = $this->_secondBlock;
            $otherPos = $this->_first;
            $otherBlock = $this->_firstBlock;
            break;
        default:
            throw new InvalidArgumentException();
        }

        $serializer = new Serializer();
        $update = false;

        // Checking the summary.
        if (!preg_match(
                $this->_config->{$which . 'SummaryRegexp'},
                $docblock->getShortDescription()
            )) {
            $this->_warnings[] = sprintf(
                Translation::t("The %s DocBlock summary should be like: "),
                $warn
            )
                . $this->_config->{$which . 'Summary'};
            if (strlen($docblock->getShortDescription()) &&
                $which == 'file') {
                // Move the file-level descriptions to the class level.
                $otherBlock = $this->_getDocBlock(
                    $docblock->getText() . "\n\n" . $otherBlock->getText(),
                    $otherBlock->getTags()
                );
                $this->_tokens = $this->_tokens->splice(
                    $otherPos, 1, array($serializer->getDocComment($otherBlock))
                );
                $this->_secondBlock = $otherBlock;
                $docblock = $this->_getDocBlock('', $docblock->getTags());
            }
            if (!strlen($docblock->getShortDescription()) &&
                strlen($this->_config->{$which . 'Summary'})) {
                $docblock->setText(
                    $this->_fillTemplate($this->_config->{$which . 'Summary'})
                    . "\n\n" . $docblock->getLongDescription()
                );
                $update = true;
            }
        }

        // Checking the description.
        if (!preg_match(
                $this->_config->{$which . 'DescriptionRegexp'},
                $docblock->getLongDescription()
            )) {
            $this->_warnings[] = sprintf(
                Translation::t("The %s DocBlock description should be like: "),
                $warn
            )
                . $this->_config->{$which . 'Description'};
            if (!strlen($docblock->getLongDescription()) &&
                strlen($this->_config->{$which . 'Description'})) {
                $docblock->setText(
                    $docblock->getShortDescription()
                    . "\n\n"
                    . $this->_fillTemplate($this->_config->{$which . 'Description'})
                );
                $update = true;
            }
        }

        // Checking for missing tags.
        $tags = $docblock->getTags();
        foreach ($this->_config->{$which . 'Tags'} as $tag => $value) {
            if (!$docblock->hasTag($tag)) {
                $this->_warnings[] = sprintf(
                    Translation::t("The %s DocBlock tags should include: "),
                    $warn
                )
                    . $tag;
                if ($otherBlock->hasTag($tag)) {
                    $tags = array_merge($tags, $otherBlock->getTagsByName($tag));
                } else {
                    if (!is_array($value)) {
                        $value = array($value);
                    }
                    foreach ($value as $v) {
                        $tags[] = TagFactory::create(
                            $tag, $this->_fillTemplate($v)
                        );
                    }
                }
            }
        }
        if (count($tags) != count($docblock->getTags())) {
            $docblock = $this->_getDocBlock($docblock, $tags);
            $update = true;
        }

        // Checking for forbidden tags.
        $tags = array();
        foreach ($docblock->getTags() as $tag) {
            if (in_array($tag->getName(), $this->_config->{$which . 'ForbiddenTags'})) {
                $this->_warnings[] = sprintf(
                    Translation::t("The %s DocBlock tags should not include: "),
                    $warn
                )
                    . $tag->getName();
            } else {
                $tags[] = $tag;
            }
        }
        if (count($tags) != count($docblock->getTags())) {
            $docblock = $this->_getDocBlock($docblock, $tags);
            $update = true;
        }

        // Update tags order.
        $tags = array();
        $oldTags = $docblock->getTags();
        foreach (array_keys($this->_config->{$which . 'Tags'}) as $tagName) {
            $tmp = array();
            foreach ($oldTags as $tag) {
                if ($tag->getName() == $tagName) {
                    $tags[] = $tag;
                } else {
                    $tmp[] = $tag;
                }
            }
            $oldTags = $tmp;
        }
        foreach ($oldTags as $key => $tag) {
            $tags[] = $tag;
        }
        if ($tags != $docblock->getTags()) {
            $docblock = $this->_getDocBlock($docblock, $tags);
            $update = true;
        }

        // Update DocBlock if necessary.
        if ($update) {
            $this->_tokens = $this->_tokens->splice(
                $pos, 1, array($serializer->getDocComment($docblock))
            );
        }
    }

    /**
     * Creates a file-level DocBlock based on the first existing DocBlock.
     */
    protected function _createFileLevelBlock()
    {
        $serializer = new Serializer();

        $fileLevelSummary = $fileLevelDescription = null;
        if ($this->_config->fileSummaryRegexp != '//' &&
            preg_match(
                $this->_config->fileSummaryRegexp,
                $this->_firstBlock->getText(),
                $match
            )) {
            $fileLevelSummary = $match[0];
            $this->_firstBlock->setText(
                str_replace($match[0], '', $this->_firstBlock->getText())
            );
        }
        if ($this->_config->fileDescriptionRegexp != '//' &&
            preg_match(
                $this->_config->fileDescriptionRegexp,
                $this->_firstBlock->getText(),
                $match
            )) {
            $fileLevelDescription = $match[0];
            $this->_firstBlock->setText(
                str_replace($match[0], '', $this->_firstBlock->getText())
            );
        }
        if ($fileLevelSummary || $fileLevelDescription) {
            $this->_firstBlock->setText(
                trim($this->_firstBlock->getText())
            );
            $this->_tokens = $this->_tokens->splice(
                $this->_first,
                1,
                array($serializer->getDocComment($this->_firstBlock))
            );
            if ($fileLevelSummary) {
                $this->_config->fileSummary = $fileLevelSummary;
            }
            if ($fileLevelDescription) {
                $this->_config->fileDescription = $fileLevelDescription;
            }
        }
        $this->_secondBlock = $this->_firstBlock;

        $tags = array();
        foreach ($this->_config->fileTags as $key => $value) {
            if ($classTags = $this->_firstBlock->getTagsByName($key)) {
                $tags = array_merge($tags, $classTags);
            } else {
                if (!is_array($value)) {
                    $value = array($value);
                }
                foreach ($value as $v) {
                    $tags[] = TagFactory::create($key, $this->_fillTemplate($v));
                }
            }
        }
        $fileDocBlock = $this->_getFileLevelDocBlock($tags);
        $this->_tokens->seek($this->_first);
        $this->_tokens = $this->_tokens->insert(array(
            $serializer->getDocComment($fileDocBlock),
            "\n\n"
        ));
        $this->_firstBlock = $fileDocBlock;
        $this->_second = $this->_first + 2;
    }

    /**
     * Builds a default file-level DocBlock.
     *
     * @param \phpDocumentor\Reflection\DocBlock\Tag[] $tags Tags to add.
     *
     * @return \phpDocumentor\Reflection\DocBlock  A file-level DocBlock.
     */
    protected function _getFileLevelDocBlock(array $tags)
    {
        return $this->_getDocBlock(
            $this->_fillTemplate($this->_config->fileSummary)
            . "\n\n" . $this->_fillTemplate($this->_config->fileDescription),
            $tags
        );
    }

    /**
     * Builds a DocBlock.
     *
     * @param \phpDocumentor\Reflection\DocBlock|string $descriptions
     *     The DocBlock summary and description or the DocBlock to pull those
     *     from.
     * @param \phpDocumentor\Reflection\DocBlock\Tag[] $tags
     *     Tags to add.
     *
     * @return \phpDocumentor\Reflection\DocBlock  A DocBlock.
     */
    protected function _getDocBlock($descriptions, array $tags)
    {
        $docblock = new DocBlock('');
        $docblock->setText(
            $descriptions instanceof DocBlock
                ? $descriptions->getText()
                : $descriptions
        );
        foreach ($tags as $tag) {
            $docblock->appendTag(
                TagFactory::create($tag->getName(), $tag->getContent())
            );
        }
        return $docblock;
    }

    /**
     * Processes an existing DocBlock.
     *
     * Parses any information out of the block that might be required later,
     * and checks for different tag contents if processing the second block.
     *
     * @param \phpDocumentor\Reflection\DocBlock $block  A DocBlock.
     */
    protected function _processDocBlock($block)
    {
        if (preg_match($this->_config->licenseExtractRegexp, $block->getText(), $match)) {
            $this->_config->license = $match[1];
        }
        if (preg_match($this->_config->licenseUrlExtractRegexp, $block->getText(), $match)) {
            $this->_config->licenseUrl = $match[1];
        }
        if (preg_match($this->_config->yearExtractRegexp, $block->getText(), $match)) {
            $this->_config->year = $match[1];
        }
        if ($tags = $block->getTagsByName('copyright')) {
            foreach ($tags as $tag) {
                $copyright = explode(' ', $tag->getContent(), 2);
                if (count($copyright) == 2 &&
                    strpos($this->_config->fileSummary, $copyright[1]) !== false) {
                    $this->_config->year = $copyright[0];
                    break;
                }
            }
        }
        if ($tags = $block->getTagsByName('license')) {
            if (count($tags) > 1) {
                $this->_warnings[] = Translation::t(
                    "More than one @license tag."
                );
            }
            $license = explode(' ', $tags[0]->getContent(), 2);
            if (count($license) == 2) {
                $this->_config->licenseUrl = $license[0];
                $this->_config->license = $license[1];
            }
        }
    }

    /**
     * Fills out the placeholders in DocBlock templates.
     *
     * @param string|array $template  The template(s) to fill out.
     *
     * @return string  The filled template.
     */
    protected function _fillTemplate($template)
    {
        return str_replace(
            array(
                '%year%',
                '%license%',
                '%licenseUrl%',
            ),
            array(
                $this->_config->year,
                $this->_config->license,
                $this->_config->licenseUrl,
            ),
            $template
        );
    }
}
