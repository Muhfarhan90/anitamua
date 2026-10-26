<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

class RichText
{
    public static function sanitize(?string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('Cache.SerializerPath', storage_path('framework/cache'));
        $config->set('HTML.Allowed', 'h1[class],h2[class],h3[class],p[class],br,strong,em,u,s,blockquote[class],ul,ol,li[class],a[href]');
        $config->set('Attr.AllowedClasses', array_merge(
            ['ql-align-center', 'ql-align-right', 'ql-align-justify'],
            array_map(fn ($level) => 'ql-indent-'.$level, range(1, 8)),
        ));
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);

        $cleanHtml = (new HTMLPurifier($config))->purify($html ?? '');

        return trim(strip_tags($cleanHtml)) === '' ? '' : $cleanHtml;
    }
}
