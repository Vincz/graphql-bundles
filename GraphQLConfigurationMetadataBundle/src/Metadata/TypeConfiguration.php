<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation\Annotation as Meta;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation as Metadata;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataConfigurationException;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\TypeGuessingException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;

use function sprintf;

class TypeConfiguration extends MetadataConfiguration
{
    protected const TYPE = self::GQL_TYPE;

    protected const OPERATION_TYPE_QUERY = 'query';
    protected const OPERATION_TYPE_MUTATION = 'mutation';

    protected array $providers = [];

    protected function getTypeName(ReflectionClass $reflectionClass, Meta $typeMetadata): string
    {
        return $typeMetadata->name ?? $reflectionClass->getShortName();
    }

    public function setClassesMap(ReflectionClass $reflectionClass, Meta $metadata): void
    {
        if ($metadata instanceof Metadata\Provider) {
            $this->addProvider($reflectionClass, $metadata);
        } else {
            $gqlName = $this->getTypeName($reflectionClass, $metadata);
            $this->classesTypesMap->addClassType($gqlName, $reflectionClass->getName(), self::TYPE);
        }
    }

    public function getConfiguration(ReflectionClass $reflectionClass, Meta $typeMetadata): array
    {
        if (!$typeMetadata instanceof Metadata\Type) {
            return [];
        }
        
        $gqlName = $this->getTypeName($reflectionClass, $typeMetadata);
        $currentValue = $this->getOperationType($gqlName) !== null ? sprintf("service('%s')", $this->formatNamespaceForExpression($reflectionClass->getName())) : 'value';

        $gqlConfiguration = $this->graphQLTypeConfigFromAnnotation($reflectionClass, $typeMetadata, $currentValue);
        $providerFields = $this->getGraphQLFieldsFromProviders($reflectionClass, $gqlName);

        $gqlConfiguration['config']['fields'] = array_merge($gqlConfiguration['config']['fields'], $providerFields);

        return [$gqlName => $gqlConfiguration];
    }

    protected function addProvider(ReflectionClass $reflectionClass, Metadata\Provider $providerMetadata)
    {
        $defaultAccessAnnotation = $this->getFirstMetadataMatching($this->getMetadatas($reflectionClass), Metadata\Access::class);
        $defaultIsPublicAnnotation = $this->getFirstMetadataMatching($this->getMetadatas($reflectionClass), Metadata\IsPublic::class);

        $defaultAccess = $defaultAccessAnnotation ? $this->formatExpression($defaultAccessAnnotation->value) : false;
        $defaultIsPublic = $defaultIsPublicAnnotation ? $this->formatExpression($defaultIsPublicAnnotation->value) : false;

        foreach ($reflectionClass->getMethods() as $method) {
            $metadatas = $this->getMetadatas($method);
            $metadata = $this->getFirstMetadataMatching($metadatas, [Metadata\Mutation::class, Metadata\Query::class]);
            if (null === $metadata) {
                continue;
            }

            $targets = $this->getProviderTargetTypes($metadata, $providerMetadata);
            if (null === $targets) {
                throw new MetadataConfigurationException("Unable to find provider targets");
            }

            foreach ($targets as $targetType) {
                if (!isset($this->providers[$targetType])) {
                    $this->providers[$targetType] = [];
                }
                $value = $providerMetadata->service ?? sprintf("service('%s')", $this->formatNamespaceForExpression($reflectionClass->getName()));
                $this->providers[$targetType][] = [
                    'name' => $reflectionClass->getName(),
                    'prefix' => $providerMetadata->prefix,
                    'value' => $value,
                    'defaultAccess' => $defaultAccess,
                    'defaultPublic' => $defaultIsPublic,
                    'method' => $method,
                    'operation' => $metadata instanceof Metadata\Query ? self::OPERATION_TYPE_QUERY : self::OPERATION_TYPE_MUTATION
                ];
            }
        }
    }

