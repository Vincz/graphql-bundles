<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\Extension;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\ClassesTypesMap;
use ReflectionClass;
use Reflector;

abstract class TypeGuesserExtension implements TypeGuesserExtensionInterface
{
    protected ClassesTypesMap $classesTypesMap;

    public function __construct(ClassesTypesMap $classesTypesMap)
    {
        $this->classesTypesMap = $classesTypesMap;
    }

    abstract public function supports(Reflector $reflector): bool;

    abstract public function getName(): string;

    abstract public function guessType(ReflectionClass $reflectionClass, Reflector $reflector, array $filterGraphQLTypes = []): ?string;
}
