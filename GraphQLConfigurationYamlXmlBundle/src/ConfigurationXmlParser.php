<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlXmlBundle;

use DOMElement;
use InvalidArgumentException;
use SplFileInfo;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Util\XmlUtils;
use function sprintf;

class ConfigurationXmlParser extends ConfigurationParser
{
    public function getSupportedExtensions(): array
    {
        return ['xml'];
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
        } catch (InvalidArgumentException $e) {
            throw new InvalidConfigurationException(sprintf('The file "%s" does not contain valid XML.', $file), $e->getCode(), $e);
        }

        return $typesConfig;
    }
}
