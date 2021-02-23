<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler;

use Closure;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\ClassesTypesMap;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Reader\MetadataReaderInterface;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\TypeGuesserInterface;
use Overblog\GraphQLBundle\Configuration\Configuration;
use Overblog\GraphQLBundle\Configuration\ExtensionConfiguration;
use Overblog\GraphQLBundle\Configuration\TypeConfigurationInterface;
use ReflectionClass;
use Reflector;

abstract class MetadataHandler
{
    /**
     * @see https://facebook.github.io/graphql/draft/#sec-Input-and-Output-Types
     */
    const VALID_INPUT_TYPES = [TypeConfigurationInterface::TYPE_SCALAR, TypeConfigurationInterface::TYPE_ENUM, TypeConfigurationInterface::TYPE_INPUT];
    const VALID_OUTPUT_TYPES = [
        TypeConfigurationInterface::TYPE_SCALAR,
        TypeConfigurationInterface::TYPE_OBJECT,
        TypeConfigurationInterface::TYPE_INTERFACE,
        TypeConfigurationInterface::TYPE_UNION,
        TypeConfigurationInterface::TYPE_ENUM
    ];

    protected ClassesTypesMap $classesTypesMap;
    protected MetadataReaderInterface $reader;
    protected TypeGuesserInterface $typeGuesser;
    protected array $schemas;

    public function __construct(ClassesTypesMap $classesTypesMap, MetadataReaderInterface $reader, TypeGuesserInterface $typeGuesser, array $schemas)
    {
        $this->classesTypesMap = $classesTypesMap;
        $this->reader = $reader;
        $this->typeGuesser = $typeGuesser;
        $this->schemas = $schemas;
    }

    abstract public function setClassesMap(ReflectionClass $reflectionClass, Metadata\Metadata $metadata): void;

    abstract public function addConfiguration(Configuration $configuration, ReflectionClass $reflectionClass, Metadata\Metadata $metadata): ?TypeConfigurationInterface;

    /**
     * Get the name of the default schema
     * @return string
     */
    protected function getDefaultSchemaName(): string
    {
        return isset($this->schemas['default']) ? 'default' : array_key_first($this->schemas);
    }

    /**
     * Get a schema configuration by its name
     * @param string $schemaName
     * @return null|array
     */
    protected function getSchema(string $schemaName): ?array
    {
        return $this->schemas[$schemaName] ?? null;
    }

    /**
     * Get the description from array of metadata
     */
    protected function getDescription(array $metadatas): ?string
    {
        $descriptionMeta = $this->getFirstMetadataMatching($metadatas, Metadata\Description::class);
        
        return $descriptionMeta !== null ? $descriptionMeta->description : null;
    }

    /**
     * Get the deprecation from array of metadata
     */
    protected function getDeprecation(array $metadatas): ?string
    {
        $deprecatedMeta = $this->getFirstMetadataMatching($metadatas, Metadata\Deprecated::class);

        return $deprecatedMeta !== null ? $deprecatedMeta->reason : null;
    }

    /**
     * Get the extensions associated to the metadatas
     */
    protected function getExtensions(array $metadatas): array
    {
        $extensionsMeta = $this->getMetadataMatching($metadatas, Metadata\Extension::class);
        $extensions = [];
        foreach ($extensionsMeta as $extensionMeta) {
            $extensions[] = new ExtensionConfiguration($extensionMeta->name, $extensionMeta->configuration);
        }

        return $extensions;
    }

    /**
     * @return ReflectionProperty[]
     */
    protected function getClassProperties(ReflectionClass $reflectionClass): array
    {
        $properties = [];
        do {
            foreach ($reflectionClass->getProperties() as $property) {
                if (isset($properties[$property->getName()])) {
                    continue;
                }
                $properties[$property->getName()] = $property;
            }
        } while ($reflectionClass = $reflectionClass->getParentClass());

        return $properties;
    }

    /**
     * Get metadata for given object
     */
    protected function getMetadatas(Reflector $reflector)
    {
        return $this->reader->getMetadatas($reflector);
    }

    /**
     * Format an metadata type to be used in errors text
     */
    protected function formatMetadata(string $metadataType): string
    {
        return $this->reader->formatMetadata($metadataType);
    }

    /**
     * Get the first metadata matching given class.
     *
     * @phpstan-template T of object
     * @phpstan-param class-string<T>|class-string<T>[] $metadataClasses
     * @phpstan-return T|null
     *
     * @return object|null
     */
    protected function getFirstMetadataMatching(array $metadatas, $metadataClasses)
    {
        $metas = $this->getMetadataMatching($metadatas, $metadataClasses);

        return array_shift($metas);
    }

    /**
     * Return the metadata matching given class
     *
     * @phpstan-template T of object
     * @phpstan-param class-string<T>|class-string<T>[] $metadataClasses
     *
     * @return array
     */
    protected function getMetadataMatching(array $metadatas, $metadataClasses)
    {
        if (is_string($metadataClasses)) {
            $metadataClasses = [$metadataClasses];
        }

        return array_filter($metadatas, function ($metadata) use ($metadataClasses) {
            foreach ($metadataClasses as $metadataClass) {
                if ($metadata instanceof $metadataClass) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Format a namespace to be used in an expression (double escape).
     */
    protected function formatNamespaceForExpression(string $namespace): string
    {
        return str_replace('\\', '\\\\', $namespace);
    }

    /**
     * Format an expression (ie. add "@=" if not set).
     */
    protected function formatExpression(string $expression): string
    {
        return '@=' === substr($expression, 0, 2) ? $expression : sprintf('@=%s', $expression);
    }

    /**
     * Suffix a name if it is not already.
     */
    protected function suffixName(string $name, string $suffix): string
    {
        return substr($name, -strlen($suffix)) === $suffix ? $name : sprintf('%s%s', $name, $suffix);
    }
}
