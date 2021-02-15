<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlBundle;

use Overblog\GraphQL\Bundle\ConfigurationYamlBundle\DependencyInjection\OverblogGraphQLConfigurationYamlExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GraphQLConfigurationYamlBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new OverblogGraphQLConfigurationYamlExtension();
    }
}
