<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests;

use ArrayIterator;
use Exception;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\ClassesTypesMap;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\ConfigurationMetadataParser;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata\EnumConfiguration;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata\InputConfiguration;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata\InterfaceConfiguration;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata\ScalarConfiguration;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata\TypeConfiguration;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata\UnionConfiguration;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata\RelayEdgeConfiguration;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Annotation as Metadata;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata\RelayConnectionConfiguration;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataConfigurationException;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Reader\MetadataReaderInterface;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\Extension\DocBlockTypeGuesserExtension;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\Extension\DoctrineTypeGuesserExtension;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\Extension\TypeHintTypeGuesserExtension;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\TypeGuesser;
use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Symfony\Component\Finder\Finder;

use function sprintf;

abstract class ConfigurationMetadataParserTest extends WebTestCase
{
    protected array $config = [];
    protected TypeGuesser $typeGuesser;
    protected ConfigurationMetadataParser $configurationParser;
    protected ClassesTypesMap $classesTypesMap;
    protected array $excludeDirectories = ['Invalid', 'Deprecated'];

    abstract protected function getMetadataReader(): MetadataReaderInterface;

    protected static function cleanConfig(array $config): array
    {
        foreach ($config as $key => &$value) {
            if (is_array($value)) {
                $value = self::cleanConfig($value);
            }
        }

        return array_filter($config, function ($item) {
            return !is_array($item) || !empty($item);
        });
    }

    protected array $schemas = [
        'default' => ['query' => 'RootQuery', 'mutation' => 'RootMutation'],
        'second' => ['query' => 'RootQuery2', 'mutation' => 'RootMutation2'],
    ];

    protected array $doctrineMapping = [
        'text[]' => '[String]',
    ];
    

    public function formatMetadata(string $metadata): string
    {
        return $this->getMetadataReader()->formatMetadata($metadata);
    }

    public function setUp(): void
    {
        parent::setup();
        $this->config = self::cleanConfig($this->getConfiguration());
    }

    protected function getConfiguration(array $includeDirectories = [])
    {
        $reader = $this->getMetadataReader();
        $this->classesTypesMap = new ClassesTypesMap();
        $this->typeGuesser = new TypeGuesser(new ArrayIterator([
            new DocBlockTypeGuesserExtension($this->classesTypesMap),
            new TypeHintTypeGuesserExtension($this->classesTypesMap),
            new DoctrineTypeGuesserExtension($this->classesTypesMap, $this->doctrineMapping),
        ]));

        $resolverArgs = [
            $this->classesTypesMap,
            $reader,
            $this->typeGuesser,
            $this->schemas
        ];
        $typeResolver = new TypeConfiguration(...$resolverArgs);
        $resolvers = new ArrayIterator([
            Metadata\Provider::class => $typeResolver,
            Metadata\Relay\Edge::class => new RelayEdgeConfiguration(...$resolverArgs),
            Metadata\Relay\Connection::class => new RelayConnectionConfiguration(...$resolverArgs),
            Metadata\Type::class => $typeResolver,
            Metadata\Input::class => new InputConfiguration(...$resolverArgs),
            Metadata\Scalar::class => new ScalarConfiguration(...$resolverArgs),
            Metadata\Enum::class => new EnumConfiguration(...$resolverArgs),
            Metadata\Union::class => new UnionConfiguration(...$resolverArgs),
            Metadata\TypeInterface::class => new InterfaceConfiguration(...$resolverArgs),
        ]);

        // Exclude Deprecated & Invalid directories from the test directories
        $finder = Finder::create()
            ->in(__DIR__.'/fixtures')
            ->directories();
        foreach ($this->excludeDirectories as $exclude) {
            $finder = $finder->exclude($exclude);
        }
        $directories = array_values(array_map(fn (SplFileInfo $dir) => $dir->getPathname(), iterator_to_array($finder->getIterator())));
        $directories = [...$directories, ...$includeDirectories];
        
        $generator = new ConfigurationMetadataParser($reader, $this->classesTypesMap, $resolvers, $directories);
        
        return $generator->getConfiguration();
    }

