<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlBundle;

use Overblog\GraphQL\Bundle\ConfigurationYamlBundle\Processor\BuilderProcessor;
use Overblog\GraphQL\Bundle\ConfigurationYamlBundle\Processor\InheritanceProcessor;
use Overblog\GraphQL\Bundle\ConfigurationYamlBundle\Processor\NamedConfigProcessor;
use Overblog\GraphQL\Bundle\ConfigurationYamlBundle\Processor\ProcessorInterface;
use Overblog\GraphQL\Bundle\ConfigurationYamlBundle\Processor\RelayProcessor;

class Processor implements ProcessorInterface
{
    public const BEFORE_NORMALIZATION = 0;
    public const NORMALIZATION = 2;

    public const PROCESSORS = [
        self::BEFORE_NORMALIZATION => [
            RelayProcessor::class,          // By an extension relay
            // BuilderProcessor::class,        // By an extension
            // NamedConfigProcessor::class,    // Globally
            InheritanceProcessor::class,
        ],
        self::NORMALIZATION => [],
    ];

    public static function process(array $configs, int $type = self::NORMALIZATION): array
    {
        /** @var ProcessorInterface $processor */
        foreach (static::PROCESSORS[$type] as $processor) {
            $configs = $processor::process($configs);
        }

        return $configs;
    }
}
