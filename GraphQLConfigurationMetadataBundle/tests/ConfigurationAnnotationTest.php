<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Reader\AnnotationReader;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Reader\MetadataReaderInterface;

class ConfigurationAnnotationTest extends ConfigurationMetadataTest
{
    public function getMetadataReader(): MetadataReaderInterface
    {
        return new AnnotationReader(null, false);
    }
}
