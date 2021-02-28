<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests;

use ArrayIterator;
use Exception;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\ClassesTypesMap;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\ConfigurationMetadataParser;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataConfigurationException;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler\EnumHandler;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler\InputHandler;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler\InterfaceHandler;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler\ObjectHandler;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler\RelayConnectionHandler;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler\RelayEdgeHandler;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler\ScalarHandler;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\MetadataHandler\UnionHandler;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Reader\MetadataReaderInterface;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\Extension\DocBlockTypeGuesserExtension;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\Extension\DoctrineTypeGuesserExtension;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\Extension\TypeHintTypeGuesserExtension;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\TypeGuesser;
use Overblog\GraphQLBundle\Configuration\Configuration;
use Overblog\GraphQLBundle\Configuration\EnumConfiguration;
use Overblog\GraphQLBundle\Configuration\InputConfiguration;
use Overblog\GraphQLBundle\Configuration\InterfaceConfiguration;
use Overblog\GraphQLBundle\Configuration\ObjectConfiguration;
use Overblog\GraphQLBundle\Configuration\ScalarConfiguration;
use Overblog\GraphQLBundle\Configuration\UnionConfiguration;
use Overblog\GraphQLBundle\Extension\Builder\BuilderExtension;
use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Finder\Finder;
use function sprintf;

abstract class ConfigurationMetadataTest extends WebTestCase
{
    protected Configuration $configuration;
    protected TypeGuesser $typeGuesser;
    protected ConfigurationMetadataParser $configurationParser;
    protected ClassesTypesMap $classesTypesMap;
    protected array $excludeDirectories = ['Invalid', 'Deprecated'];

