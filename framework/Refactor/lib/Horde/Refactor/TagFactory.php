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

/**
 * Factory for DocBlock\Tag objects.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Refactor
 */
class TagFactory
{
    /**
     * @var array An array with a tag as a key, and an FQCN to a class that
     *     handles it as an array value. The class is expected to inherit this
     *     class.
     */
    protected static $_tagHandlerMappings = array(
        'author'
            => '\phpDocumentor\Reflection\DocBlock\Tag\AuthorTag',
        'covers'
            => '\phpDocumentor\Reflection\DocBlock\Tag\CoversTag',
        'deprecated'
            => '\phpDocumentor\Reflection\DocBlock\Tag\DeprecatedTag',
        'example'
            => '\phpDocumentor\Reflection\DocBlock\Tag\ExampleTag',
        'link'
            => '\phpDocumentor\Reflection\DocBlock\Tag\LinkTag',
        'method'
            => '\phpDocumentor\Reflection\DocBlock\Tag\MethodTag',
        'param'
            => '\phpDocumentor\Reflection\DocBlock\Tag\ParamTag',
        'property-read'
            => '\phpDocumentor\Reflection\DocBlock\Tag\PropertyReadTag',
        'property'
            => '\phpDocumentor\Reflection\DocBlock\Tag\PropertyTag',
        'property-write'
            => '\phpDocumentor\Reflection\DocBlock\Tag\PropertyWriteTag',
        'return'
            => '\phpDocumentor\Reflection\DocBlock\Tag\ReturnTag',
        'see'
            => '\phpDocumentor\Reflection\DocBlock\Tag\SeeTag',
        'since'
            => '\phpDocumentor\Reflection\DocBlock\Tag\SinceTag',
        'source'
            => '\phpDocumentor\Reflection\DocBlock\Tag\SourceTag',
        'throw'
            => '\phpDocumentor\Reflection\DocBlock\Tag\ThrowsTag',
        'throws'
            => '\phpDocumentor\Reflection\DocBlock\Tag\ThrowsTag',
        'uses'
            => '\phpDocumentor\Reflection\DocBlock\Tag\UsesTag',
        'var'
            => '\phpDocumentor\Reflection\DocBlock\Tag\VarTag',
        'version'
            => '\phpDocumentor\Reflection\DocBlock\Tag\VersionTag'
    );

    /**
     * Factory method responsible for instantiating the correct sub type.
     *
     * @param string $name   The tag name.
     * @param string $value  The tag value.
     *
     * @return \phpDocumentor\Reflection\DocBlock\Tag A new tag object.
     */
    public static function create($name, $value)
    {
        $handler = '\phpDocumentor\Reflection\DocBlock\Tag';
        if (isset(self::$_tagHandlerMappings[$name])) {
            $handler = self::$_tagHandlerMappings[$name];
        }
        return new $handler($name, $value);
    }
}
