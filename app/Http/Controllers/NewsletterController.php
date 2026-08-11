<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\NewsletterRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class NewsletterController
{
    public function __invoke(NewsletterRequest $request): JsonResponse
    {
        return response()->json(
            [
                'success' => true,
            ],
            Response::HTTP_OK
        );
    }
}
