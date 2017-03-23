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
            $this->_processDocBlockText($this->_firstBlock, 'file');
            $this->_processDocBlockText($this->_secondBlock, 'class');
            $this->_checkDocBlocks();
            return;
        }

        // The file-level DocBlock is missing, create one.
        $this->_processDocBlockText($this->_firstBlock, 'class');
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
        // Checking for different tags.
        $tags = array();
        foreach ($this->_firstBlock->getTags() as $tag) {
            if (!isset($tags[$tag->getName()])) {
                $tags[$tag->getName()] = array();
            }
            $tags[$tag->getName()][] = $tag->getContent();
        }
        foreach ($tags as $name => $values) {
            $secondTags = $this->_secondBlock->getTagsByName($name);
            foreach ($secondTags as $tag) {
                if (!in_array($tag->getContent(), $values)) {
                    $this->_errors[] = sprintf(
                        Translation::t("The DocBlocks contain different values for the @%s tag"),
                        $name
                    );
                }
            }
        }

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
            $other = 'class';
            $otherWarn = Translation::t("class-level");
            $otherPos = $this->_second;
            $otherBlock = $this->_secondBlock;
            break;
        case 'class':
            $warn = Translation::t("class-level");
            $pos = $this->_second;
            $docblock = $this->_secondBlock;
            $other = 'file';
            $otherWarn = Translation::t("file-level");
            $otherPos = $this->_first;
            $otherBlock = $this->_firstBlock;
            break;
        default:
            throw new InvalidArgumentException();
        }

        $serializer = new Serializer();
        $update = false;

        // Cleaning the summary and description.
        $text = $docblock->getText();
        $text = $this->_stripIncorrectText($text, $other);
        if ($text != $docblock->getText()) {
            $this->_warnings[] = sprintf(
                Translation::t("The %s DocBlock contains text that should be in the %s DocBlock"),
                $warn, $otherWarn
            );
            $docblock->setText($text);
            $update = true;
        }

        // Checking the summary.
        if (!preg_match(
                $this->_config->{$which . 'SummaryRegexp'},
                $docblock->getShortDescription()
            )) {
            $this->_warnings[] = sprintf(
                Translation::t("The %s DocBlock summary is not valid"),
                $warn
            );
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
                Translation::t("The %s DocBlock description is not valid"),
                $warn
            );
            if (strlen($this->_config->{$which . 'Description'})) {
                $description = $docblock->getShortDescription()
                    . "\n\n"
                    . $this->_fillTemplate($this->_config->{$which . 'Description'});
                if (strlen($docblock->getLongDescription())) {
                    $description .= "\n\n" . $docblock->getLongDescription();
                }
                $docblock->setText($description);
                $update = true;
            }
        }

        // Checking for duplicate tags.
        $tags = array();
        foreach ($docblock->getTags() as $tag) {
            if (!isset($tags[$tag->getName()])) {
                $tags[$tag->getName()] = array();
            }
            $tags[$tag->getName()][] = $tag->getContent();
        }
        foreach ($tags as $name => &$values) {
            if (count($values) != count(array_unique($values))) {
                $this->_warnings[] = sprintf(
                    Translation::t("The %s DocBlock contains duplicate @%s tags"),
                    $warn, $name
                );
                $values = array_unique($values);
            }
        }
        $newtags = array();
        foreach ($tags as $name => $namedTags) {
            foreach ($namedTags as $value) {
                $newtags[] = TagFactory::create($name, $value);
            }
        }
        if (count($newtags) != count($docblock->getTags())) {
            $docblock = $this->_getDocBlock($docblock, $newtags);
            $update = true;
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
            if (isset($this->_config->{$which . 'ForbiddenTags'}[$tag->getName()])) {
                $test = $this->_config->{$which . 'ForbiddenTags'}[$tag->getName()];
                if (($test instanceof Regexp && $test->match($tag->getContent())) ||
                    $test) {
                    $this->_warnings[] = sprintf(
                        Translation::t("The %s DocBlock tags should not include: "),
                        $warn
                    )
                        . $tag->getName();
                }
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

        $this->_firstBlock->setText(
            $this->_stripIncorrectText($this->_firstBlock->getText(), 'file')
        );
        $this->_tokens = $this->_tokens->splice(
            $this->_first,
            1,
            array($serializer->getDocComment($this->_firstBlock))
        );
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
     * Strips text from summary and description that belongs to the "other"
     * block.
     *
     * @param string $text   The original text.
     * @param string $which  Which DocBlock to verify, either 'file' or 'class'.
     *
     * @return string  The cleaned text.
     */
    protected function _stripIncorrectText($text, $which)
    {
        return trim(
            str_replace(
                array(
                    $this->_config->{$which . 'Summary'},
                    $this->_config->{$which . 'Description'}
                ),
                '',
                $text
            )
        );
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
     * Processes the summary and discription of an existing DocBlock.
     *
     * Parses any information out of the "other" block that might be required
     * later.
     *
     * @param \phpDocumentor\Reflection\DocBlock $block  A DocBlock.
     * @param string $which  Which DocBlock to verify, either 'file' or 'class'.
     */
    protected function _processDocBlockText($block, $which)
    {
        $update = $which == 'file' ? 'class' : 'file';
        foreach (array('Summary', 'Description') as $what) {
            if ($this->_config->{$update . $what . 'ExtractRegexp'} != '//' &&
                preg_match(
                    $this->_config->{$update . $what . 'ExtractRegexp'},
                    $block->getText(),
                    $match
                )) {
                $this->_config->{$update . $what} = $match[0];
            }
        }
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
        foreach (array('license', 'licenseUrl', 'year') as $property) {
            if (preg_match($this->_config->{$property . 'ExtractRegexp'}, $block->getText(), $match)) {
                $this->_config->$property = $match[1];
            }
        }
        if (preg_match_all($this->_config->copyrightExtractRegexp, $block->getText(), $match)) {
            $this->_config->classTags['copyright'] = $match[1];
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
