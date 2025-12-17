<?php

namespace App\Tags;

use Statamic\Tags\Tags;

class BladeIcon extends Tags
{
    /**
     * The {{ blade_icon:icon-name }} tag.
     *
     * Renders a Blade icon component in Antlers templates.
     *
     * Usage:
     *   {{ blade_icon:lucide-x }}
     *   {{ blade_icon:lucide-check class="size-4" }}
     *   {{ blade_icon:heroicon-o-home class="size-6 text-gray-500" }}
     */
    public function wildcard(string $icon): string
    {
        return svg($icon, $this->params->all())->toHtml();
    }
}
