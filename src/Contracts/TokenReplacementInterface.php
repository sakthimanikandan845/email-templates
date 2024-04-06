<?php


namespace Visualbuilder\EmailTemplates\Contracts;

interface TokenReplacementInterface
{
    public function replaceTokens(string $content, $models);
}
