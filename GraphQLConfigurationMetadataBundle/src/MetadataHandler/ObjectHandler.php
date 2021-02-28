<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataConfigurationException;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\TypeGuessingException;
use Overblog\GraphQLBundle\Configuration\ArgumentConfiguration;
use Overblog\GraphQLBundle\Configuration\Configuration;
use Overblog\GraphQLBundle\Configuration\ExtensionConfiguration;
use Overblog\GraphQLBundle\Configuration\FieldConfiguration;
use Overblog\GraphQLBundle\Configuration\ObjectConfiguration;
use Overblog\GraphQLBundle\Configuration\TypeConfiguration;
use Overblog\GraphQLBundle\Extension\Builder\BuilderExtension;
use Overblog\GraphQLBundle\Extension\Complexity\ComplexityExtension;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;
use function sprintf;

class ObjectHandler extends MetadataHandler
{
    protected const TYPE = TypeConfiguration::TYPE_OBJECT;

    protected const OPERATION_TYPE_QUERY = 'query';
    protected const OPERATION_TYPE_MUTATION = 'mutation';

    protected array $providers = [];

    protected function getTypeName(ReflectionClass $reflectionClass, Metadata\Metadata $typeMetadata): string
    {
        return $typeMetadata->name ?? $reflectionClass->getShortName();
    }

    public function setClassesMap(ReflectionClass $reflectionClass, Metadata\Metadata $metadata): void
    {
        if ($metadata instanceof Metadata\Provider) {
            $this->addProvider($reflectionClass, $metadata);
        } else {
            $gqlName = $this->getTypeName($reflectionClass, $metadata);
            $this->classesTypesMap->addClassType($gqlName, $reflectionClass->getName(), self::TYPE);
        }
    }

    public function addConfiguration(Configuration $configuration, ReflectionClass $reflectionClass, Metadata\Metadata $typeMetadata): ?TypeConfiguration
    {
        if (!$typeMetadata instanceof Metadata\Type) {
            return null;
        }

        $gqlName = $this->getTypeName($reflectionClass, $typeMetadata);
        $metadatas = $this->getMetadatas($reflectionClass);

        $objectConfiguration = ObjectConfiguration::get($gqlName)
            ->setDescription($this->getDescription($metadatas))
            ->setDeprecation($this->getDeprecation($metadatas))
            ->setOrigin($this->getOrigin($reflectionClass));

        $currentValue = null !== $this->getOperationType($gqlName) ? sprintf("service('%s')", $this->formatNamespaceForExpression($reflectionClass->getName())) : 'value';

        $fieldsFromProperties = $this->getGraphQLTypeFieldsFromAnnotations($reflectionClass, $this->getClassProperties($reflectionClass), $currentValue);
        $fieldsFromMethods = $this->getGraphQLTypeFieldsFromAnnotations($reflectionClass, $reflectionClass->getMethods(), $currentValue);
        $fieldsFromProviders = $this->getGraphQLFieldsFromProviders($reflectionClass, $gqlName);

        $fields = $fieldsFromProperties + $fieldsFromMethods + $fieldsFromProviders;

        foreach ($fields as $field) {
            $objectConfiguration->addField($field);
        }

        if (!empty($typeMetadata->interfaces)) {
            $interfaces = $typeMetadata->interfaces;
        } else {
            $interfaces = array_keys($this->classesTypesMap->searchClassesMapBy(function ($gqlType, $configuration) use ($reflectionClass) {
                ['class' => $interfaceClassName] = $configuration;

                $interfaceMetadata = new ReflectionClass($interfaceClassName);
                if ($interfaceMetadata->isInterface() && $reflectionClass->implementsInterface($interfaceMetadata->getName())) {
                    return true;
                }

                return $reflectionClass->isSubclassOf($interfaceClassName);
            }, TypeConfiguration::TYPE_INTERFACE));

            sort($interfaces);
        }

        if (count($interfaces) > 0) {
            $objectConfiguration->setInterfaces($interfaces);
        }

        if (isset($typeMetadata->isTypeOf)) {
            $objectConfiguration->setIsTypeOf($typeMetadata->isTypeOf);
        }

        if (isset($typeMetadata->resolveField)) {
            $objectConfiguration->setFieldsResolver($this->formatExpression($typeMetadata->resolveField));
        }

        $objectConfiguration->addExtensions($this->getExtensions($metadatas));

        $configuration->addType($objectConfiguration);

        return $objectConfiguration;
    }

