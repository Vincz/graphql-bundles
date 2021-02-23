<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter;

use GraphQL\Language\AST\Node;
use Overblog\GraphQLBundle\Configuration\InputConfiguration;
use Overblog\GraphQLBundle\Configuration\InterfaceConfiguration;
use Overblog\GraphQLBundle\Configuration\ObjectConfiguration;
use Overblog\GraphQLBundle\Configuration\TypeConfigurationInterface;

class ObjectNode implements NodeInterface
{
    protected const TYPENAME = 'object';

    public static function toConfiguration(Node $node): TypeConfigurationInterface
    {
        switch (static::TYPENAME) {
            case 'object':
                $configuration = new ObjectConfiguration('');
                break;
            case 'interface':
                $configuration = new InterfaceConfiguration('');
                break;
            case 'input-object':
                $configuration = new InputConfiguration('');
                break;
        }
        $configuration->setDeprecation(Deprecated::get($node));
        $configuration->setDescription(Description::get($node));
        $configuration->addExtensions(Extensions::get($node));

        $configuration->addFields(Fields::get($node));
        
        if (!empty($node->interfaces)) {
            $interfaces = [];
            foreach ($node->interfaces as $interface) {
                $interfaces[] = Type::get($interface);
            }
            if (count($interfaces) > 0) {
                $configuration->setInterfaces($interfaces);
            }
        }

        return $configuration;
    }
}
