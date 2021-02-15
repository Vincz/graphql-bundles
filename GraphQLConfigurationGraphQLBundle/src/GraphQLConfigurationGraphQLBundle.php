<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle;

use Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\DependencyInjection\OverblogGraphQLConfigurationGraphQLExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GraphQLConfigurationGraphQLBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new OverblogGraphQLConfigurationGraphQLExtension();
    }
}