    /**
     * Resolve the method target types
     * @param Metadata\Query|Metadata\Mutation $methodMetadata
     * @param Metadata\Provider $providerMetadata
     * @return null|string[]
     */
    protected function getProviderTargetTypes(Meta $methodMetadata, Meta $providerMetadata): ?array
    {
        // Check if types are declared on the method metadata
        $targets = $methodMetadata->targetTypes ?? null;
        if ($targets) {
            return $targets;
        }

        $isQuery = $methodMetadata instanceof Metadata\Query;

        // Check if types are declared on the provider class metadata
        $targets = $isQuery ? $providerMetadata->targetQueryTypes : $providerMetadata->targetMutationTypes;
        if ($targets) {
            return $targets;
        }

        // The target type is the default schema root query or mutation
        $defaultSchema = $this->getSchema($this->getDefaultSchemaName());
        $target = $isQuery ? ($defaultSchema['query'] ?? null) : ($defaultSchema['mutation'] ?? null);
        if ($target) {
            return [$target];
        }

        return null;
    }

    /**
     * Get the operation type associated with the given type
     * if $isDefaultSchema is set, ensure it is also defined in the defaultSchema
     */
    protected function getOperationType(string $type, bool $isDefaultSchema = false):?string
    {
        foreach ($this->schemas as $schemaName => $schema) {
            if (!$isDefaultSchema || $schemaName === $this->getDefaultSchemaName()) {
                if ($type === $schema['query'] ?? null) {
                    return self::OPERATION_TYPE_QUERY;
                } elseif ($type === $schema['mutation'] ?? null) {
                    return self::OPERATION_TYPE_MUTATION;
                }
            }
        }

        return null;
    }

    /**
     * @return array{type: 'relay-mutation-payload'|'object', config: array}
     */
    protected function graphQLTypeConfigFromAnnotation(ReflectionClass $reflectionClass, Metadata\Type $typeMetadata, string $currentValue): array
    {
        $typeConfiguration = [];
        $metadatas = $this->getMetadatas($reflectionClass);

        $fieldsFromProperties = $this->getGraphQLTypeFieldsFromAnnotations($reflectionClass, $this->getClassProperties($reflectionClass), Metadata\Field::class, $currentValue);
        $fieldsFromMethods = $this->getGraphQLTypeFieldsFromAnnotations($reflectionClass, $reflectionClass->getMethods(), Metadata\Field::class, $currentValue);

        $typeConfiguration['fields'] = array_merge($fieldsFromProperties, $fieldsFromMethods);
        $typeConfiguration = $this->getDescriptionConfiguration($metadatas) + $typeConfiguration;

        if (!empty($typeMetadata->interfaces)) {
            $typeConfiguration['interfaces'] = $typeMetadata->interfaces;
        } else {
            $interfaces = array_keys($this->classesTypesMap->searchClassesMapBy(function ($gqlType, $configuration) use ($reflectionClass) {
                ['class' => $interfaceClassName] = $configuration;

                $interfaceMetadata = new ReflectionClass($interfaceClassName);
                if ($interfaceMetadata->isInterface() && $reflectionClass->implementsInterface($interfaceMetadata->getName())) {
                    return true;
                }

                return $reflectionClass->isSubclassOf($interfaceClassName);
            }, self::GQL_INTERFACE));

            sort($interfaces);
            $typeConfiguration['interfaces'] = $interfaces;
        }

        if (isset($typeMetadata->resolveField)) {
            $typeConfiguration['resolveField'] = $this->formatExpression($typeMetadata->resolveField);
        }

        $buildersAnnotations = array_merge($this->getMetadataMatching($metadatas, Metadata\FieldsBuilder::class), $typeMetadata->builders);
        if (!empty($buildersAnnotations)) {
            $typeConfiguration['builders'] = array_map(function ($fieldsBuilderAnnotation) {
                return ['builder' => $fieldsBuilderAnnotation->name, 'builderConfig' => $fieldsBuilderAnnotation->config];
            }, $buildersAnnotations);
        }

        if (isset($typeMetadata->isTypeOf)) {
            $typeConfiguration['isTypeOf'] = $typeMetadata->isTypeOf;
        }

        $publicMetadata = $this->getFirstMetadataMatching($metadatas, Metadata\IsPublic::class);
        if (null !== $publicMetadata) {
            $typeConfiguration['fieldsDefaultPublic'] = $this->formatExpression($publicMetadata->value);
        }

        $accessMetadata = $this->getFirstMetadataMatching($metadatas, Metadata\Access::class);
        if (null !== $accessMetadata) {
            $typeConfiguration['fieldsDefaultAccess'] = $this->formatExpression($accessMetadata->value);
        }

        return ['type' => $typeMetadata->isRelay ? 'relay-mutation-payload' : self::CONFIGURATION_TYPES_MAP[self::TYPE], 'config' => $typeConfiguration];
    }

