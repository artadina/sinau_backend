<?php

namespace App\Services;

use App\Models\Unit;
use HTMLPurifier;
use HTMLPurifier_Config;

class MaterialHtmlSanitizer
{
    // FR-A16, NFR-18: persist only the editor's safe HTML subset and this unit's private image URLs.
    public function sanitize(?string $html, Unit $unit): string
    {
        $html = trim($html ?? '');

        if ($html === '') {
            return '';
        }

        if (strip_tags($html) === $html) {
            $html = '<p>'.nl2br(e($html), false).'</p>';
        }

        $imagePrefix = '/admin/units/'.$unit->id.'/material/images/';
        $html = preg_replace_callback('/<img\b[^>]*>/i', function (array $matches) use ($imagePrefix): string {
            $tag = $matches[0];

            if (! preg_match('/\bsrc\s*=\s*(["\'])(.*?)\1/i', $tag, $srcMatch)) {
                return '';
            }

            $src = html_entity_decode($srcMatch[2], ENT_QUOTES | ENT_HTML5);
            $validImageUrl = str_starts_with($src, $imagePrefix)
                && preg_match('/^[a-f0-9-]+\.(?:jpg|jpeg|png)$/i', substr($src, strlen($imagePrefix)));

            return $validImageUrl ? $tag : '';
        }, $html) ?? '';

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,b,em,i,h2,h3,ul,ol,li,img[src|alt]');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
        $config->set('Cache.SerializerPath', storage_path('framework/cache'));

        return (new HTMLPurifier($config))->purify($html);
    }
}
