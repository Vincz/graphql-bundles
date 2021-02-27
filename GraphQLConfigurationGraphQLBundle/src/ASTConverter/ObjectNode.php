<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter;

use GraphQL\Language\AST\Node;
use Overblog\GraphQLBundle\Configuration\InputConfiguration;
use Overblog\GraphQLBundle\Configuration\InterfaceConfiguration;
use Overblog\GraphQLBundle\Configuration\ObjectConfiguration;
use Overblog\GraphQLBundle\Configuration\TypeConfiguration;

class ObjectNode implements NodeInterface
{
    protected const TYPENAME = 'object';

    public static function toConfiguration(string $name, Node $node): TypeConfiguration
    {
        $fieldsType = Fields::TYPE_FIELDS;
        switch (static::TYPENAME) {
            case 'object':
                $configuration = ObjectConfiguration::get($name);
                break;
            case 'interface':
                $configuration = InterfaceConfiguration::get($name);
                break;
            case 'input-object':
                $configuration = InputConfiguration::get($name);
                $fieldsType = Fields::TYPE_INPUT_FIELDS;
                break;
        }

        $configuration->setDeprecation(Deprecated::get($node));
        $configuration->setDescription(Description::get($node));
        $configuration->addExtensions(Extensions::get($node));

        $configuration->addFields(Fields::get($node, $fieldsType));

        if (!empty($node->interfaces)) {
            $interfaces = [];
            foreach ($node->interfaces as $interface) {
                $interfaces[] = Type::astTypeNodeToString($interface);
            }
            if (count($interfaces) > 0) {
                $configuration->setInterfaces($interfaces);
            }
        }

        return $configuration;
    }
}