    /**
     * Create GraphQL type fields configuration based on metadatas.
     *
     * @phpstan-param class-string<Metadata\Field> $fieldMetadataName
     *
     * @param ReflectionProperty[]|ReflectionMethod[] $reflectors
     *
     * @throws AnnotationException
     */
    protected function getGraphQLTypeFieldsFromAnnotations(ReflectionClass $reflectionClass, array $reflectors, string $fieldMetadataName = Metadata\Field::class, string $currentValue = 'value'): array
    {
        $fields = [];

        foreach ($reflectors as $reflector) {
            $fields = array_merge($fields, $this->getTypeFieldConfigurationFromReflector($reflectionClass, $reflector, $fieldMetadataName, $currentValue));
        }

        return $fields;
    }

    /**
     * @phpstan-param ReflectionMethod|ReflectionProperty $reflector
     * @phpstan-param class-string<Metadata\Field> $fieldMetadataName
     *
     * @throws AnnotationException
     *
     * @return array<string,array>
     */
    protected function getTypeFieldConfigurationFromReflector(ReflectionClass $reflectionClass, Reflector $reflector, string $fieldMetadataName, string $currentValue = 'value'): array
    {
        /** @var ReflectionProperty|ReflectionMethod $reflector */
        $metadatas = $this->getMetadatas($reflector);

        $fieldMetadata = $this->getFirstMetadataMatching($metadatas, $fieldMetadataName);
        $accessMetadata = $this->getFirstMetadataMatching($metadatas, Metadata\Access::class);
        $publicMetadata = $this->getFirstMetadataMatching($metadatas, Metadata\IsPublic::class);

        if (null === $fieldMetadata) {
            if (null !== $accessMetadata || null !== $publicMetadata) {
                throw new MetadataConfigurationException(sprintf('The metadatas %s and/or %s defined on "%s" are only usable in addition of metadata %s', $this->formatMetadata('Access'), $this->formatMetadata('Visible'), $reflector->getName(), $this->formatMetadata('Field')));
            }

            return [];
        }

        if ($reflector instanceof ReflectionMethod && !$reflector->isPublic()) {
            throw new MetadataConfigurationException(sprintf('The metadata %s can only be applied to public method. The method "%s" is not public.', $this->formatMetadata('Field'), $reflector->getName()));
        }

        $fieldName = $reflector->getName();
        $fieldConfiguration = [];

        if (isset($fieldMetadata->type)) {
            $fieldConfiguration['type'] = $fieldMetadata->type;
        }

        $fieldConfiguration = $this->getDescriptionConfiguration($metadatas, true) + $fieldConfiguration;

        $args = [];

        /** @var Metadata\Arg[] $argAnnotations */
        $argAnnotations = array_merge($this->getMetadataMatching($metadatas, Metadata\Arg::class), $fieldMetadata->args);

        foreach ($argAnnotations as $arg) {
            $args[$arg->name] = ['type' => $arg->type];

            if (isset($arg->description)) {
                $args[$arg->name]['description'] = $arg->description;
            }

            if (isset($arg->default)) {
                $args[$arg->name]['defaultValue'] = $arg->default;
            }
        }

        if (empty($argAnnotations) && $reflector instanceof ReflectionMethod) {
            $args = $this->guessArgs($reflectionClass, $reflector);
        }

        if (!empty($args)) {
            $fieldConfiguration['args'] = $args;
        }

        $fieldName = $fieldMetadata->name ?? $fieldName;

        if (isset($fieldMetadata->resolve)) {
            $fieldConfiguration['resolve'] = $this->formatExpression($fieldMetadata->resolve);
        } else {
            if ($reflector instanceof ReflectionMethod) {
                $fieldConfiguration['resolve'] = $this->formatExpression(sprintf('call(%s.%s, %s)', $currentValue, $reflector->getName(), $this->formatArgsForExpression($args)));
            } else {
                if ($fieldName !== $reflector->getName() || 'value' !== $currentValue) {
                    $fieldConfiguration['resolve'] = $this->formatExpression(sprintf('%s.%s', $currentValue, $reflector->getName()));
                }
            }
        }

        $argsBuilder = $this->getFirstMetadataMatching($metadatas, Metadata\ArgsBuilder::class);
        if ($argsBuilder) {
            $fieldConfiguration['argsBuilder'] = ['builder' => $argsBuilder->name, 'config' => $argsBuilder->config];
        } elseif ($fieldMetadata->argsBuilder) {
            if (is_string($fieldMetadata->argsBuilder)) {
                $fieldConfiguration['argsBuilder'] = ['builder' => $fieldMetadata->argsBuilder, 'config' => []];
            } elseif (is_array($fieldMetadata->argsBuilder)) {
                list($builder, $builderConfig) = $fieldMetadata->argsBuilder;
                $fieldConfiguration['argsBuilder'] = ['builder' => $builder, 'config' => $builderConfig];
            } else {
                throw new MetadataConfigurationException(sprintf('The attribute "argsBuilder" on metadata %s defined on "%s" must be a string or an array where first index is the builder name and the second is the config.', $this->formatMetadata($fieldMetadataName), $reflector->getName()));
            }
        }
        $fieldBuilder = $this->getFirstMetadataMatching($metadatas, Metadata\FieldBuilder::class);
        if ($fieldBuilder) {
            $fieldConfiguration['builder'] = $fieldBuilder->name;
            $fieldConfiguration['builderConfig'] = $fieldBuilder->config;
        } elseif ($fieldMetadata->fieldBuilder) {
            if (is_string($fieldMetadata->fieldBuilder)) {
                $fieldConfiguration['builder'] = $fieldMetadata->fieldBuilder;
                $fieldConfiguration['builderConfig'] = [];
            } elseif (is_array($fieldMetadata->fieldBuilder)) {
                list($builder, $builderConfig) = $fieldMetadata->fieldBuilder;
                $fieldConfiguration['builder'] = $builder;
                $fieldConfiguration['builderConfig'] = $builderConfig ?: [];
            } else {
                throw new MetadataConfigurationException(sprintf('The attribute "fieldBuilder" on metadata %s defined on "%s" must be a string or an array where first index is the builder name and the second is the config.', $this->formatMetadata($fieldMetadataName), $reflector->getName()));
            }
        } else {
            if (!isset($fieldMetadata->type)) {
                try {
                    $fieldConfiguration['type'] = $this->typeGuesser->guessType($reflectionClass, $reflector, self::VALID_OUTPUT_TYPES);
                } catch (TypeGuessingException $e) {
                    $message = sprintf('The attribute "type" on %s is missing on %s "%s" and cannot be auto-guessed from the following type guessers:'."\n%s\n", $this->formatMetadata($fieldMetadataName), $reflector instanceof ReflectionProperty ? 'property' : 'method', $reflector->getName(), $e->getMessage());

                    throw new MetadataConfigurationException($message, 0, $e);
                }
            }
        }

        if ($accessMetadata) {
            $fieldConfiguration['access'] = $this->formatExpression($accessMetadata->value);
        }

        if ($publicMetadata) {
            $fieldConfiguration['public'] = $this->formatExpression($publicMetadata->value);
        }

        if (isset($fieldMetadata->complexity)) {
            $fieldConfiguration['complexity'] = $this->formatExpression($fieldMetadata->complexity);
        }

        return [$fieldName => $fieldConfiguration];
    }

