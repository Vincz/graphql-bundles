<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle;

use Doctrine\Common\Annotations\Reader;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\ClassesTypesMap;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataParser\MetadataConfiguration;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Reader\MetadataReaderInterface;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use Overblog\GraphQLBundle\Configuration\ConfigurationFilesParser;
use ReflectionClass;
use Reflector;
use SplFileInfo;
use function sprintf;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataConfigurationException;

class ConfigurationMetadataParser extends ConfigurationFilesParser
{
    protected ?Reader $annotationReader = null;
    protected MetadataReaderInterface $metadataReader;
    protected ClassesTypesMap $classesTypesMap;

    protected array $providers = [];
    protected array $resolvers = [];

    public function __construct(MetadataReaderInterface $metadataReader, ClassesTypesMap $classesTypesMap, iterable $resolvers, array $directories = [])
    {
        parent::__construct($directories);

        $this->metadataReader = $metadataReader;
        $this->classesTypesMap = $classesTypesMap;

        $this->resolvers = iterator_to_array($resolvers);
    }

    public function getSupportedExtensions(): array
    {
        return ['php'];
    }

    public function getConfiguration(): array
    {
        $configuration = [];
        $files = $this->getFiles($this->getDirectories(), $this->getSupportedExtensions());

        foreach ($files as $file) {
            $this->parseFileClassMap($file);
        }

        foreach ($files as $file) {
            $configuration = $configuration + $this->parseFile($file);
        }

        $this->classesTypesMap->cache();
        return $configuration;
    }

    protected function parseFileClassMap(SplFileInfo $file)
    {
        return $this->processFile($file, true);
    }

    protected function parseFile(SplFileInfo $file): array
    {
        return $this->processFile($file);
    }

    protected function processFile(SplFileInfo $file, bool $initializeClassesTypesMap = false): ?array
    {
        try {
            $reflectionClass = $this->getFileClassReflection($file);
            $gqlTypes = [];

            foreach ($this->getMetadatas($reflectionClass) as $classMetadata) {
                if ($classMetadata instanceof Metadata\Metadata) {
                    $resolver = $this->getResolver($classMetadata);
                    if ($resolver) {
                        if ($initializeClassesTypesMap) {
                            $resolver->setClassesMap($reflectionClass, $classMetadata, $this->classesTypesMap);
                        } else {
                            $gqlTypes = array_merge($gqlTypes, $resolver->getConfiguration($reflectionClass, $classMetadata));
                        }
                    }
                }
            }

            return $initializeClassesTypesMap ? null : $gqlTypes;
        } catch (\RuntimeException $e) {
            throw new MetadataConfigurationException(sprintf('Failed to parse GraphQL metadata from file "%s".', $file), $e->getCode(), $e);
        }
    }

    protected function getResolver(Metadata\Metadata $classMetadata): ?MetadataConfiguration
    {
        foreach ($this->resolvers as $metadataClass => $resolver) {
            if ($classMetadata instanceof $metadataClass) {
                return $resolver;
            }
        }

        return null;
    }

    protected function getFileClassReflection(SplFileInfo $file): ReflectionClass
    {
        try {
            $className = $file->getBasename('.php');
            if (preg_match('#namespace (.+);#', file_get_contents($file->getRealPath()), $matches)) {
                $className = trim($matches[1]).'\\'.$className;
            }

            return new ReflectionClass($className);
        } catch (\RuntimeException $e) {
            throw new MetadataConfigurationException(sprintf('Failed to parse GraphQL metadata from file "%s".', $file), $e->getCode(), $e);
        }
    }

    protected function getMetadatas(Reflector $reflector)
    {
        return $this->metadataReader->getMetadatas($reflector);
    }
}
