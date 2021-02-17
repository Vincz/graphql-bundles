<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser;

use ReflectionClass;
use Reflector;

class TypeGuesser implements TypeGuesserInterface
{
    protected iterable $extensions;

    public function __construct(iterable $extensions)
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
