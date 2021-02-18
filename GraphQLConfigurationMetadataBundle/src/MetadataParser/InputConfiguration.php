<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataParser;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataConfigurationException;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\TypeGuessingException;
use ReflectionClass;
use ReflectionProperty;

class InputConfiguration extends MetadataConfiguration
{
    const TYPE = self::GQL_INPUT;

    protected function getInputName(ReflectionClass $reflectionClass, Metadata\Metadata $inputMetadata): string
    {
        return $inputMetadata->name ?? $this->suffixName($reflectionClass->getShortName(), 'Input');
    }

    public function setClassesMap(ReflectionClass $reflectionClass, Metadata\Metadata $inputMetadata): void
    {
        $gqlName = $this->getInputName($reflectionClass, $inputMetadata);
        $this->classesTypesMap->addClassType($gqlName, $reflectionClass->getName(), self::TYPE);
    }

    /**
     * Create a GraphQL Input type configuration from metadatas on properties.
     *
     * @return array{type: 'relay-mutation-input'|'input-object', config: array}
     */
    public function getConfiguration(ReflectionClass $reflectionClass, Metadata\Metadata $inputMetadata): array
    {
        $gqlName = $this->getInputName($reflectionClass, $inputMetadata);
        $inputConfiguration = array_merge([
            'fields' => $this->getGraphQLInputFieldsFromMetadatas($reflectionClass, $this->getClassProperties($reflectionClass)),
        ], $this->getDescriptionConfiguration($this->getMetadatas($reflectionClass)));

        return [
            $gqlName => [
                'type' => $inputMetadata->isRelay ? 'relay-mutation-input' : self::CONFIGURATION_TYPES_MAP[self::TYPE],
                'config' => $inputConfiguration,
            ],
        ];
    }

    /**
     * Create GraphQL input fields configuration based on metadatas.
     *
     * @param ReflectionProperty[] $reflectors
     *
     * @throws AnnotationException
     *
     * @return array<string,array>
     */
    protected function getGraphQLInputFieldsFromMetadatas(ReflectionClass $reflectionClass, array $reflectors): array
    {
        $fields = [];

        foreach ($reflectors as $reflector) {
            $metadatas = $this->getMetadatas($reflector);

            /** @var Metadata\Field|null $fieldMetadata */
            $fieldMetadata = $this->getFirstMetadataMatching($metadatas, Metadata\Field::class);

            // No field metadata found
            if (null === $fieldMetadata) {
                continue;
            }

            // Ignore field with resolver when the type is an Input
            if (isset($fieldMetadata->resolve)) {
                continue;
            }

            $fieldName = $reflector->getName();
            if (isset($fieldMetadata->type)) {
                $fieldType = $fieldMetadata->type;
            } else {
                try {
                    $fieldType = $this->typeGuesser->guessType($reflectionClass, $reflector, self::VALID_INPUT_TYPES);
                } catch (TypeGuessingException $e) {
                    throw new MetadataConfigurationException(sprintf('The attribute "type" on %s is missing on property "%s" and cannot be auto-guessed from the following type guessers:'."\n%s\n", $this->formatMetadata(Metadata\Field::class), $reflector->getName(), $e->getMessage()));
                }
            }
            $fieldConfiguration = [];
            if ($fieldType) {
                // Resolve a PHP class from a GraphQL type
                $resolvedType = $this->classesTypesMap->getType($fieldType);
                // We found a type but it is not allowed
                if (null !== $resolvedType && !in_array($resolvedType['type'], self::VALID_INPUT_TYPES)) {
                    throw new MetadataConfigurationException(sprintf('The type "%s" on "%s" is a "%s" not valid on an Input %s. Only Input, Scalar and Enum are allowed.', $fieldType, $reflector->getName(), $resolvedType['type'], $this->formatMetadata('Field')));
                }

                $fieldConfiguration['type'] = $fieldType;
            }

            $fieldConfiguration = array_merge($this->getDescriptionConfiguration($metadatas, true), $fieldConfiguration);
            $fields[$fieldName] = $fieldConfiguration;
        }

        return $fields;
    }
}
