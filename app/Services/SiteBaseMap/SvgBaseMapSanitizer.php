<?php

namespace App\Services\SiteBaseMap;

use DomainException;
use DOMDocument;
use DOMElement;

class SvgBaseMapSanitizer
{
    /**
     * 生のSVG文字列を検証・サニタイズし、正規化済みXMLとviewBoxを抽出する
     *
     * @return array{xml: string, viewbox: string}
     */
    public function sanitizeAndExtract(string $rawContent): array
    {
        if (trim($rawContent) === '') {
            throw new DomainException('SVGファイルの内容が空です。');
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $prevUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($rawContent, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prevUseInternalErrors);

        $root = $dom->documentElement;
        if (! $loaded || ! $root instanceof DOMElement || strtolower((string) ($root->localName ?: $root->nodeName)) !== 'svg') {
            throw new DomainException('有効なSVGファイルではありません。');
        }

        $this->sanitizeSvgDom($dom);

        $viewbox = $this->extractViewboxFromSvgRoot($root);

        $sanitizedXml = $dom->saveXML();
        if ($sanitizedXml === false) {
            throw new DomainException('SVGファイルのサニタイズ保存に失敗しました。');
        }

        return [
            'xml' => $sanitizedXml,
            'viewbox' => $viewbox,
        ];
    }

    /**
     * SVG DOM ツリーから危険な要素・イベントハンドラ・スクリプトURIを除去
     */
    protected function sanitizeSvgDom(DOMDocument $dom): void
    {
        $forbiddenTags = ['script', 'foreignobject', 'iframe', 'object', 'embed'];
        $elementsToRemove = [];

        $allElements = $dom->getElementsByTagName('*');
        foreach ($allElements as $element) {
            $tagName = strtolower((string) ($element->localName ?: $element->nodeName));
            if (in_array($tagName, $forbiddenTags, true)) {
                $elementsToRemove[] = $element;

                continue;
            }

            $attrsToRemove = [];
            if ($element->hasAttributes()) {
                foreach ($element->attributes as $attr) {
                    $attrName = strtolower((string) $attr->nodeName);
                    $attrLocal = strtolower((string) ($attr->localName ?: $attr->nodeName));
                    $attrVal = trim((string) $attr->nodeValue);

                    if (str_starts_with($attrName, 'on')) {
                        $attrsToRemove[] = $attr;

                        continue;
                    }

                    if (in_array($attrLocal, ['href', 'src', 'action', 'formaction'], true)
                        && preg_match('/^\s*(?:(?:javascript|vbscript)\s*:|data\s*:\s*text\/html\b)/i', $attrVal) === 1
                    ) {
                        $attrsToRemove[] = $attr;

                        continue;
                    }

                    if ($attrLocal === 'style'
                        && preg_match('/(?:javascript\s*:|expression\s*\()/i', $attrVal) === 1
                    ) {
                        $attrsToRemove[] = $attr;
                    }
                }
            }

            foreach ($attrsToRemove as $attr) {
                $element->removeAttributeNode($attr);
            }
        }

        foreach ($elementsToRemove as $element) {
            $element->parentNode?->removeChild($element);
        }
    }

    /**
     * ルート `<svg>` 要素から viewBox を抽出（未指定時は width/height から生成）
     */
    protected function extractViewboxFromSvgRoot(DOMElement $root): string
    {
        $rawViewBox = '';
        if ($root->hasAttribute('viewBox')) {
            $rawViewBox = $root->getAttribute('viewBox');
        } elseif ($root->hasAttribute('viewbox')) {
            $rawViewBox = $root->getAttribute('viewbox');
        }

        $trimmed = trim($rawViewBox);
        if ($trimmed !== '' && preg_match('/^(-?\d+(?:\.\d+)?)[\s,]+(-?\d+(?:\.\d+)?)[\s,]+(\d+(?:\.\d+)?)[\s,]+(\d+(?:\.\d+)?)$/', $trimmed, $m) === 1) {
            return "{$m[1]} {$m[2]} {$m[3]} {$m[4]}";
        }

        $w = $this->parseNumericLength($root->getAttribute('width'));
        $h = $this->parseNumericLength($root->getAttribute('height'));
        if ($w !== null && $h !== null && (float) $w > 0 && (float) $h > 0) {
            return "0 0 {$w} {$h}";
        }

        return '0 0 842 1191';
    }

    /**
     * SVG の長さ属性から正の数値を取得（%指定は除外）
     */
    protected function parseNumericLength(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '' || str_contains($trimmed, '%')) {
            return null;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)(?:px|pt|mm|cm|in|pc|em|rem)?$/i', $trimmed, $m) === 1) {
            return $m[1];
        }

        return null;
    }
}