    protected function expect(string $name, string $type, array $config = []): void
    {
        $expected = [
            'type' => $type,
            'config' => $config,
        ];

        $this->assertArrayHasKey($name, $this->config, sprintf("The GraphQL type '%s' doesn't exist", $name));
        $this->assertEquals($expected, $this->config[$name]);
    }

    public function testTypes(): void
    {
        // Test an interface
        $this->expect('Character', 'interface', [
            'description' => 'The character interface',
            'resolveType' => "@=resolver('character_type', [value])",
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the character'],
                'friends' => ['type' => '[Character]', 'description' => 'The friends of the character', 'resolve' => "@=resolver('App\\MyResolver::getFriends')"],
            ],
        ]);

        // Test a type extending an interface
        $this->expect('Hero', 'object', [
            'description' => 'The Hero type',
            'interfaces' => ['Character'],
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the character'],
                'friends' => ['type' => '[Character]', 'description' => 'The friends of the character', 'resolve' => "@=resolver('App\\MyResolver::getFriends')"],
                'race' => ['type' => 'Race'],
            ],
        ]);

        $this->expect('Droid', 'object', [
            'description' => 'The Droid type',
            'interfaces' => ['Character'],
            'isTypeOf' => "@=isTypeOf('App\Entity\Droid')",
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the character'],
                'friends' => ['type' => '[Character]', 'description' => 'The friends of the character', 'resolve' => "@=resolver('App\\MyResolver::getFriends')"],
                'memory' => ['type' => 'Int!'],
                'planet_allowedPlanets' => [
                    'type' => '[Planet]',
                    'resolve' => '@=call(service(\'Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository\').getAllowedPlanetsForDroids, arguments({}, args))',
                    'access' => '@=override_access',
                    'public' => '@=default_public',
                ],
                'planet_armorResistance' => [
                    'type' => 'Int!',
                    'resolve' => '@=call(service(\'Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository\').getArmorResistance, arguments({}, args))',
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
            ],
        ]);

        // Test a type with public/access on fields, methods as field
        $this->expect('Sith', 'object', [
            'description' => 'The Sith type',
            'interfaces' => ['Character'],
            'resolveField' => '@=value',
            'fieldsDefaultPublic' => '@=isAuthenticated()',
            'fieldsDefaultAccess' => '@=isAuthenticated()',
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the character'],
                'friends' => ['type' => '[Character]', 'description' => 'The friends of the character', 'resolve' => "@=resolver('App\\MyResolver::getFriends')"],
                'realName' => ['type' => 'String!', 'access' => "@=hasRole('SITH_LORD')"],
                'location' => ['type' => 'String!', 'public' => "@=hasRole('SITH_LORD')"],
                'currentMaster' => ['type' => 'Sith', 'resolve' => "@=service('master_resolver').getMaster(value)"],
                'victims' => [
                    'type' => '[Character]',
                    'args' => ['jediOnly' => ['type' => 'Boolean', 'description' => 'Only Jedi victims', 'defaultValue' => false]],
                    'resolve' => '@=call(value.getVictims, arguments({jediOnly: "Boolean"}, args))',
                ],
            ],
        ]);

        // Test a type with a field builder
        $this->expect('Planet', 'object', [
            'description' => 'The Planet type',
            'fields' => [
                'name' => ['type' => 'String!'],
                'location' => ['type' => 'GalaxyCoordinates'],
                'population' => ['type' => 'Int!'],
                'notes' => [
                    'builder' => 'NoteFieldBuilder',
                    'builderConfig' => ['option1' => 'value1'],
                ],
                'closestPlanet' => [
                    'type' => 'Planet',
                    'argsBuilder' => [
                        'builder' => 'PlanetFilterArgBuilder',
                        'config' => ['option2' => 'value2'],
                    ],
                    'resolve' => "@=resolver('closest_planet', [args['filter']])",
                ],
                'notesDeprecated' => [
                    'builder' => 'NoteFieldBuilder',
                    'builderConfig' => ['option1' => 'value1'],
                ],
                'closestPlanetDeprecated' => [
                    'type' => 'Planet',
                    'argsBuilder' => [
                        'builder' => 'PlanetFilterArgBuilder',
                        'config' => ['option2' => 'value2'],
                    ],
                    'resolve' => "@=resolver('closest_planet', [args['filter']])",
                ],
            ],
        ]);

        // Test a type with a fields builder
        $this->expect('Crystal', 'object', [
            'fields' => [
                'color' => ['type' => 'String!'],
            ],
            'builders' => [['builder' => 'MyFieldsBuilder', 'builderConfig' => ['param1' => 'val1']]],
        ]);

        // Test a type extending another type
        $this->expect('Cat', 'object', [
            'description' => 'The Cat type',
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the animal'],
                'lives' => ['type' => 'Int!'],
                'toys' => ['type' => '[String!]!'],
            ],
        ]);
    }

    public function testInput(): void
    {
        $this->expect('PlanetInput', 'input-object', [
            'description' => 'Planet Input type description',
            'fields' => [
                'name' => ['type' => 'String!'],
                'population' => ['type' => 'Int!'],
                'description' => ['type' => 'String!'],
                'diameter' => ['type' => 'Int'],
                'variable' => ['type' => 'Int!'],
                'tags' => ['type' => '[String]!'],
            ],
        ]);
    }

    public function testInterfaces(): void
    {
        $this->expect('WithArmor', 'interface', [
            'description' => 'The armored interface',
            'resolveType' => '@=resolver(\'character_type\', [value])',
        ]);
    }

    public function testEnum(): void
    {
        $this->expect('Race', 'enum', [
            'description' => 'The list of races!',
            'values' => [
                'HUMAIN' => ['value' => 1],
                'CHISS' => ['value' => '2', 'description' => 'The Chiss race'],
                'ZABRAK' => ['value' => '3', 'deprecationReason' => 'The Zabraks have been wiped out'],
                'TWILEK' => ['value' => '4'],
            ],
        ]);
    }

    public function testUnion(): void
    {
        $this->expect('ResultSearch', 'union', [
            'description' => 'A search result',
            'types' => ['Hero', 'Droid', 'Sith'],
            'resolveType' => '@=value.getType()',
        ]);

        $this->expect('SearchResult2', 'union', [
            'types' => ['Hero', 'Droid', 'Sith'],
            'resolveType' => "@=call('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Union\\\\SearchResult2::resolveType', [service('overblog_graphql.type_resolver'), value], true)",
        ]);
    }

    public function testUnionAutoguessed(): void
    {
        $this->expect('Killable', 'union', [
            'types' => ['Hero', 'Mandalorian',  'Sith'],
            'resolveType' => '@=value.getType()',
        ]);
    }

    public function testInterfaceAutoguessed(): void
    {
        $this->expect('Mandalorian', 'object', [
            'interfaces' => ['Character', 'WithArmor'],
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the character'],
                'friends' => ['type' => '[Character]', 'description' => 'The friends of the character', 'resolve' => "@=resolver('App\\MyResolver::getFriends')"],
                'planet_armorResistance' => [
                    'type' => 'Int!',
                    'resolve' => '@=call(service(\'Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository\').getArmorResistance, arguments({}, args))',
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
            ],
        ]);
    }

    public function testScalar(): void
    {
        $this->expect('GalaxyCoordinates', 'custom-scalar', [
            'serialize' => ['Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Scalar\GalaxyCoordinates', 'serialize'],
            'parseValue' => ['Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Scalar\GalaxyCoordinates', 'parseValue'],
            'parseLiteral' => ['Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Scalar\GalaxyCoordinates', 'parseLiteral'],
            'description' => 'The galaxy coordinates scalar',
        ]);

        $this->expect('MyScalar', 'custom-scalar', [
            'scalarType' => '@=newObject(\'App\Type\EmailType\')'
        ]);

        $this->expect('MyScalar2', 'custom-scalar', [
            'scalarType' => '@=newObject(\'App\Type\EmailType\')'
        ]);
    }

    public function testProviders(): void
    {
        $this->expect('RootQuery', 'object', [
            'fields' => [
                'planet_searchPlanet' => [
                    'type' => '[Planet]',
                    'args' => ['keyword' => ['type' => 'String!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').searchPlanet, arguments({keyword: \"String!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
                'planet_isPlanetDestroyed' => [
                    'type' => 'Boolean!',
                    'args' => ['planetId' => ['type' => 'Int!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').isPlanetDestroyed, arguments({planetId: \"Int!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
                'countSecretWeapons' => [
                    'type' => 'Int!',
                    'resolve' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\WeaponRepository').countSecretWeapons, arguments({}, args))",
                ],
                'planet_searchStar' => [
                    'type' => '[Planet]',
                    'args' => ['distance' => ['type' => 'Int!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').searchStar, arguments({distance: \"Int!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
            ],
        ]);

        $this->expect('RootMutation', 'object', [
            'fields' => [
                'planet_createPlanet' => [
                    'type' => 'Planet',
                    'args' => ['planetInput' => ['type' => 'PlanetInput!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').createPlanet, arguments({planetInput: \"PlanetInput!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=override_public',
                ],
                'planet_destroyPlanet' => [
                    'type' => 'Boolean!',
                    'args' => ['planetId' => ['type' => 'Int!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').destroyPlanet, arguments({planetId: \"Int!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
            ],
        ]);
    }

    public function testProvidersMultischema(): void
    {
        $this->expect('RootQuery2', 'object', [
            'fields' => [
                'planet_getPlanetSchema2' => [
                    'type' => 'Planet',
                    'resolve' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').getPlanetSchema2, arguments({}, args))",
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
                'planet_isPlanetDestroyed' => [
                    'type' => 'Boolean!',
                    'args' => ['planetId' => ['type' => 'Int!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').isPlanetDestroyed, arguments({planetId: \"Int!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
                'hasSecretWeapons' => [
                    'type' => 'Boolean!',
                    'resolve' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\WeaponRepository').hasSecretWeapons, arguments({}, args))",
                ],
            ],
        ]);

        $this->expect('RootMutation2', 'object', [
            'fields' => [
                'planet_createPlanetSchema2' => [
                    'type' => 'Planet',
                    'args' => ['planetInput' => ['type' => 'PlanetInput!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').createPlanetSchema2, arguments({planetInput: \"PlanetInput!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=override_public',
                ],
                'planet_destroyPlanet' => [
                    'type' => 'Boolean!',
                    'args' => ['planetId' => ['type' => 'Int!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').destroyPlanet, arguments({planetId: \"Int!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
                'createLightsaber' => [
                    'type' => 'Boolean!',
                    'resolve' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\WeaponRepository').createLightsaber, arguments({}, args))",
                ],
            ],
        ]);
    }

    public function testDoctrineGuessing(): void
    {
        $this->expect('Lightsaber', 'object', [
            'fields' => [
                'color' => ['type' => 'String!'],
                'size' => ['type' => 'Int'],
                'holders' => ['type' => '[Hero]!'],
                'creator' => ['type' => 'Hero!'],
                'crystal' => ['type' => 'Crystal!'],
                'battles' => ['type' => '[Battle]!'],
                'currentHolder' => ['type' => 'Hero'],
                'tags' => ['type' => '[String]!', 'deprecationReason' => 'No more tags on lightsabers'],
                'text' => ['type' => 'String!'],
                'string' => ['type' => 'String!'],
                'float' => ['type' => 'Float!'],
                'decimal' => ['type' => 'Float!'],
                'bool' => ['type' => 'Boolean!'],
                'boolean' => ['type' => 'Boolean!'],
            ],
        ]);
    }

    public function testArgsAndReturnGuessing(): void
    {
        $this->expect('Battle', 'object', [
            'fields' => [
                'planet' => ['type' => 'Planet', 'complexity' => '@=100 + childrenComplexity'],
                'casualties' => [
                    'type' => 'Int',
                    'args' => [
                        'areaId' => ['type' => 'Int!'],
                        'raceId' => ['type' => 'String!'],
                        'dayStart' => ['type' => 'Int', 'defaultValue' => null],
                        'dayEnd' => ['type' => 'Int', 'defaultValue' => null],
                        'nameStartingWith' => ['type' => 'String', 'defaultValue' => ''],
                        'planet' => ['type' => 'PlanetInput', 'defaultValue' => null],
                        'away' => ['type' => 'Boolean', 'defaultValue' => false],
                        'maxDistance' => ['type' => 'Float', 'defaultValue' => null],
                    ],
                    'resolve' => '@=call(value.getCasualties, arguments({areaId: "Int!", raceId: "String!", dayStart: "Int", dayEnd: "Int", nameStartingWith: "String", planet: "PlanetInput", away: "Boolean", maxDistance: "Float"}, args))',
                    'complexity' => '@=childrenComplexity * 5',
                ],
            ],
        ]);
    }

    public function testRelayConnectionAuto(): void
    {
        $this->expect('EnemiesConnection', 'object', [
            'builders' => [
                ['builder' => 'relay-connection', 'builderConfig' => ['edgeType' => 'EnemiesConnectionEdge']],
            ],
        ]);

        $this->expect('EnemiesConnectionEdge', 'object', [
            'builders' => [
                ['builder' => 'relay-edge', 'builderConfig' => ['nodeType' => 'Character']],
            ],
        ]);
    }

    public function testRelayConnectionEdge(): void
    {
        $this->expect('FriendsConnection', 'object', [
            'builders' => [
                ['builder' => 'relay-connection', 'builderConfig' => ['edgeType' => 'FriendsConnectionEdge']],
            ],
        ]);

        $this->expect('FriendsConnectionEdge', 'object', [
            'builders' => [
                ['builder' => 'relay-edge', 'builderConfig' => ['nodeType' => 'Character']],
            ],
        ]);
    }

    
    public function testInvalidParamGuessing(): void
    {
        try {
            $file = __DIR__.'/fixtures/Invalid/argumentGuessing';
            $this->getConfiguration([$file]);
            $this->fail('Missing type hint for auto-guessed argument should have raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/Argument n°1 "\$test"/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidReturnGuessing(): void
    {
        try {
            $file = __DIR__.'/fixtures/Invalid/returnTypeGuessing';
            $this->getConfiguration([$file]);
            $this->fail('Missing type hint for auto-guessed return type should have raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/is missing on method "guessFail" and cannot be auto-guessed from the following type guessers/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidDoctrineRelationGuessing(): void
    {
        try {
            $file = __DIR__.'/fixtures/Invalid/doctrineRelationGuessing';
            $this->getConfiguration([$file]);
            $this->fail('Auto-guessing field type from doctrine relation on a non graphql entity should failed with an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/Unable to auto-guess GraphQL type from Doctrine target class/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidDoctrineTypeGuessing(): void
    {
        try {
            $file = __DIR__.'/fixtures/Invalid/doctrineTypeGuessing';
            $this->getConfiguration([$file]);
            $this->fail('Auto-guessing field type from doctrine relation on a non graphql entity should failed with an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/Unable to auto-guess GraphQL type from Doctrine type "invalidType"/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidUnion(): void
    {
        try {
            $file = __DIR__.'/fixtures/Invalid/union';
            $this->getConfiguration([$file]);
            $this->fail('Union with missing resolve type shoud have raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/The metadata '.preg_quote($this->formatMetadata('Union')).' has no "resolveType"/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidAccess(): void
    {
        try {
            $file = __DIR__.'/fixtures/Invalid/access';
            $this->getConfiguration([$file]);
            $this->fail('@Access annotation without a @Field annotation should raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/The metadatas '.preg_quote($this->formatMetadata('Access')).' and\/or '.preg_quote($this->formatMetadata('Visible')).' defined on "field"/', $e->getPrevious()->getMessage());
        }
    }

    public function testFieldOnPrivateProperty(): void
    {
        try {
            $file = __DIR__.'/fixtures/Invalid/privateMethod';
            $this->getConfiguration([$file]);
            $this->fail($this->formatMetadata('Access').' annotation without a '.$this->formatMetadata('Field').' annotation should raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/The metadata '.preg_quote($this->formatMetadata('Field')).' can only be applied to public method/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidProviderQueryOnMutation(): void
    {
        try {
            $file = __DIR__.'/fixtures/Invalid/provider';
            $this->getConfiguration([$file]);
            $this->fail('Using @Query or #Query targeting mutation type should raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/The provider provides a "query" but the type expects a "mutation"/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidProviderMutationOnQuery(): void
    {
        try {
            $file = __DIR__.'/fixtures/Invalid/provider2';
            $this->getConfiguration([$file]);
            $this->fail('Using @Mutation or #Mutation targeting regular type should raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/The provider provides a "mutation" but the type expects a "query"/', $e->getPrevious()->getMessage());
        }
    }
}