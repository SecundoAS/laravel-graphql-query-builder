<?php

declare(strict_types=1);

namespace Secundo\GraphQL;

class GraphQL
{
    public static function query(?string $name = null): Builder
    {
        return (new Builder())->query($name);
    }

    public static function mutation(?string $name = null): Builder
    {
        return (new Builder())->mutation($name);
    }
}
