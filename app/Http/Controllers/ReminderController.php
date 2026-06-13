<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Reminder;
use App\Services\ReminderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader;

class ReminderController extends Controller
{
    public function __construct(private ReminderService $reminderService) {}

    public function index(Request $request)
    {
        $query = Reminder::with('client')->latest('send_at');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $reminders = $query->paginate(30)->withQueryString();
        $clients = Client::active()->pluck('company_name', 'id');

        return view('reminders.index', compact('reminders', 'clients'));
    }

    public function create()
    {
        $clients = Client::active()->with('reminderTemplates')->get();
        return view('reminders.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'template'  => 'required|string',
            'send_at'   => 'required|date',
            'name'      => 'nullable|string|max:255',
            'phone'     => 'nullable|string|max:30',
            'email'     => 'nullable|email|max:255',
        ]);

        $client = Client::findOrFail($validated['client_id']);

        try {
            $this->reminderService->create($client, $validated, 'manual');
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('reminders.index')->with('success', 'Reminder scheduled.');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'csv_file'  => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $client = Client::findOrFail($request->client_id);

        $csv = Reader::createFromPath($request->file('csv_file')->getPathname(), 'r');
        $csv->setHeaderOffset(0);

        $created = 0;
        $errors = [];
        $line = 1;

        DB::transaction(function () use ($csv, $client, &$created, &$errors, &$line) {
            foreach ($csv->getRecords() as $record) {
                $line++;

                // Collect any extra columns as merge data.
                $known = ['template', 'send_at', 'name', 'phone', 'email'];
                $data = [];
                foreach ($record as $key => $value) {
                    if (!in_array(strtolower($key), $known) && $value !== '') {
                        $data[$key] = $value;
                    }
                }

                $row = [
                    'template' => $record['template'] ?? $record['Template'] ?? '',
                    'send_at'  => $record['send_at'] ?? $record['Send At'] ?? $record['send_date'] ?? '',
                    'name'     => $record['name'] ?? $record['Name'] ?? null,
                    'phone'    => $record['phone'] ?? $record['Phone'] ?? null,
                    'email'    => $record['email'] ?? $record['Email'] ?? null,
                    'data'     => $data ?: null,
                ];

                try {
                    $this->reminderService->create($client, $row, 'csv');
                    $created++;
                } catch (ValidationException $e) {
                    $errors[] = "Row {$line}: " . implode(' ', array_merge(...array_values($e->errors())));
                }
            }
        });

        $msg = "Imported {$created} reminder(s).";
        if ($errors) {
            $msg .= ' ' . count($errors) . ' row(s) skipped.';
            return redirect()->route('reminders.index')->with('success', $msg)
                ->with('import_errors', array_slice($errors, 0, 20));
        }

        return redirect()->route('reminders.index')->with('success', $msg);
    }

    public function cancel(Reminder $reminder)
    {
        abort_if($reminder->status !== 'pending', 403, 'Only pending reminders can be cancelled.');
        $reminder->update(['status' => 'cancelled']);
        return back()->with('success', 'Reminder cancelled.');
    }

    public function destroy(Reminder $reminder)
    {
        $reminder->delete();
        return back()->with('success', 'Reminder deleted.');
    }
}
