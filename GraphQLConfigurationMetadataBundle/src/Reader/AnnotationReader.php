<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Reader;

use Doctrine\Common\Annotations\AnnotationReader as DotrineAnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\PhpFileCache;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;
use RuntimeException;
use function apcu_enabled;

class AnnotationReader implements MetadataReaderInterface
{
    const METADATA_FORMAT = '@%s';

    protected ?Reader $annotationReader = null;
    protected bool $useCache;

    public function __construct(Reader $annotationReader = null, bool $useCache = true)
    {
        $this->annotationReader = $annotationReader;
        $this->useCache = $useCache;
    }

    public function formatMetadata(string $metadataType): string
    {
        return sprintf(self::METADATA_FORMAT, $metadataType);
    }

    public function getMetadatas(Reflector $reflector): array
    {
        $reader = $this->getAnnotationReader();

        switch (true) {
            case $reflector instanceof ReflectionClass: return $reader->getClassAnnotations($reflector);
            case $reflector instanceof ReflectionMethod: return $reader->getMethodAnnotations($reflector);
            case $reflector instanceof ReflectionProperty: return $reader->getPropertyAnnotations($reflector);
        }

        return [];
    }

    protected function getAnnotationReader(): Reader
    {
        if (null === $this->annotationReader) {
            if (!class_exists(AnnotationReader::class) ||
                !class_exists(AnnotationRegistry::class)) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('In order to use graphql annotations, you need to require doctrine annotations');
                // @codeCoverageIgnoreEnd
            }

            AnnotationRegistry::registerLoader('class_exists');
            $reader = new DotrineAnnotationReader();
            if ($this->useCache) {
                $cacheKey = md5(__DIR__);
                // @codeCoverageIgnoreStart
                if (extension_loaded('apcu') && apcu_enabled()) {
                    $annotationCache = new ApcuCache();
                } else {
                    $annotationCache = new PhpFileCache(join(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), $cacheKey]));
                }
                // @codeCoverageIgnoreEnd
                $annotationCache->setNamespace($cacheKey);

                $this->annotationReader = new CachedReader($reader, $annotationCache, true);
            } else {
                $this->annotationReader = $reader;
            }
        }

        return $this->annotationReader;
    }
}
