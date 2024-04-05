<?php


namespace Visualbuilder\EmailTemplates\Contracts;

interface TokenReplacementInterface
{
    public function replaceTokens($content, $models);
}
