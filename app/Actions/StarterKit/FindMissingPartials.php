<?php

declare(strict_types=1);

namespace App\Actions\StarterKit;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\View\Antlers\Language\Nodes\AntlersNode;
use Statamic\View\Antlers\Language\Nodes\Parameters\ParameterNode;
use Statamic\View\Antlers\Language\Parser\ComponentCompiler;
use Statamic\View\Antlers\Language\Parser\DocumentParser;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final readonly class FindMissingPartials
{
    private const array PARTIAL_TAGS = ['partial', 'include'];

    /**
     * @return list<string>
     */
    public function handle(string $exportPath): array
    {
        $viewsPath = "{$exportPath}/resources/views";
        $problems = [];

        foreach (Finder::create()->files()->in($viewsPath)->name('*.antlers.html') as $view) {
            array_push($problems, ...$this->missingPartialsOf($view, $viewsPath));
        }

        return $problems;
    }

    /**
     * @return list<string>
     */
    private function missingPartialsOf(SplFileInfo $view, string $viewsPath): array
    {
        $missingPartials = array_filter(
            array_unique($this->partialNamesIn($view->getContents())),
            fn (string $partial): bool => ! $this->partialExists($viewsPath, $partial),
        );

        return array_values(array_map(
            fn (string $partial): string => "resources/views/{$view->getRelativePathname()} includes the partial {$partial}, which was not exported",
            $missingPartials,
        ));
    }

    /**
     * Statamic compiles `<s:partial:name />` to Antlers before it parses a template.
     *
     * @return list<string>
     */
    private function partialNamesIn(string $template): array
    {
        $parser = new DocumentParser;
        $parser->parse(new ComponentCompiler()->compile($template));

        $names = [];

        foreach ($parser->getNodes() as $node) {
            if ($node instanceof AntlersNode && $this->isPartialTag($node)) {
                $names[] = $this->partialNameOf($node);
            }
        }

        return array_values(array_filter($names));
    }

    private function isPartialTag(AntlersNode $node): bool
    {
        return ! $node->isClosingTag && in_array($node->name?->name, self::PARTIAL_TAGS, true);
    }

    /**
     * A name built from a variable, such as `blocks/{type}` or `:src="handle"`, is only known at render time.
     */
    private function partialNameOf(AntlersNode $node): ?string
    {
        if ($node->interpolationRegions !== []) {
            return null;
        }

        $source = array_find($node->parameters, fn (ParameterNode $parameter): bool => $parameter->name === 'src');

        if ($source === null) {
            return $node->name?->methodPart;
        }

        return $source->isVariableReference ? null : $source->value;
    }

    /**
     * Statamic tries `_name`, `partials/name` and `partials/_name` before `name`.
     */
    private function partialExists(string $viewsPath, string $partial): bool
    {
        $path = Str::replace('.', '/', $partial);
        $underscored = Str::of($path)->replaceMatches('#([^/]+)$#', '_$1')->toString();

        return array_any(
            [$path, $underscored, "partials/{$path}", "partials/{$underscored}"],
            fn (string $candidate): bool => File::isFile("{$viewsPath}/{$candidate}.antlers.html"),
        );
    }
}
