<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlXmlBundle\Processor;

interface ProcessorInterface
{
    public static function process(array $configs): array;
}
