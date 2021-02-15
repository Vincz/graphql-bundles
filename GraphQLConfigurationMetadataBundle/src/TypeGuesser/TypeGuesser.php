<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser;

use iterator;
use ReflectionClass;
use Reflector;

class TypeGuesser implements TypeGuesserInterface
{
    protected iterator $extensions;

    public function __construct(iterator $extensions)
    {
        $this->extensions = $extensions;
    }

    public function guessType(ReflectionClass $reflectionClass, Reflector $reflector, array $filterGraphQLTypes = []): ?string
    {
        $errors = [];
        foreach ($this->extensions as $extension) {
            if (!$extension->supports($reflector)) {
                continue;
            }
            try {
                $type = $extension->guessType($reflectionClass, $reflector, $filterGraphQLTypes);

                return $type;
            } catch (TypeGuessingException $exception) {
                $errors[] = sprintf('[%s] %s', $extension->getName(), $exception->getMessage());
            }
        }

        throw new TypeGuessingException(join("\n", $errors));
    }
}
