<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Scalar;

use DateTimeInterface;
use GraphQL\Language\AST\Node;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;
use function explode;
use function implode;

/**
 * @GQL\Scalar
 * @GQL\Description("The galaxy coordinates scalar")
 */
#[GQL\Scalar]
#[GQL\Description("The galaxy coordinates scalar")]
class GalaxyCoordinates
{
    /**
     * @return string
     */
    public static function serialize(array $coordinates)
    {
        return implode(',', $coordinates);
    }

    /**
     * @param mixed $value
     *
     * @return DateTimeInterface
     */
    public static function parseValue($value)
    {
        return explode(',', $value);
    }

    /**
     * @return DateTimeInterface
     */
    public static function parseLiteral(Node $valueNode)
    {
        return explode(',', $valueNode->value);
    }
}
