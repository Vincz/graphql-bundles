<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Reader;

use Reflector;

interface MetadataReaderInterface
{
    public function formatMetadata(string $metadataType): string;

    public function getMetadatas(Reflector $reflector): array;
}