    protected function addProvider(ReflectionClass $reflectionClass, Metadata\Provider $providerMetadata)
    {
        $metadatas = $this->getMetadatas($reflectionClass);
        $extensions = $this->getExtensions($metadatas);

        foreach ($reflectionClass->getMethods() as $method) {
            $metadatas = $this->getMetadatas($method);
            $metadata = $this->getFirstMetadataMatching($metadatas, [Metadata\Mutation::class, Metadata\Query::class]);
            if (null === $metadata) {
                continue;
            }

            $targets = $this->getProviderTargetTypes($metadata, $providerMetadata);
            if (null === $targets) {
                throw new MetadataConfigurationException('Unable to find provider targets');
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
                    'extensions' => $extensions,
                    'method' => $method,
                    'operation' => $metadata instanceof Metadata\Query ? self::OPERATION_TYPE_QUERY : self::OPERATION_TYPE_MUTATION,
                ];
            }
        }
    }

    /**
     * Resolve the method target types
     *
     * @param Metadata\Query|Metadata\Mutation $methodMetadata
     * @param Metadata\Provider                $providerMetadata
     *
     * @return string[]|null
     */
    protected function getProviderTargetTypes(Metadata\Metadata $methodMetadata, Metadata\Metadata $providerMetadata): ?array
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
    protected function getOperationType(string $type, bool $isDefaultSchema = false): ?string
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
     * Create GraphQL type fields configuration based on metadatas.
     *
     * @phpstan-param class-string<Metadata\Field> $fieldMetadataName
     *
     * @param ReflectionProperty[]|ReflectionMethod[] $reflectors
     *
     * @return FieldConfiguration[]
     *
     * @throws AnnotationException
     */
    protected function getGraphQLTypeFieldsFromAnnotations(ReflectionClass $reflectionClass, array $reflectors, string $currentValue = 'value'): array
    {
        return array_filter(array_map(fn (Reflector $reflector) => $this->getTypeFieldConfigurationFromReflector($reflectionClass, $reflector, Metadata\Field::class, $currentValue), $reflectors));
    }

    /**
     * @phpstan-param ReflectionMethod|ReflectionProperty $reflector
     * @phpstan-param class-string<Metadata\Field> $fieldMetadataName
     *
     * @throws AnnotationException
     *
     * @return array<string,array>
     */
    protected function getTypeFieldConfigurationFromReflector(ReflectionClass $reflectionClass, Reflector $reflector, string $fieldMetadataName, string $currentValue = 'value'): ?FieldConfiguration
    {
        /** @var ReflectionProperty|ReflectionMethod $reflector */
        $metadatas = $this->getMetadatas($reflector);
        $fieldMetadata = $this->getFirstMetadataMatching($metadatas, $fieldMetadataName);

        if (null === $fieldMetadata) {
            return null;
        }

        if ($reflector instanceof ReflectionMethod && !$reflector->isPublic()) {
            throw new MetadataConfigurationException(sprintf('The metadata %s can only be applied to public method. The method "%s" is not public.', $this->formatMetadata('Field'), $reflector->getName()));
        }

        if (isset($fieldMetadata->type)) {
            $type = $fieldMetadata->type;
        } else {
            try {
                $type = $this->typeGuesser->guessType($reflectionClass, $reflector, TypeConfiguration::VALID_OUTPUT_TYPES);
            } catch (TypeGuessingException $e) {
                $message = sprintf('The attribute "type" on %s is missing on %s "%s" and cannot be auto-guessed from the following type guessers:'."\n%s\n", $this->formatMetadata($fieldMetadataName), $reflector instanceof ReflectionProperty ? 'property' : 'method', $reflector->getName(), $e->getMessage());

                throw new MetadataConfigurationException($message, 0, $e);
            }
        }
        $fieldName = $fieldMetadata->name ?? $reflector->getName();
        $fieldConfiguration = FieldConfiguration::get($fieldName, $type)
            ->setDescription($this->getDescription($metadatas))
            ->setDeprecation($this->getDeprecation($metadatas))
            ->addExtensions($this->getExtensions($metadatas))
            ->setOrigin($this->getOrigin($reflector));

        /** @var Metadata\Arg[] $argAnnotations */
        $argAnnotations = array_merge($this->getMetadataMatching($metadatas, Metadata\Arg::class), $fieldMetadata->args);
        $arguments = [];
        foreach ($argAnnotations as $arg) {
            $argConfiguration = ArgumentConfiguration::get($arg->name, $arg->type)
                ->setDescription($arg->description);

            if (isset($arg->default)) {
                $argConfiguration->setDefaultValue($arg->default);
            }

            $arguments[] = $argConfiguration;
        }

        if (empty($argAnnotations) && $reflector instanceof ReflectionMethod) {
            $arguments = $this->guessArgs($reflectionClass, $reflector);
        }

        foreach ($arguments as $argumentConfiguration) {
            $fieldConfiguration->addArgument($argumentConfiguration);
        }

        $resolver = null;
        if (isset($fieldMetadata->resolve)) {
            $resolver = $this->formatExpression($fieldMetadata->resolve);
        } else {
            if ($reflector instanceof ReflectionMethod) {
                $resolver = $this->formatExpression(sprintf('call(%s.%s, %s)', $currentValue, $reflector->getName(), $this->formatArgsForExpression($arguments)));
            } else {
                if ($fieldName !== $reflector->getName() || 'value' !== $currentValue) {
                    $resolver = $this->formatExpression(sprintf('%s.%s', $currentValue, $reflector->getName()));
                }
            }
        }

        if (null !== $resolver) {
            $fieldConfiguration->setResolver($resolver);
        }

        /**  handle legacy args builders */
        if ($fieldMetadata->argsBuilder) {
            if (is_array($fieldMetadata->argsBuilder) || is_string($fieldMetadata->argsBuilder)) {
                $builderConfiguration = [
                    'name' => is_string($fieldMetadata->argsBuilder) ? $fieldMetadata->argsBuilder : $fieldMetadata->argsBuilder[0],
                    'configuration' => is_string($fieldMetadata->argsBuilder) ? [] : $fieldMetadata->argsBuilder[1],
                ];
                $fieldConfiguration->addExtension(ExtensionConfiguration::get(BuilderExtension::ALIAS, $builderConfiguration));
            } else {
                throw new MetadataConfigurationException(sprintf('The attribute "argsBuilder" on metadata %s defined on "%s" must be a string or an array where first index is the builder name and the second is the config.', $this->formatMetadata($fieldMetadataName), $reflector->getName()));
            }
        }
        /** handle legacy field builders */
        if ($fieldMetadata->fieldBuilder) {
            if (is_array($fieldMetadata->fieldBuilder) || is_string($fieldMetadata->fieldBuilder)) {
                $builderConfiguration = [
                    'name' => is_string($fieldMetadata->fieldBuilder) ? $fieldMetadata->fieldBuilder : $fieldMetadata->fieldBuilder[0],
                    'configuration' => is_string($fieldMetadata->fieldBuilder) ? [] : $fieldMetadata->fieldBuilder[1],
                ];

                $fieldConfiguration->addExtension(ExtensionConfiguration::get(BuilderExtension::ALIAS, $builderConfiguration));
            } else {
                throw new MetadataConfigurationException(sprintf('The attribute "fieldBuilder" on metadata %s defined on "%s" must be a string or an array where first index is the builder name and the second is the config.', $this->formatMetadata($fieldMetadataName), $reflector->getName()));
            }
        }

        if (isset($fieldMetadata->complexity)) {
            $fieldConfiguration->addExtension(ExtensionConfiguration::get(ComplexityExtension::ALIAS, $this->formatExpression($fieldMetadata->complexity)));
        }

        return $fieldConfiguration;
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
                $expectedMetadata = self::OPERATION_TYPE_QUERY === $provider['operation'] ? Metadata\Query::class : Metadata\Mutation::class;
                $providerField = $this->getTypeFieldConfigurationFromReflector($reflectionClass, $provider['method'], $expectedMetadata, $provider['value']);
                if (null !== $providerField) {
                    $providerField->setName(sprintf('%s%s', $provider['prefix'], $providerField->getName()));
                    $fields[] = $providerField;

                    $providerExtensions = [];
                    foreach ($provider['extensions'] as $providerExtension) {
                        if (!$providerField->hasExtension($providerExtension->getAlias())) {
                            $providerExtensions[] = $providerExtension;
                        }
                    }

                    $providerField->addExtensions($providerExtensions);
                }
            }
        }

        return $fields;
    }

    /**
     * Format an array of args to a list of arguments in an expression.
     */
    protected function formatArgsForExpression(array $arguments): string
    {
        $mapping = [];
        foreach ($arguments as $argument) {
            $mapping[] = sprintf('%s: "%s"', $argument->getName(), $argument->getType());
        }

        return sprintf('arguments({%s}, args)', implode(', ', $mapping));
    }

    /**
     * Transform a method arguments from reflection to a list of GraphQL argument.
     *
     * @return ArgumentConfiguration[]
     */
    public function guessArgs(ReflectionClass $reflectionClass, ReflectionMethod $method): array
    {
        $arguments = [];
        foreach ($method->getParameters() as $index => $parameter) {
            try {
                $gqlType = $this->typeGuesser->guessType($reflectionClass, $parameter, TypeConfiguration::VALID_INPUT_TYPES);
            } catch (TypeGuessingException $exception) {
                throw new MetadataConfigurationException(sprintf('Argument n°%s "$%s" on method "%s" cannot be auto-guessed from the following type guessers:'."\n%s\n", $index + 1, $parameter->getName(), $method->getName(), $exception->getMessage()));
            }

            $argumentConfiguration = ArgumentConfiguration::get($parameter->getName(), $gqlType)
                ->setOrigin($this->getOrigin($parameter));

            if ($parameter->isDefaultValueAvailable()) {
                $argumentConfiguration->setDefaultValue($parameter->getDefaultValue());
            }

            $arguments[] = $argumentConfiguration;
        }

        return $arguments;
    }
}