    abstract protected function getMetadataReader(): MetadataReaderInterface;

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
        $this->configuration = unserialize(serialize($this->getConfiguration()));
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
            $this->schemas,
        ];
        $objectHandler = new ObjectHandler(...$resolverArgs);
        $resolvers = new ArrayIterator([
            Metadata\Provider::class => $objectHandler,
            Metadata\Relay\Edge::class => new RelayEdgeHandler(...$resolverArgs),
            Metadata\Relay\Connection::class => new RelayConnectionHandler(...$resolverArgs),
            Metadata\Type::class => $objectHandler,
            Metadata\Input::class => new InputHandler(...$resolverArgs),
            Metadata\Scalar::class => new ScalarHandler(...$resolverArgs),
            Metadata\Enum::class => new EnumHandler(...$resolverArgs),
            Metadata\Union::class => new UnionHandler(...$resolverArgs),
            Metadata\TypeInterface::class => new InterfaceHandler(...$resolverArgs),
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

    protected function getType(string $name, string $configurationClass = null)
    {
        $type = $this->configuration->getType($name);
        if (!$type) {
            $this->fail(sprintf('Unable to retrieve type "%s" from configuration', $name));
        }
        $this->assertNotNull($type);
        if ($configurationClass) {
            $this->assertInstanceOf($configurationClass, $type);
        }

        return $type;
    }

    public function testTypeInterface(): void
    {
        $interface = $this->getType('Character', InterfaceConfiguration::class);
        $this->assertEquals([
            'name' => 'Character',
            'description' => 'The character interface',
            'typeResolver' => "@=resolver('character_type', [value])",
            'fields' => [
                [
                    'name' => 'name',
                    'type' => 'String!',
                    'description' => 'The name of the character',
                ],
                [
                    'name' => 'friends',
                    'type' => '[Character]',
                    'description' => 'The friends of the character',
                    'resolver' => "@=resolver('App\\MyResolver::getFriends')",
                ],
            ],
        ], $interface->toArray());
    }

    public function testTypeObjectWithInterface(): void
    {
        $object = $this->getType('Hero', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'Hero',
            'interfaces' => ['Character'],
            'description' => 'The Hero type',
            'fields' => [
                [
                    'name' => 'race',
                    'type' => 'Race',
                ],
                [
                    'name' => 'name',
                    'type' => 'String!',
                    'description' => 'The name of the character',
                ],
                [
                    'name' => 'friends',
                    'type' => '[Character]',
                    'description' => 'The friends of the character',
                    'resolver' => "@=resolver('App\\MyResolver::getFriends')",
                ],
            ],
        ], $object->toArray());
    }

    public function testTypeObjectWithProviderFields(): void
    {
        $object = $this->getType('Droid', ObjectConfiguration::class);

        $this->assertEquals([
            'name' => 'Droid',
            'description' => 'The Droid type',
            'interfaces' => ['Character'],
            'isTypeOf' => "@=isTypeOf('App\Entity\Droid')",
            'fields' => [
                ['name' => 'memory', 'type' => 'Int!'],
                ['name' => 'name', 'type' => 'String!', 'description' => 'The name of the character'],
                ['name' => 'friends', 'type' => '[Character]', 'description' => 'The friends of the character', 'resolver' => "@=resolver('App\\MyResolver::getFriends')"],
                [
                    'name' => 'planet_allowedPlanets',
                    'type' => '[Planet]',
                    'resolver' => '@=call(service(\'Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository\').getAllowedPlanetsForDroids, arguments({}, args))',
                    'extensions' => [
                        ['alias' => 'access', 'configuration' => 'override_access'],
                        ['alias' => 'public', 'configuration' => 'default_public'],
                    ],
                ],
                [
                    'name' => 'planet_armorResistance',
                    'type' => 'Int!',
                    'resolver' => '@=call(service(\'Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository\').getArmorResistance, arguments({}, args))',
                    'extensions' => [
                        ['alias' => 'access', 'configuration' => 'default_access'],
                        ['alias' => 'public', 'configuration' => 'default_public'],
                    ],
                ],
            ],
        ], $object->toArray());
    }

    public function testTypeObjectWithFieldsMethod(): void
    {
        $object = $this->getType('Sith', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'Sith',
            'description' => 'The Sith type',
            'interfaces' => ['Character'],
            'fieldsResolver' => '@=value',
            'fields' => [
                ['name' => 'realName', 'type' => 'String!', 'extensions' => [['alias' => 'access', 'configuration' => "hasRole('SITH_LORD')"]]],
                ['name' => 'location', 'type' => 'String!',  'extensions' => [['alias' => 'public', 'configuration' => "hasRole('SITH_LORD')"]]],
                ['name' => 'currentMaster', 'type' => 'Sith', 'resolver' => "@=service('master_resolver').getMaster(value)"],

                ['name' => 'name', 'type' => 'String!', 'description' => 'The name of the character'],
                ['name' => 'friends', 'type' => '[Character]', 'description' => 'The friends of the character', 'resolver' => "@=resolver('App\\MyResolver::getFriends')"],

                [
                    'name' => 'victims',
                    'type' => '[Character]',
                    'arguments' => [
                        ['name' => 'jediOnly', 'type' => 'Boolean', 'description' => 'Only Jedi victims', 'defaultValue' => false],
                    ],
                    'resolver' => '@=call(value.getVictims, arguments({jediOnly: "Boolean"}, args))',
                ],
            ],
            'extensions' => [
                ['alias' => 'access', 'configuration' => 'isAuthenticated()'],
                ['alias' => 'public', 'configuration' => 'isAuthenticated()'],
            ],
        ], $object->toArray());
    }

    public function testTypeObjectWithFieldBuilder(): void
    {
        $object = $this->getType('Planet', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'Planet',
            'description' => 'The Planet type',
            'fields' => [
                ['name' => 'name', 'type' => 'String!'],
                ['name' => 'location', 'type' => 'GalaxyCoordinates'],
                ['name' => 'population', 'type' => 'Int!'],
                [
                    'name' => 'notes',
                    'extensions' => [
                        ['alias' => BuilderExtension::ALIAS, 'configuration' => ['name' => 'NoteFieldBuilder', 'configuration' => ['option1' => 'value1']]],
                    ],
                    'type' => 'Builder',
                ],
                [
                    'name' => 'closestPlanet',
                    'type' => 'Planet',
                    'resolver' => "@=resolver('closest_planet', [args['filter']])",
                    'extensions' => [
                        ['alias' => BuilderExtension::ALIAS, 'configuration' => ['name' => 'PlanetFilterArgBuilder', 'configuration' => ['option2' => 'value2']]],
                    ],
                ],
                [
                    'name' => 'notesDeprecated',
                    'extensions' => [
                        ['alias' => BuilderExtension::ALIAS, 'configuration' => ['name' => 'NoteFieldBuilder', 'configuration' => ['option1' => 'value1']]],
                    ],
                    'type' => 'Builder',
                ],
                [
                    'name' => 'closestPlanetDeprecated',
                    'type' => 'Planet',
                    'resolver' => "@=resolver('closest_planet', [args['filter']])",
                    'extensions' => [
                        ['alias' => BuilderExtension::ALIAS, 'configuration' => ['name' => 'PlanetFilterArgBuilder', 'configuration' => ['option2' => 'value2']]],
                    ],
                ],
            ],
        ], $object->toArray());
    }

    public function testTypeObjectWithFieldsBuilder(): void
    {
        $object = $this->getType('Crystal', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'Crystal',
            'fields' => [
                ['name' => 'color', 'type' => 'String!'],
            ],
            'extensions' => [
                [
                    'alias' => BuilderExtension::ALIAS,
                    'configuration' => [
                        'name' => 'MyFieldsBuilder',
                        'configuration' => ['param1' => 'val1'],
                    ],
                ],
            ],
        ], $object->toArray());
    }

    public function testTypeObjectExtendingTypeObject(): void
    {
        $object = $this->getType('Cat', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'Cat',
            'description' => 'The Cat type',
            'fields' => [
                ['name' => 'lives', 'type' => 'Int!'],
                ['name' => 'toys', 'type' => '[String!]!'],
                ['name' => 'name', 'type' => 'String!', 'description' => 'The name of the animal'],
            ],
        ], $object->toArray());
    }

    public function testInput(): void
    {
        $input = $this->getType('PlanetInput', InputConfiguration::class);

        $this->assertEquals([
            'name' => 'PlanetInput',
            'description' => 'Planet Input type description',
            'fields' => [
                ['name' => 'name', 'type' => 'String!', 'defaultValue' => 'Sun'],
                ['name' => 'population', 'type' => 'Int!'],
                ['name' => 'description', 'type' => 'String!'],
                ['name' => 'diameter', 'type' => 'Int'],
                ['name' => 'variable', 'type' => 'Int!'],
                ['name' => 'tags', 'type' => '[String]!', 'defaultValue' => []],
            ],
        ], $input->toArray());
    }

    public function testInterfaces(): void
    {
        $interface = $this->getType('WithArmor', InterfaceConfiguration::class);
        $this->assertEquals([
            'name' => 'WithArmor',
            'description' => 'The armored interface',
            'typeResolver' => '@=resolver(\'character_type\', [value])',
            'extensions' => [
                ['alias' => 'CustomExtension', 'configuration' => ['config1' => 12]],
            ],
        ], $interface->toArray());
    }

    public function testEnum(): void
    {
        $interface = $this->getType('Race', EnumConfiguration::class);
        $this->assertEquals([
            'name' => 'Race',
            'description' => 'The list of races!',
            'values' => [
                ['name' => 'HUMAIN', 'value' => 1],
                ['name' => 'CHISS', 'value' => '2', 'description' => 'The Chiss race'],
                ['name' => 'ZABRAK', 'value' => '3', 'deprecation' => 'The Zabraks have been wiped out'],
                ['name' => 'TWILEK', 'value' => '4'],
            ],
        ], $interface->toArray());
    }

    public function testUnion(): void
    {
        $union = $this->getType('ResultSearch', UnionConfiguration::class);
        $this->assertEquals([
            'name' => 'ResultSearch',
            'description' => 'A search result',
            'types' => ['Hero', 'Droid', 'Sith'],
            'typeResolver' => '@=value.getType()',
        ], $union->toArray());

        $union = $this->getType('SearchResult2', UnionConfiguration::class);
        $this->assertEquals([
            'name' => 'SearchResult2',
            'types' => ['Hero', 'Droid', 'Sith'],
            'typeResolver' => "@=call('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Union\\\\SearchResult2::resolveType', [service('overblog_graphql.type_resolver'), value], true)",
        ], $union->toArray());
    }

    public function testUnionAutoguessed(): void
    {
        $union = $this->getType('Killable', UnionConfiguration::class);
        $this->assertEquals([
            'name' => 'Killable',
            'types' => ['Hero', 'Mandalorian',  'Sith'],
            'typeResolver' => '@=value.getType()',
        ], $union->toArray());
    }

    public function testInterfaceAutoguessed(): void
    {
        $interface = $this->getType('Mandalorian', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'Mandalorian',
            'interfaces' => ['Character', 'WithArmor'],
            'fields' => [
                ['name' => 'name', 'type' => 'String!', 'description' => 'The name of the character'],
                ['name' => 'friends', 'type' => '[Character]', 'description' => 'The friends of the character', 'resolver' => "@=resolver('App\\MyResolver::getFriends')"],
                [
                    'name' => 'planet_armorResistance',
                    'type' => 'Int!',
                    'resolver' => '@=call(service(\'Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository\').getArmorResistance, arguments({}, args))',
                    'extensions' => [
                        ['alias' => 'access', 'configuration' => 'default_access'],
                        ['alias' => 'public', 'configuration' => 'default_public'],
                    ],
                ],
            ],
        ], $interface->toArray());
    }

    public function testScalar(): void
    {
        $scalar = $this->getType('GalaxyCoordinates', ScalarConfiguration::class);
        $this->assertEquals([
            'name' => 'GalaxyCoordinates',
            'serialize' => 'Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Scalar\GalaxyCoordinates::serialize',
            'parseValue' => 'Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Scalar\GalaxyCoordinates::parseValue',
            'parseLiteral' => 'Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Scalar\GalaxyCoordinates::parseLiteral',
            'description' => 'The galaxy coordinates scalar',
        ], $scalar->toArray());

        $scalar = $this->getType('MyScalar', ScalarConfiguration::class);
        $this->assertEquals([
            'name' => 'MyScalar',
            'scalarType' => '@=newObject(\'App\Type\EmailType\')',
        ], $scalar->toArray());

        $scalar = $this->getType('MyScalar2', ScalarConfiguration::class);
        $this->assertEquals([
            'name' => 'MyScalar2',
            'scalarType' => '@=newObject(\'App\Type\EmailType\')',
        ], $scalar->toArray());
    }

    public function testProviderRootQuery(): void
    {
        $object = $this->getType('RootQuery', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'RootQuery',
            'fields' => [
                [
                    'name' => 'countSecretWeapons',
                    'type' => 'Int!',
                    'resolver' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\WeaponRepository').countSecretWeapons, arguments({}, args))",
                ],
                [
                    'name' => 'planet_searchPlanet',
                    'type' => '[Planet]',
                    'arguments' => [['name' => 'keyword', 'type' => 'String!']],
                    'resolver' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').searchPlanet, arguments({keyword: \"String!\"}, args))",
                    'extensions' => [
                        ['alias' => 'access', 'configuration' => 'default_access'],
                        ['alias' => 'public', 'configuration' => 'default_public'],
                    ],
                ],
                [
                    'name' => 'planet_searchStar',
                    'type' => '[Planet]',
                    'arguments' => [['name' => 'distance', 'type' => 'Int!']],
                    'resolver' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').searchStar, arguments({distance: \"Int!\"}, args))",
                    'extensions' => [
                        ['alias' => 'access', 'configuration' => 'default_access'],
                        ['alias' => 'public', 'configuration' => 'default_public'],
                    ],
                ],
                [
                    'name' => 'planet_isPlanetDestroyed',
                    'type' => 'Boolean!',
                    'arguments' => [['name' => 'planetId', 'type' => 'Int!']],
                    'resolver' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').isPlanetDestroyed, arguments({planetId: \"Int!\"}, args))",
                    'extensions' => [
                        ['alias' => 'access', 'configuration' => 'default_access'],
                        ['alias' => 'public', 'configuration' => 'default_public'],
                    ],
                ],
            ],
        ], $object->toArray());
    }

    public function testProviderRootMutation(): void
    {
        $object = $this->getType('RootMutation', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'RootMutation',
            'fields' => [
                [
                    'name' => 'planet_createPlanet',
                    'type' => 'Planet',
                    'arguments' => [['name' => 'planetInput', 'type' => 'PlanetInput!']],
                    'resolver' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').createPlanet, arguments({planetInput: \"PlanetInput!\"}, args))",
                    'extensions' => [
                        ['alias' => 'public', 'configuration' => 'override_public'],
                        ['alias' => 'access', 'configuration' => 'default_access'],
                    ],
                ],
                [
                    'name' => 'planet_destroyPlanet',
                    'type' => 'Boolean!',
                    'arguments' => [['name' => 'planetId', 'type' => 'Int!']],
                    'resolver' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').destroyPlanet, arguments({planetId: \"Int!\"}, args))",
                    'extensions' => [
                        ['alias' => 'access', 'configuration' => 'default_access'],
                        ['alias' => 'public', 'configuration' => 'default_public'],
                    ],
                ],
            ],
        ], $object->toArray());
    }

    public function testProvidersMultischema(): void
    {
        $object = $this->getType('RootQuery2', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'RootQuery2',
            'fields' => [
                [
                    'name' => 'hasSecretWeapons',
                    'type' => 'Boolean!',
                    'resolver' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\WeaponRepository').hasSecretWeapons, arguments({}, args))",
                ],
                [
                    'name' => 'planet_getPlanetSchema2',
                    'type' => 'Planet',
                    'resolver' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').getPlanetSchema2, arguments({}, args))",
                    'extensions' => [
                        ['alias' => 'access', 'configuration' => 'default_access'],
                        ['alias' => 'public', 'configuration' => 'default_public'],
                    ],
                ],
                [
                    'name' => 'planet_isPlanetDestroyed',
                    'type' => 'Boolean!',
                    'arguments' => [['name' => 'planetId', 'type' => 'Int!']],
                    'resolver' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').isPlanetDestroyed, arguments({planetId: \"Int!\"}, args))",
                    'extensions' => [
                        ['alias' => 'access', 'configuration' => 'default_access'],
                        ['alias' => 'public', 'configuration' => 'default_public'],
                    ],
                ],
            ],
        ], $object->toArray());

        $object = $this->getType('RootMutation2', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'RootMutation2',
            'fields' => [
                [
                    'name' => 'createLightsaber',
                    'type' => 'Boolean!',
                    'resolver' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\WeaponRepository').createLightsaber, arguments({}, args))",
                ],
                [
                    'name' => 'planet_createPlanetSchema2',
                    'type' => 'Planet',
                    'arguments' => [['name' => 'planetInput', 'type' => 'PlanetInput!']],
                    'resolver' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').createPlanetSchema2, arguments({planetInput: \"PlanetInput!\"}, args))",
                    'extensions' => [
                        ['alias' => 'public', 'configuration' => 'override_public'],
                        ['alias' => 'access', 'configuration' => 'default_access'],
                    ],
                ],
                [
                    'name' => 'planet_destroyPlanet',
                    'type' => 'Boolean!',
                    'arguments' => [['name' => 'planetId', 'type' => 'Int!']],
                    'resolver' => "@=call(service('Overblog\\\\GraphQL\\\\Bundle\\\\ConfigurationMetadataBundle\\\\Tests\\\\fixtures\\\\Repository\\\\PlanetRepository').destroyPlanet, arguments({planetId: \"Int!\"}, args))",
                    'extensions' => [
                        ['alias' => 'access', 'configuration' => 'default_access'],
                        ['alias' => 'public', 'configuration' => 'default_public'],
                    ],
                ],
            ],
        ], $object->toArray());
    }

    public function testDoctrineGuessing(): void
    {
        $object = $this->getType('Lightsaber', ObjectConfiguration::class);
        $this->assertEquals([
                'name' => 'Lightsaber',
                'fields' => [
                    ['name' => 'color', 'type' => 'String!'],
                    ['name' => 'text', 'type' => 'String!'],
                    ['name' => 'string', 'type' => 'String!'],
                    ['name' => 'size', 'type' => 'Int'],
                    ['name' => 'holders', 'type' => '[Hero]!'],
                    ['name' => 'creator', 'type' => 'Hero!'],
                    ['name' => 'crystal', 'type' => 'Crystal!'],
                    ['name' => 'battles', 'type' => '[Battle]!'],
                    ['name' => 'currentHolder', 'type' => 'Hero'],
                    ['name' => 'tags', 'type' => '[String]!', 'deprecation' => 'No more tags on lightsabers'],
                    ['name' => 'float', 'type' => 'Float!'],
                    ['name' => 'decimal', 'type' => 'Float!'],
                    ['name' => 'bool', 'type' => 'Boolean!'],
                    ['name' => 'boolean', 'type' => 'Boolean!'],
                ],
            ], $object->toArray());
    }

    public function testArgsAndReturnGuessing(): void
    {
        $object = $this->getType('Battle', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'Battle',
            'fields' => [
                [
                    'name' => 'planet',
                    'type' => 'Planet',
                    'extensions' => [
                        ['alias' => 'complexity', 'configuration' => '@=100 + childrenComplexity'],
                    ],
                ],
                [
                    'name' => 'casualties',
                    'type' => 'Int',
                    'arguments' => [
                        ['name' => 'areaId', 'type' => 'Int!'],
                        ['name' => 'raceId', 'type' => 'String!'],
                        ['name' => 'dayStart', 'type' => 'Int'],
                        ['name' => 'dayEnd', 'type' => 'Int'],
                        ['name' => 'nameStartingWith', 'type' => 'String', 'defaultValue' => ''],
                        ['name' => 'planet', 'type' => 'PlanetInput'],
                        ['name' => 'away', 'type' => 'Boolean', 'defaultValue' => false],
                        ['name' => 'maxDistance', 'type' => 'Float'],
                    ],
                    'resolver' => '@=call(value.getCasualties, arguments({areaId: "Int!", raceId: "String!", dayStart: "Int", dayEnd: "Int", nameStartingWith: "String", planet: "PlanetInput", away: "Boolean", maxDistance: "Float"}, args))',
                    'extensions' => [
                        ['alias' => 'complexity', 'configuration' => '@=childrenComplexity * 5'],
                    ],
                ],
            ],
        ], $object->toArray());
    }

    public function testRelayConnectionAuto(): void
    {
        $connection = $this->getType('EnemiesConnection', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'EnemiesConnection',
            'extensions' => [
                ['alias' => 'builder', 'configuration' => ['name' => 'relay-connection', 'configuration' => ['edgeType' => 'EnemiesConnectionEdge']]],
            ],
        ], $connection->toArray());

        $edge = $this->getType('EnemiesConnectionEdge', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'EnemiesConnectionEdge',
            'extensions' => [
                ['alias' => 'builder', 'configuration' => ['name' => 'relay-edge', 'configuration' => ['nodeType' => 'Character']]],
            ],
        ], $edge->toArray());
    }

    public function testRelayConnectionEdge(): void
    {
        $connection = $this->getType('FriendsConnection', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'FriendsConnection',
            'extensions' => [
                ['alias' => 'builder', 'configuration' => ['name' => 'relay-connection', 'configuration' => ['edgeType' => 'FriendsConnectionEdge']]],
            ],
        ], $connection->toArray());

        $edge = $this->getType('FriendsConnectionEdge', ObjectConfiguration::class);
        $this->assertEquals([
            'name' => 'FriendsConnectionEdge',
            'extensions' => [
                ['alias' => 'builder', 'configuration' => ['name' => 'relay-edge', 'configuration' => ['nodeType' => 'Character']]],
            ],
        ], $edge->toArray());
    }

    public function testInvalidParamGuessing(): void
    {
        $file = __DIR__.'/fixtures/Invalid/argumentGuessing';
        try {
            $this->getConfiguration([$file]);
            $this->fail('Missing type hint for auto-guessed argument should have raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/Argument n°1 "\$test"/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidReturnGuessing(): void
    {
        $file = __DIR__.'/fixtures/Invalid/returnTypeGuessing';
        try {
            $this->getConfiguration([$file]);
            $this->fail('Missing type hint for auto-guessed return type should have raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/is missing on method "guessFail" and cannot be auto-guessed from the following type guessers/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidDoctrineRelationGuessing(): void
    {
        $file = __DIR__.'/fixtures/Invalid/doctrineRelationGuessing';
        try {
            $this->getConfiguration([$file]);
            $this->fail('Auto-guessing field type from doctrine relation on a non graphql entity should failed with an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/Unable to auto-guess GraphQL type from Doctrine target class/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidDoctrineTypeGuessing(): void
    {
        $file = __DIR__.'/fixtures/Invalid/doctrineTypeGuessing';
        try {
            $this->getConfiguration([$file]);
            $this->fail('Auto-guessing field type from doctrine relation on a non graphql entity should failed with an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/Unable to auto-guess GraphQL type from Doctrine type "invalidType"/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidUnion(): void
    {
        $file = __DIR__.'/fixtures/Invalid/union';
        try {
            $this->getConfiguration([$file]);
            $this->fail('Union with missing resolve type shoud have raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/The metadata '.preg_quote($this->formatMetadata('Union')).' has no "resolveType"/', $e->getPrevious()->getMessage());
        }
    }

    public function testFieldOnPrivateProperty(): void
    {
        $file = __DIR__.'/fixtures/Invalid/privateMethod';
        try {
            $this->getConfiguration([$file]);
            $this->fail($this->formatMetadata('Access').' annotation without a '.$this->formatMetadata('Field').' annotation should raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/The metadata '.preg_quote($this->formatMetadata('Field')).' can only be applied to public method/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidProviderQueryOnMutation(): void
    {
        $file = __DIR__.'/fixtures/Invalid/provider';
        try {
            $this->getConfiguration([$file]);
            $this->fail('Using @Query or #Query targeting mutation type should raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/The provider provides a "query" but the type expects a "mutation"/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidProviderMutationOnQuery(): void
    {
        $file = __DIR__.'/fixtures/Invalid/provider2';
        try {
            $this->getConfiguration([$file]);
            $this->fail('Using @Mutation or #Mutation targeting regular type should raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(MetadataConfigurationException::class, $e);
            $this->assertMatchesRegularExpression('/The provider provides a "mutation" but the type expects a "query"/', $e->getPrevious()->getMessage());
        }
    }
}
