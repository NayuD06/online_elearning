<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Strips dangerous markup from rich-editor HTML before it is rendered.
 */
class HtmlSanitizer
{
    /**
     * Tags that are removed together with their content.
     *
     * @var list<string>
     */
    private const array FORBIDDEN_TAGS = [
        'script', 'style', 'object', 'embed', 'applet', 'form', 'input', 'button', 'textarea', 'select', 'meta', 'link', 'base',
        'svg', 'math', 'template', 'noscript', 'noembed', 'noframes', 'xmp', 'plaintext', 'frame', 'frameset', 'portal',
    ];

    /**
     * Attributes holding a URL that a browser may follow or load.
     *
     * @var list<string>
     */
    private const array URL_ATTRIBUTES = ['href', 'src', 'xlink:href', 'action', 'formaction', 'poster', 'background', 'data', 'cite'];

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $document = new DOMDocument;
        $previousSetting = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?><div id="sanitizer-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousSetting);

        $xpath = new DOMXPath($document);

        foreach (self::FORBIDDEN_TAGS as $tag) {
            foreach (iterator_to_array($document->getElementsByTagName($tag)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        foreach (iterator_to_array($document->getElementsByTagName('iframe')) as $iframe) {
            /** @var DOMElement $iframe */
            if (! preg_match('~^https://(www\.)?(youtube\.com|youtube-nocookie\.com|player\.vimeo\.com)/~', $iframe->getAttribute('src'))) {
                $iframe->parentNode?->removeChild($iframe);
            }
        }

        foreach (iterator_to_array($xpath->query('//@*') ?: []) as $attribute) {
            $name = strtolower($attribute->nodeName);
            // Browsers ignore whitespace and control characters inside a URL scheme ("java\tscript:").
            $value = strtolower(preg_replace('~[\x00-\x20]+~', '', $attribute->nodeValue ?? ''));

            if (str_starts_with($name, 'on') || $name === 'srcdoc' || (in_array($name, self::URL_ATTRIBUTES, true) && preg_match('~^(javascript|vbscript|data:(?!image/(png|jpe?g|gif|webp|avif);))~', $value))) {
                $attribute->ownerElement?->removeAttributeNode($attribute);
            }
        }

        $root = $document->getElementById('sanitizer-root');
        $output = '';

        foreach ($root?->childNodes ?? [] as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }
}
