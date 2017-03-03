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

        $firstPos = $this->_tokens->key();
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
            $this->_checkDocBlocks($firstPos, $this->_tokens->key());
            return;
        }

        // The file-level DocBlock is missing, create one.
        $this->_createFileLevelBlock($firstPos);
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
        foreach ($this->_fillTemplate($this->_config->fileTags) as $key => $value) {
            $tags[] = TagFactory::create($key, $value);
        }
        $serializer = new Serializer();
        $new .= $serializer->getDocComment($this->_getFileLevelDocBlock($tags));
        $docblock = new DocBlock('');
        $docblock->setText(
            $this->_fillTemplate($this->_config->classSummary)
            . "\n\n" . $this->_fillTemplate($this->_config->classDescription)
        );
        foreach ($this->_fillTemplate($this->_config->classTags) as $key => $value) {
            $docblock->appendTag(TagFactory::create($key, $value));
        }
        $new .= "\n\n" . $serializer->getDocComment($docblock) . "\n";
        $this->_tokens = $this->_tokens->insert(array($new));
    }

    /**
     * Verifies the existing DocBlocks.
     *
     * @param integer $first   Position of the first DocBlock.
     * @param integer $second  Position of the second DocBlock.
     */
    protected function _checkDocBlocks($first, $second)
    {
        $this->_checkDocBlock($first, 'file');
        $this->_checkDocBlock($second, 'class');
    }

    /**
     * Verifies one of the existing DocBlocks.
     *
     * @param integer $pos   Position of the DocBlock.
     * @param string $which  Which DocBlock to verify, either 'file' or 'class'.
     */
    protected function _checkDocBlock($pos, $which)
    {
        $this->_tokens->seek($pos);
        $docblock = new DocBlock($this->_tokens->current()[1]);
        if (!preg_match(
                $this->_config->{$which . 'SummaryRegexp'},
                $docblock->getShortDescription()
            )) {
            $this->_warnings[] = ($which == 'file'
                ? Translation::t(
                    "The file-level DocBlock summary should be like: "
                )
                : Translation::t(
                    "The class-level DocBlock summary should be like: "
                ))
                . $this->_config->{$which . 'Summary'};
        }
        if (!preg_match(
                $this->_config->{$which . 'DescriptionRegexp'},
                $docblock->getLongDescription()
            )) {
            $this->_warnings[] = ($which == 'file'
                ? Translation::t(
                    "The file-level DocBlock description should be like: "
                )
                : Translation::t(
                    "The class-level DocBlock  description should be like: "
                ))
                . $this->_config->{$which . 'Description'};
        }
        foreach (array_keys($this->_config->{$which . 'Tags'}) as $tag) {
            if (!$docblock->hasTag($tag)) {
                $this->_warnings[] = ($which == 'file'
                    ? Translation::t(
                        "The file-level DocBlock tags should include: "
                    )
                    : Translation::t(
                        "The class-level DocBlock tags should include: "
                    ))
                    . $tag;
            }
        }
        foreach ($this->_config->{$which . 'ForbiddenTags'} as $tag) {
            if ($docblock->hasTag($tag)) {
                $this->_warnings[] = ($which == 'file'
                    ? Translation::t(
                        "The file-level DocBlock tags should not include: "
                    )
                    : Translation::t(
                        "The class-level DocBlock tags should not include: "
                    ))
                    . $tag;
            }
        }
    }

    /**
     * Creates a file-level DocBlock based on the first existing DocBlock.
     *
     * @param integer $pos  The position of the first existing DocBlock.
     */
    protected function _createFileLevelBlock($pos)
    {
        $this->_tokens->seek($pos);
        $classDocBlock = new DocBlock($this->current()[1]);
        if ($license = $classDocBlock->getTagsByName('license')) {
            $license = explode(' ', $license[0]->getContent(), 2);
            if (count($license) == 2) {
                $this->_config->licenseUrl = $license[0];
                $this->_config->license = $license[1];
            }
        }
        $tags = array();
        foreach ($this->_fillTemplate($this->_config->fileTags) as $key => $value) {
            if ($classTags = $classDocBlock->getTagsByName($key)) {
                $value = $classTags[0]->getContent();
            }
            $tags[] = TagFactory::create($key, $value);
        }
        $fileDocBlock = $this->_getFileLevelDocBlock($tags);
        $serializer = new Serializer();
        $this->_tokens = $this->_tokens->insert(array(
            $serializer->getDocComment($fileDocBlock),
            "\n\n"
        ));
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
        $docblock = new DocBlock('');
        $docblock->setText(
            $this->_fillTemplate($this->_config->fileSummary)
            . "\n\n" . $this->_fillTemplate($this->_config->fileDescription)
        );
        foreach ($tags as $tag) {
            $docblock->appendTag($tag);
        }
        return $docblock;
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
