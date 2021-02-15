<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationXmlBundle;

use Overblog\GraphQL\Bundle\ConfigurationXmlBundle\DependencyInjection\OverblogGraphQLConfigurationXmlExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GraphQLConfigurationXmlBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new OverblogGraphQLConfigurationXmlExtension();
    }
}
