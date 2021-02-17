<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationXmlBundle;

use DOMElement;
use Overblog\GraphQLBundle\Configuration\ConfigurationFilesParser;
use SplFileInfo;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use function sprintf;

class ConfigurationXmlParser extends ConfigurationFilesParser
{
    public function getSupportedExtensions(): array
    {
        return ['xml'];
    }

    public function getDirectories(): array
    {
        $directories = [];
        if ($this->rootDirectory) {
            $directories[] = sprintf('%s/config/graphql', $this->rootDirectory);
        }

        foreach ($this->bundlesDirectories as $bundleDirectory) {
            $directories[] = sprintf('%s/Resources/config/graphql', $bundleDirectory);
        }

        return array_unique([...$directories, ...$this->directories]);
    }

    protected function parseFile(SplFileInfo $file): array
    {
        $typesConfig = [];
        try {
            $xml = XmlUtils::loadFile($file->getRealPath());
            foreach ($xml->documentElement->childNodes as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }
                $values = XmlUtils::convertDomElementToArray($node);
                if (is_array($values)) {
                    $typesConfig = array_merge($typesConfig, $values);
                }
            }
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
        }

        return $typesConfig;
    }
}
