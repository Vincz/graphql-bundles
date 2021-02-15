<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser;

use ReflectionClass;
use Reflector;

interface TypeGuesserInterface
{
    public function guessType(ReflectionClass $reflectionClass, Reflector $reflector, array $filterGraphQLTypes = []): ?string;
}
