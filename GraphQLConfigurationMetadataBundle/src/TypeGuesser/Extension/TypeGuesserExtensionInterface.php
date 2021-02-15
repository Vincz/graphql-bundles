<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\Extension;

use ReflectionClass;
use Reflector;

interface TypeGuesserExtensionInterface
{
    public function supports(Reflector $reflector): bool;

    public function getName(): string;

    public function guessType(ReflectionClass $reflectionClass, Reflector $reflector, array $filterGraphQLTypes = []): ?string;
}
