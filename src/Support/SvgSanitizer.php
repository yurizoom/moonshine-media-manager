<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Support;

use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use YuriZoom\MoonShineMediaManager\Exceptions\MediaManagerException;

/**
 * Sanitizes uploaded SVG files to prevent stored XSS.
 *
 * SVG files are served inline from the public disk, so any embedded script,
 * event handler, or hostile URL inside an SVG executes in the admin panel
 * origin. This stripper removes the known-dangerous constructs while keeping
 * the document visually intact.
 */
final class SvgSanitizer
{
    /**
     * Elements that never have a legitimate purpose inside an uploaded icon
     * but are commonly used for XSS payloads.
     */
    private const BLOCKED_ELEMENTS = [
        'script',
        'foreignObject',
        'iframe',
        'embed',
        'object',
    ];

    /**
     * URL schemes that must not appear in href-ish attributes.
     * data:image/... raster images stay allowed; data:image/svg+xml is not
     * (it can nest another hostile document).
     */
    private const BLOCKED_HREF_PATTERN = '/^\s*(javascript|vbscript|data:image\/svg\+xml)/i';

    /**
     * Sanitize raw SVG content.
     *
     * @param  string  $content  raw SVG document
     * @return string sanitized SVG document
     *
     * @throws MediaManagerException when the content is not well-formed XML
     */
    public static function sanitize(string $content): string
    {
        $content = trim($content);

        if ($content === '') {
            throw new MediaManagerException(
                __('moonshine-media-manager::media-manager.error.invalid_svg')
            );
        }

        $previous = libxml_use_internal_errors(true);

        $document = new \DOMDocument();
        $loaded = $document->loadXML($content, LIBXML_NONET | LIBXML_COMPACT);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            Log::warning('[SvgSanitizer] rejected malformed SVG document');

            throw new MediaManagerException(
                __('moonshine-media-manager::media-manager.error.invalid_svg')
            );
        }

        self::removeBlockedElements($document);
        self::stripDangerousAttributes($document);

        $sanitized = $document->saveXML($document->documentElement);

        Log::debug('[SvgSanitizer] svg sanitized', [
            'bytes_in' => strlen($content),
            'bytes_out' => strlen((string) $sanitized),
        ]);

        return (string) $sanitized;
    }

    /**
     * True when the given file name should be routed through the sanitizer.
     */
    public static function appliesTo(string $extension): bool
    {
        return strtolower($extension) === 'svg';
    }

    /**
     * Remove blocked elements from the document tree.
     */
    private static function removeBlockedElements(\DOMDocument $document): void
    {
        foreach (self::BLOCKED_ELEMENTS as $element) {
            $nodes = $document->getElementsByTagNameNS('*', $element);

            self::eachNode($nodes, static function (DOMNode $node): void {
                $node->parentNode?->removeChild($node);
            });
        }

        // <animate attributeName="href" ...> can rewrite links at runtime.
        $animator = new DOMXPath($document);
        self::eachNode(
            $animator->query('//*[local-name()="animate" and @attributeName="href"]'),
            static function (DOMNode $node): void {
                $node->parentNode?->removeChild($node);
            },
        );
    }

    /**
     * Strip event handler attributes (on*) and hostile href values.
     */
    private static function stripDangerousAttributes(\DOMDocument $document): void
    {
        self::eachNode($document->getElementsByTagNameNS('*', '*'), static function (DOMNode $node): void {
            if (! $node instanceof DOMElement) {
                return;
            }

            foreach (iterator_to_array($node->attributes ?? []) as $attribute) {
                $name = $attribute->nodeName;

                $isEventHandler = preg_match('/^on\w+/i', $name) === 1;
                $isHrefLike = preg_match('/^(xlink:)?href$/i', $name) === 1;

                if ($isEventHandler) {
                    $node->removeAttribute($name);
                    continue;
                }

                if ($isHrefLike && preg_match(self::BLOCKED_HREF_PATTERN, $attribute->nodeValue ?? '') === 1) {
                    $node->removeAttribute($name);
                }
            }
        });
    }

    /**
     * Iterate a node list safely while the tree is being mutated.
     *
     * @param  DOMNodeList<DOMNode>|false  $nodes
     */
    private static function eachNode(DOMNodeList|false $nodes, callable $callback): void
    {
        if ($nodes === false) {
            return;
        }

        foreach (iterator_to_array($nodes) as $node) {
            $callback($node);
        }
    }
}
