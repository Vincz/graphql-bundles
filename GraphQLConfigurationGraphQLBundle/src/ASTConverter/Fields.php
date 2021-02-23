<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter;

use GraphQL\Language\AST\Node;
use GraphQL\Utils\AST;
use Overblog\GraphQLBundle\Configuration\ArgumentConfiguration;
use Overblog\GraphQLBundle\Configuration\FieldConfiguration;

class Fields
{
    public static function get(Node $node, string $property = 'fields'): array
    {
        $list = [];
        if (!empty($node->$property)) {
            foreach ($node->$property as $definition) {
                $configurationClass = $property === 'fields' ? FieldConfiguration::class : ArgumentConfiguration::class;
                $configuration = new $configurationClass($definition->name->value, Type::get($definition));
                $configuration->setDeprecation(Deprecated::get($definition));
                $configuration->setDescription(Description::get($definition));
                $configuration->addExtensions(Extensions::get($definition));

                if (!empty($definition->arguments)) {
                    foreach (self::get($definition, 'arguments') as $argumentConfiguration) {
                        $configuration->addArgument($argumentConfiguration);
                    }
                }

                if ($property === 'arguments' && !empty($definition->defaultValue)) {
                    $configuration->setDefaultValue(AST::valueFromASTUntyped($definition->defaultValue));
                }

                $list[] = $configuration;
            }
        }

        return $list;
    }
}
