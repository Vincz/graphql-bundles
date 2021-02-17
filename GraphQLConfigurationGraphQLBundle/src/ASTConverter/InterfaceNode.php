<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter;

class InterfaceNode extends ObjectNode
{
    protected const TYPENAME = 'interface';
}