    /**
     * @phpstan-param class-string<Metadata\Query|Metadata\Mutation> $expectedMetadata
     *
     * Return fields config from Provider methods.
     * Loop through configured provider and extract fields targeting the targetType.
     *
     * @return array<string,array>
     */
    protected function getGraphQLFieldsFromProviders(ReflectionClass $reflectionClass, string $targetType): array
    {
        $fields = [];
        if (isset($this->providers[$targetType])) {
            foreach ($this->providers[$targetType] as $provider) {
                $expectedOperation = $this->getOperationType($targetType) ?? self::OPERATION_TYPE_QUERY;
                if ($expectedOperation !== $provider['operation']) {
                    $message = sprintf('Failed to add field on type "%s" from provider "%s" method "%s"', $targetType, $provider['name'], $provider['method']->getName());
                    $message .= "\n".sprintf('The provider provides a "%s" but the type expects a "%s"', $provider['operation'], $expectedOperation);
                    throw new MetadataConfigurationException($message);
                }
                $expectedMetadata = $provider['operation'] === self::OPERATION_TYPE_QUERY ? Metadata\Query::class : Metadata\Mutation::class;
                $providerFields = $this->getTypeFieldConfigurationFromReflector($reflectionClass, $provider['method'], $expectedMetadata, $provider['value']);
                foreach ($providerFields as $fieldName => $fieldConfiguration) {
                    if (!isset($fieldConfiguration['access']) && $provider['defaultAccess']) {
                        $fieldConfiguration['access'] = $provider['defaultAccess'];
                    }
                    if (!isset($fieldConfiguration['public']) && $provider['defaultPublic']) {
                        $fieldConfiguration['public'] = $provider['defaultPublic'];
                    }
                    $fields[sprintf('%s%s', $provider['prefix'], $fieldName)] = $fieldConfiguration;
                }
            }
        }

        return $fields;
    }

