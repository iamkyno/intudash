<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ReminderTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReminderTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = ReminderTemplate::with('client')->latest();
        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }
        $templates = $query->paginate(20)->withQueryString();
        $clients = Client::active()->pluck('company_name', 'id');

        return view('reminders.templates.index', compact('templates', 'clients'));
    }

    public function create()
    {
        $clients = Client::active()->get();
        return view('reminders.templates.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateTemplate($request);
        $validated['slug'] = $this->resolveSlug($validated['client_id'], $validated['slug'] ?: $validated['name']);

        $template = ReminderTemplate::create($validated);

        return redirect()->route('reminder-templates.index')
            ->with('success', "Template '{$template->name}' created with slug '{$template->slug}'.");
    }

    public function edit(ReminderTemplate $reminderTemplate)
    {
        $clients = Client::active()->get();
        return view('reminders.templates.edit', ['template' => $reminderTemplate, 'clients' => $clients]);
    }

    public function update(Request $request, ReminderTemplate $reminderTemplate)
    {
        $validated = $this->validateTemplate($request, $reminderTemplate);
        $validated['slug'] = $this->resolveSlug($validated['client_id'], $validated['slug'] ?: $validated['name'], $reminderTemplate->id);

        $reminderTemplate->update($validated);

        return redirect()->route('reminder-templates.index')
            ->with('success', 'Template updated.');
    }

    public function destroy(ReminderTemplate $reminderTemplate)
    {
        $reminderTemplate->delete();
        return back()->with('success', 'Template deleted.');
    }

    private function validateTemplate(Request $request, ?ReminderTemplate $existing = null): array
    {
        return $request->validate([
            'client_id'          => 'required|exists:clients,id',
            'name'               => 'required|string|max:255',
            'slug'               => 'nullable|string|max:255|alpha_dash',
            'channel'            => 'required|in:sms,email,both',
            'sms_body'           => 'required_if:channel,sms|required_if:channel,both|nullable|string',
            'sender_name'        => 'nullable|string|max:11',
            'email_subject'      => 'required_if:channel,email|required_if:channel,both|nullable|string|max:255',
            'email_from_name'    => 'nullable|string|max:255',
            'email_from_address' => 'nullable|email|max:255',
            'email_reply_to'     => 'nullable|email|max:255',
            'email_body'         => 'required_if:channel,email|required_if:channel,both|nullable|string',
            'active'             => 'boolean',
        ]);
    }

    private function resolveSlug(int $clientId, string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base);
        $original = $slug;
        $i = 2;

        while (
            ReminderTemplate::where('client_id', $clientId)
                ->where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$original}-{$i}";
            $i++;
        }

        return $slug;
    }
}
