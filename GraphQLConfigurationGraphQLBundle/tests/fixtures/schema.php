<?php

declare(strict_types=1);

return [
    [
        'name' => 'Query',
        'description' => 'Root Query',
        'fields' => [
            [
                'name' => 'hero',
                'type' => 'Character',
                'arguments' => [[
                    'name' => 'episodes',
                    'type' => '[Episode!]!',
                    'description' => 'Episode list to use to filter',
                    'defaultValue' => ['NEWHOPE', 'EMPIRE'],
                ]],
            ],
            [
                'name' => 'droid',
                'type' => 'Droid',
                'description' => 'search for a droid',
                'arguments' => [[
                    'name' => 'id',
                    'type' => 'ID!',
                ]],
            ],
        ],
    ],
    [
        'name' => 'Starship',
        'fields' => [
            ['name' => 'id', 'type' => 'ID!'],
            ['name' => 'name', 'type' => 'String!'],
            [
                'name' => 'length',
                'type' => 'Float',
                'arguments' => [[
                    'name' => 'unit',
                    'type' => 'LengthUnit',
                    'defaultValue' => 'METER',
                ]],
            ],
        ],
    ], [
        'name' => 'Episode',
        'values' => [
            ['name' => 'NEWHOPE', 'value' => 'NEWHOPE'],
            [
                'name' => 'EMPIRE',
                'description' => 'Star Wars: Episode V – The Empire Strikes Back',
                'value' => 'EMPIRE',
            ],
            [
                'name' => 'JEDI',
                'deprecation' => 'No longer supported',
                'value' => 'JEDI',
            ],
        ],
    ],
    [
        'name' => 'Character',
        'fields' => [
            ['name' => 'id', 'type' => 'ID!'],
            ['name' => 'name', 'type' => 'String!'],
            ['name' => 'friends', 'type' => '[Character]'],
            ['name' => 'appearsIn', 'type' => '[Episode]!'],
            ['name' => 'deprecatedField',
                'type' => 'String!',
                'deprecation' => 'This field was deprecated!',
            ],
        ],
    ], [
        'name' => 'Human',
        'interfaces' => ['Character'],
        'fields' => [
            ['name' => 'id', 'type' => 'ID!'],
            ['name' => 'name', 'type' => 'String!'],
            ['name' => 'friends', 'type' => '[Character]'],
            ['name' => 'appearsIn', 'type' => '[Episode]!'],
            ['name' => 'starships', 'type' => '[Starship]'],
            ['name' => 'totalCredits', 'type' => 'Int'],
        ],
    ],
    [
        'name' => 'Droid',
        'interfaces' => ['Character'],
        'fields' => [
            ['name' => 'id', 'type' => 'ID!'],
            [
                'name' => 'name',
                'type' => 'String!',
                'extensions' => [
                    ['alias' => 'access', 'configuration' => ['foo', 'bar']],
                ],
            ],
            ['name' => 'friends', 'type' => '[Character]'],
            ['name' => 'appearsIn', 'type' => '[Episode]!'],
            ['name' => 'primaryFunction', 'type' => 'String'],
        ],
    ], [
        'name' => 'SearchResult',
        'types' => ['Human', 'Droid', 'Starship'],
    ],
    [
        'name' => 'ReviewInput',
        'fields' => [
            ['name' => 'stars', 'type' => 'Int!', 'defaultValue' => 5],
            ['name' => 'rate', 'type' => 'Float!', 'defaultValue' => 1.58],
            ['name' => 'commentary', 'type' => 'String'],
        ],
    ],
    [
        'name' => 'Year',
        'serialize' => Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter\CustomScalarNode::class.'::mustOverrideConfig',
        'parseValue' => Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter\CustomScalarNode::class.'::mustOverrideConfig',
        'parseLiteral' => Overblog\GraphQL\Bundle\ConfigurationGraphQLBundle\ASTConverter\CustomScalarNode::class.'::mustOverrideConfig',
    ],
];
