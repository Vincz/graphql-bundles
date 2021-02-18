<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Scalar;

use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

/**
 * @GQL\Scalar(name="MyScalar", scalarType="newObject('App\Type\EmailType')")
 * @GQL\Scalar(name="MyScalar2", scalarType="newObject('App\Type\EmailType')")
 */
#[GQL\Scalar(name: "MyScalar", scalarType: "newObject('App\Type\EmailType')")]
#[GQL\Scalar(name: "MyScalar2", scalarType: "newObject('App\Type\EmailType')")]
class MyScalar2
{
}
