<?php

namespace App\Tags;

use Illuminate\Support\HtmlString;
use Statamic\Tags\Tags;

class Icon extends Tags
{
    /**
     * The {{ icon }} tag.
     *
     * Usage examples in Antlers:
     *
     * 1. Using wildcard syntax (preferred):
     *    {{ icon:lucide-chevrons-up-down class="w-6 h-6 text-primary" }}
     *
     * 2. Using parameters:
     *    {{ icon name="lucide-chevrons-up-down" class="w-6 h-6" }}
     */

    /**
     * Renders an icon when called as {{ icon name="lucide-chevrons-up-down" ... }}.
     */
    public function index(): HtmlString|string
    {
        $name = $this->params->get('name');

        if (!$name) {
            return '';
        }

        return $this->renderIcon($name);
    }

    /**
     * Allows wildcard usage so any segment after the colon becomes the icon name.
     * Example: {{ icon:lucide-chevrons-up-down }}
     */
    public function wildcard(string $name): HtmlString|string
    {
        return $this->renderIcon($name);
    }

    /**
     * Build and return the SVG icon as an unescaped HTML string.
     */
    protected function renderIcon(string $name): HtmlString|string
    {
        // Gather all parameters except for the reserved "name".
        $attributes = collect($this->params->all())
            ->except('name')
            ->toArray();

        // blade-ui-kit helper to generate the SVG icon component.
        // The helper returns a BladeIcon instance; calling ->toHtml() renders the SVG string.
        $svg = svg($name, $attributes)->toHtml();

        // Return as HtmlString so Statamic will not escape the markup.
        return new HtmlString($svg);
    }
}
