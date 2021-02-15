<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\DependencyInjection\OverblogGraphQLConfigurationMetadataExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GraphQLConfigurationMetadataBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new OverblogGraphQLConfigurationMetadataExtension();
    }
}
