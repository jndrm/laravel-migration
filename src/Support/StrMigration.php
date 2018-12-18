<?php

namespace Drmer\Laravel\Migration\Support;

use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\Generator\CombGenerator;
use Ramsey\Uuid\Codec\TimestampFirstCombCodec;

class StrMigration
{
    public static function migrate()
    {
        Str::macro('uuid', function () {
            return Uuid::uuid4();
        });

        Str::macro('orderedUuid', function () {
            $factory = new UuidFactory;

            $factory->setRandomGenerator(new CombGenerator(
                $factory->getRandomGenerator(),
                $factory->getNumberConverter()
            ));

            $factory->setCodec(new TimestampFirstCombCodec(
                $factory->getUuidBuilder()
            ));

            return $factory->uuid4();
        });
    }
}
