<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Reader\AttributeReader;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Reader\MetadataReaderInterface;

/**
 * @requires PHP 8.
 */
class ConfigurationAttributeParserTest extends ConfigurationMetadataParserTest
{
    public function getMetadataReader(): MetadataReaderInterface
    {
        return new AttributeReader();
    }
}
