<?php

namespace Visualbuilder\EmailTemplates\Facades;

use Illuminate\Support\Facades\Facade;

class TokenHelper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Visualbuilder\EmailTemplates\Contracts\TokenReplacementInterface::class;
    }

    public static function replace($content, $models)
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor())->replaceTokens($content, $models);
    }
}
