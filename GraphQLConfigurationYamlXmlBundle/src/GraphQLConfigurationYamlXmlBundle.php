<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlXmlBundle;

use Overblog\GraphQL\Bundle\ConfigurationYamlXmlBundle\DependencyInjection\OverblogGraphQLConfigurationYamlXmlExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GraphQLConfigurationYamlXmlBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new OverblogGraphQLConfigurationYamlXmlExtension();
    }
}
