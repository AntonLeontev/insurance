<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackRequest;
use App\Models\User;
use App\Services\FeedbackMailService;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class FeedbackController extends Controller
{
    public function store(StoreFeedbackRequest $request, FeedbackMailService $service): Response
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array<int, UploadedFile> $screenshots */
        $screenshots = Arr::wrap($request->file('screenshots') ?? []);

        $service->send(
            $user,
            $request->validated('message'),
            $screenshots,
        );

        return response()->noContent();
    }
}
