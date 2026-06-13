<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\ReminderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReminderApiController extends Controller
{
    public function __construct(private ReminderService $reminderService) {}

    /**
     * POST /api/v1/reminders
     *
     * Accepts either a single reminder object or { "reminders": [ ... ] }.
     * Each reminder: { template, send_at, phone?, email?, name?, data? }
     */
    public function store(Request $request)
    {
        /** @var Client $client */
        $client = $request->attributes->get('api_client');

        $rows = $request->input('reminders');
        if (!is_array($rows)) {
            // Single-object payload.
            $rows = [$request->only(['template', 'send_at', 'phone', 'email', 'name', 'data'])];
        }

        if (empty($rows)) {
            return response()->json(['error' => 'No reminders provided.'], 422);
        }

        if (count($rows) > 1000) {
            return response()->json(['error' => 'Maximum 1000 reminders per request.'], 422);
        }

        $created = [];
        $errors  = [];

        foreach ($rows as $i => $row) {
            try {
                $reminder = $this->reminderService->create($client, (array) $row, 'api');
                $created[] = [
                    'id'       => $reminder->id,
                    'template' => $reminder->template_slug,
                    'send_at'  => $reminder->send_at->toIso8601String(),
                    'channel'  => $reminder->channel,
                    'status'   => $reminder->status,
                ];
            } catch (ValidationException $e) {
                $errors[] = ['index' => $i, 'errors' => $e->errors()];
            }
        }

        $status = empty($created) ? 422 : ($errors ? 207 : 201);

        return response()->json([
            'created_count' => count($created),
            'error_count'   => count($errors),
            'created'       => $created,
            'errors'        => $errors,
        ], $status);
    }
}
