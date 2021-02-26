<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Input;

use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Input
 * @GQL\Description("Planet Input type description")
 */
#[GQL\Input]
#[GQL\Description("Planet Input type description")]
class Planet
{
    /**
     * @GQL\InputField(type="String!", defaultValue="Sun")
     */
    #[GQL\InputField(type: "String!")]
    protected string $name = 'Sun';

    /**
     * @GQL\InputField(type="Int!")
     */
    #[GQL\InputField(type: "Int!")]
    protected string $population;

    /**
     * @GQL\InputField
     */
    #[GQL\InputField]
    protected string $description;

    /**
     * @GQL\InputField
     * @ORM\Column(type="integer", nullable=true)
     */
    #[GQL\InputField]
    // @phpstan-ignore-next-line
    protected $diameter;

    /**
     * @GQL\InputField
     * @ORM\Column(type="boolean")
     */
    #[GQL\InputField]
    protected int $variable;

    // @phpstan-ignore-next-line
    protected $dummy;

    /**
     * @GQL\InputField(defaultValue={})
     * @ORM\Column(type="text[]")
     */
    #[GQL\InputField]
    protected array $tags = [];
}
