<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Invalid\doctrineTypeGuessing;

use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Type
 */
#[GQL\Type]
class InvalidDoctrineTypeGuessing
{
    /**
     * @ORM\Column(type="invalidType")
     * @GQL\Field
     *
     * @var mixed
     */
    #[GQL\Field]
    protected $myRelation;
}
