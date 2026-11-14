<?php

declare(strict_types=1);

namespace App\Tags;

use Illuminate\Support\Str;
use Statamic\Fields\Values;
use Statamic\Tags\Tags;

final class BlockId extends Tags
{
    /**
     * The first block of a type keeps the plain slug, so anchors such as #faqs keep working.
     */
    public function index(): string
    {
        $slug = Str::slug($this->context->value('type'));
        $position = $this->positionAmongBlocksOfSameType();

        return $position > 1 ? "{$slug}-{$position}" : $slug;
    }

    private function positionAmongBlocksOfSameType(): int
    {
        // Replicator augments only sets that have fields into Values, so a set without fields stays an array.
        $blocks = collect($this->context->value('blocks'));
        $currentIndex = $blocks->search(fn (array|Values $block) => $block['id'] === $this->context->value('id'));

        return $blocks
            ->take($currentIndex + 1)
            ->where('type', $this->context->value('type'))
            ->count();
    }
}
