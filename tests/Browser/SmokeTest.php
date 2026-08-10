<?php

declare(strict_types=1);

it('loads key pages without javascript errors', function (): void {
    $pages = visit(['/', '/blog']);

    $pages->assertNoJavaScriptErrors();
});
