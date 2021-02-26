<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle;

use Doctrine\Common\Annotations\Reader;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler\MetadataHandler;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Reader\MetadataReaderInterface;
use Overblog\GraphQLBundle\Configuration\Configuration;
use Overblog\GraphQLBundle\ConfigurationProvider\ConfigurationFilesParser;
use ReflectionClass;
use Reflector;
use RuntimeException;
use SplFileInfo;
use function sprintf;

class ConfigurationMetadataParser extends ConfigurationFilesParser
{
    protected ?Reader $annotationReader = null;
    protected MetadataReaderInterface $metadataReader;
    protected ClassesTypesMap $classesTypesMap;

    protected Configuration $configuration;

    protected array $providers = [];
    protected array $resolvers = [];

    public function __construct(MetadataReaderInterface $metadataReader, ClassesTypesMap $classesTypesMap, iterable $resolvers, array $directories = [])
    {
        parent::__construct($directories);
        $this->configuration = new Configuration();
        $this->metadataReader = $metadataReader;
        $this->classesTypesMap = $classesTypesMap;

        $this->resolvers = iterator_to_array($resolvers);
    }

    public function getSupportedExtensions(): array
    {
        return ['php'];
    }

    public function getConfiguration(): Configuration
    {
        $files = $this->getFiles();

        foreach ($files as $file) {
            $this->parseFileClassMap($file);
        }

        foreach ($files as $file) {
            $this->parseFile($file);
        }

        $this->classesTypesMap->cache();

        return $this->configuration;
    }

    protected function parseFileClassMap(SplFileInfo $file): void
    {
        $this->processFile($file, true);
    }

    protected function parseFile(SplFileInfo $file): void
    {
        $this->processFile($file);
    }

    protected function processFile(SplFileInfo $file, bool $initializeClassesTypesMap = false): void
    {
        try {
            $reflectionClass = $this->getFileClassReflection($file);

            foreach ($this->getMetadatas($reflectionClass) as $classMetadata) {
                if ($classMetadata instanceof Metadata\Metadata) {
                    $resolver = $this->getResolver($classMetadata);
                    if ($resolver) {
                        if ($initializeClassesTypesMap) {
                            $resolver->setClassesMap($reflectionClass, $classMetadata, $this->classesTypesMap);
                        } else {
                            $resolver->addConfiguration($this->configuration, $reflectionClass, $classMetadata);
                        }
                    }
                }
            }
        } catch (RuntimeException $e) {
            throw new MetadataConfigurationException(sprintf('Failed to parse GraphQL metadata from file "%s".', $file), $e->getCode(), $e);
        }
    }

    protected function getResolver(Metadata\Metadata $classMetadata): ?MetadataHandler
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
        } catch (RuntimeException $e) {
            throw new MetadataConfigurationException(sprintf('Failed to parse GraphQL metadata from file "%s".', $file), $e->getCode(), $e);
        }
    }

    protected function getMetadatas(Reflector $reflector)
    {
        return $this->metadataReader->getMetadatas($reflector);
    }
}
