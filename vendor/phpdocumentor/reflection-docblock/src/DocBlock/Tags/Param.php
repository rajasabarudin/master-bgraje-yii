<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link      http://phpdoc.org
 */

namespace phpDocumentor\Reflection\DocBlock\Tags;

use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Context as TypeContext;
use Webmozart\Assert\Assert;
use const PREG_SPLIT_DELIM_CAPTURE;
use function array_shift;
use function implode;
use function preg_split;
use function strpos;
use function substr;

/**
 * Reflection class for the {@}param tag in a Docblock.
 */
final class Param extends BaseTag implements Factory\StaticMethod
{
    /** @var string */
    protected $name = 'param';

    /** @var Type|null */
    private $type;

    /** @var string|null */
    private $variableName;

    /** @var bool determines whether this is a variadic argument */
    private $isVariadic;

    public function __construct(
        ?string $variableName,
        ?Type $type = null,
        bool $isVariadic = false,
        ?Description $description = null
    ) {
        $this->variableName = $variableName;
        $this->type         = $type;
        $this->isVariadic   = $isVariadic;
        $this->description  = $description;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(
        string $body,
        ?TypeResolver $typeResolver = null,
        ?DescriptionFactory $descriptionFactory = null,
        ?TypeContext $context = null
    ) : self {
        Assert::stringNotEmpty($body);
        Assert::notNull($typeResolver);
        Assert::notNull($descriptionFactory);

        $parts = preg_split('/(\s+)/Su', $body, 3, PREG_SPLIT_DELIM_CAPTURE);
        Assert::isArray($parts);
        $type         = null;
        $variableName = '';
        $isVariadic   = false;

        // if the first item that is encountered is not a variable; it is a type
        if (isset($parts[0]) && ($parts[0] !== '') && ($parts[0][0] !== '$')) {
            $type = $typeResolver->resolve(array_shift($parts), $context);
            array_shift($parts);
        }

        // if the next item starts with a $ or ...$ it must be the variable name
        if (isset($parts[0]) && ($parts[0] !== '') &&
            (strpos($parts[0], '$') === 0 || strpos($parts[0], '...$') === 0)
        ) {
            $variableName = array_shift($parts);
            array_shift($parts);

            if ($variableName !== null && strpos($variableName, '...') === 0) {
                $isVariadic   = true;
                $variableName = substr($variableName, 3);
            }

            if ($variableName !== null && strpos($variableName, '$') === 0) {
                $variableName = substr($variableName, 1);
            }
        }

        $description = $descriptionFactory->create(implode('', $parts), $context);

        return new static($variableName, $type, $isVariadic, $description);
    }

    /**
     * Returns the variable's name.
     */
    public function getVariableName() : ?string
    {
        return $this->variableName;
    }

    /**
     * Returns the variable's type or null if unknown.
     */
    public function getType() : ?Type
    {
        return $this->type;
    }

    /**
     * Returns whether this tag is variadic.
     */
    public function isVariadic() : bool
    {
        return $this->isVariadic;
    }

    /**
     * Returns a string representation for this tag.
     */
    public function __toString() : string
    {
        return ($this->type ? $this->type . ' ' : '')
            . ($this->isVariadic() ? '...' : '')
            . ($this->variableName !== null ? '$' . $this->variableName : '')
            . ($this->description ? ' ' . $this->description : '');
    }
}
