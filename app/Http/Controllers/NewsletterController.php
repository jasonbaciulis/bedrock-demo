<?php

namespace App\Http\Controllers;

use Exception;
use MailchimpMarketing\ApiClient;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NewsletterController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email:rfc,dns'],
            'consent' => ['required', 'accepted'],
        ]);
        $list = config('services.mailchimp.lists.newsletter');

        try {
            $this->client()->lists->addListMember($list, [
                'email_address' => $request->email,
                'status' => 'subscribed',
            ]);
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => json_decode($e->getMessage())->message,
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return response()->json([
            'result' => 'success',
        ]);
    }

    protected function client(): ApiClient
    {
        return (new ApiClient())->setConfig([
            'apiKey' => config('services.mailchimp.key'),
            // To find the value for the server param log into your Mailchimp account and look at the URL in your browser.
            // You’ll see something like https://us19.admin.mailchimp.com/; the us19 part is what you need.
            'server' => 'us16',
        ]);
    }
}