    /**
     * Format an array of args to a list of arguments in an expression.
     */
    protected function formatArgsForExpression(array $args): string
    {
        $mapping = [];
        foreach ($args as $name => $config) {
            $mapping[] = sprintf('%s: "%s"', $name, $config['type']);
        }

        return sprintf('arguments({%s}, args)', implode(', ', $mapping));
    }

    /**
     * Transform a method arguments from reflection to a list of GraphQL argument.
     */
    public function guessArgs(ReflectionClass $reflectionClass, ReflectionMethod $method): array
    {
        $arguments = [];
        foreach ($method->getParameters() as $index => $parameter) {
            try {
                $gqlType = $this->typeGuesser->guessType($reflectionClass, $parameter, self::VALID_INPUT_TYPES);
            } catch (TypeGuessingException $exception) {
                throw new MetadataConfigurationException(sprintf('Argument n°%s "$%s" on method "%s" cannot be auto-guessed from the following type guessers:'."\n%s\n", $index + 1, $parameter->getName(), $method->getName(), $exception->getMessage()));
            }

            $argumentConfig = [];
            if ($parameter->isDefaultValueAvailable()) {
                $argumentConfig['defaultValue'] = $parameter->getDefaultValue();
            }

            $argumentConfig['type'] = $gqlType;

            $arguments[$parameter->getName()] = $argumentConfig;
        }

        return $arguments;
    }
}
