<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\TypeGuesser;

use Exception;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\ClassesTypesMap;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\Extension\DoctrineTypeGuesserExtension;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\TypeGuessingException;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DoctrineTypeGuesserExtensionTest extends WebTestCase
{
    // @phpstan-ignore-next-line
    protected $property;

    public function testGuessError(): void
    {
        $refClass = new ReflectionClass(__CLASS__);
        $doctrineGuesser = new DoctrineTypeGuesserExtension(new ClassesTypesMap());

        try {
            // @phpstan-ignore-next-line
            $doctrineGuesser->guessType($refClass, $refClass);
        } catch (Exception $e) {
            $this->assertInstanceOf(TypeGuessingException::class, $e);
            $this->assertStringContainsString('Doctrine type guesser only apply to properties.', $e->getMessage());
        }

        try {
            $doctrineGuesser->guessType($refClass, $refClass->getProperty('property'));
        } catch (Exception $e) {
            $this->assertInstanceOf(TypeGuessingException::class, $e);
            $this->assertStringContainsString('No Doctrine ORM annotation found.', $e->getMessage());
        }
    }
}
