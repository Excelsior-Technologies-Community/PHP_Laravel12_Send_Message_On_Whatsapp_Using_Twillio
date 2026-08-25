<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Message;
use App\Models\Template;
use App\Models\IncomingMessage;
use Twilio\Rest\Client;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WhatsAppController extends Controller
{

    /**
     * Number of hours during which a successfully sent
     * message to the same phone is considered a duplicate.
     *
     * Example:
     * 24 = same number cannot receive another message
     * within 24 hours.
     */
    private const DUPLICATE_WINDOW_HOURS = 24;

    /**
     * Display WhatsApp message form.
     */
    public function index()
    {
        $templates = Template::where('is_active', true)->get();

        return view('whatsapp', [
            'templates' => $templates,
        ]);
    }

    /**
     * Send WhatsApp messages.
     *
     * Features:
     * - Single message sending
     * - Bulk message sending
     * - Smart Indian phone number normalization
     * - Invalid number detection
     * - Duplicate detection inside current request
     * - Duplicate detection against recent sent messages
     * - Template support
     * - Media support
     * - Message history
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required_without:phones|nullable|string',
            'phones' => 'nullable|array',
            'phones.*' => 'nullable|string',
            'message' => 'required_without:template_id|nullable|string',
            'template_id' => 'nullable|exists:templates,id',
            'media' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            'name' => 'nullable|string|max:255',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Collect raw phone numbers
        |--------------------------------------------------------------------------
        */

        $rawPhones = collect();

        if (
            $request->filled('phones') &&
            is_array($request->input('phones'))
        ) {
            foreach ($request->input('phones') as $phoneLine) {
                $lines = explode("\n", $phoneLine);

                foreach ($lines as $line) {
                    $trimmed = trim($line);

                    if ($trimmed !== '') {
                        $rawPhones->push($trimmed);
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Add single phone number when bulk list is empty
        |--------------------------------------------------------------------------
        */

        if (
            $rawPhones->isEmpty() &&
            $request->filled('phone')
        ) {
            $rawPhones->push(
                trim($request->input('phone'))
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Smart phone number validation
        |--------------------------------------------------------------------------
        */

        $validPhones = collect();
        $invalidPhones = collect();
        $duplicatePhones = collect();

        foreach ($rawPhones as $rawPhone) {

            $normalizedPhone = $this->normalizeIndianPhone(
                $rawPhone
            );

            /*
            |--------------------------------------------------------------------------
            | Invalid number
            |--------------------------------------------------------------------------
            */

            if ($normalizedPhone === null) {
                $invalidPhones->push($rawPhone);

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Duplicate inside current request
            |--------------------------------------------------------------------------
            */

            if ($validPhones->contains($normalizedPhone)) {
                $duplicatePhones->push(
                    $normalizedPhone
                );

                continue;
            }

            $validPhones->push($normalizedPhone);
        }

        /*
        |--------------------------------------------------------------------------
        | Stop when no valid numbers are available
        |--------------------------------------------------------------------------
        */

        if ($validPhones->isEmpty()) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'No valid Indian WhatsApp phone numbers were found.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Prepare message
        |--------------------------------------------------------------------------
        */

        $messageText = $request->input('message');

        if ($request->filled('template_id')) {
            $template = Template::findOrFail(
                $request->template_id
            );

            $messageText = $template->content;
        }

        /*
        |--------------------------------------------------------------------------
        | Name
        |--------------------------------------------------------------------------
        */

        $name = $request->input('name');

        if (empty($name)) {
            $name = 'User';
        }

        /*
        |--------------------------------------------------------------------------
        | Replace template variables
        |--------------------------------------------------------------------------
        */

        $messageText = $this->replaceTemplateVariables(
            $messageText,
            $name,
            $validPhones->first()
        );

        /*
        |--------------------------------------------------------------------------
        | Store uploaded media
        |--------------------------------------------------------------------------
        */

        $mediaPath = null;

        if ($request->hasFile('media')) {
            $mediaPath = $request
                ->file('media')
                ->store('media', 'public');
        }

        /*
        |--------------------------------------------------------------------------
        | Remove numbers already successfully sent recently
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Only "sent" messages are considered duplicates.
        |
        | Failed messages are NOT removed.
        |
        | This means the Retry functionality still works.
        |
        */

        $duplicateSince = now()->subHours(
            self::DUPLICATE_WINDOW_HOURS
        );

        $recentlySentPhones = Message::query()
            ->where('status', 'sent')
            ->where('sent_at', '>=', $duplicateSince)
            ->whereIn('phone', $validPhones)
            ->pluck('phone')
            ->unique();

        if ($recentlySentPhones->isNotEmpty()) {

            foreach ($recentlySentPhones as $phone) {

                $duplicatePhones->push($phone);

                $validPhones = $validPhones->reject(
                    fn($validPhone) =>
                    $validPhone === $phone
                );
            }

            $validPhones = $validPhones->values();
        }

        /*
        |--------------------------------------------------------------------------
        | If every number was duplicate
        |--------------------------------------------------------------------------
        */

        if ($validPhones->isEmpty()) {

            $summary =
                '0 message(s) sent. ' .
                $duplicatePhones->unique()->count() .
                ' duplicate number(s) skipped.';

            return back()
                ->withInput()
                ->with('warning', $summary);
        }

        /*
        |--------------------------------------------------------------------------
        | Twilio configuration
        |--------------------------------------------------------------------------
        */

        $sid = config('services.twilio.sid');

        $token = config('services.twilio.token');

        $from =
            'whatsapp:' .
            config('services.twilio.whatsapp_from');

        /*
        |--------------------------------------------------------------------------
        | Validate Twilio configuration
        |--------------------------------------------------------------------------
        */

        if (
            empty($sid) ||
            empty($token) ||
            empty(config('services.twilio.whatsapp_from'))
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Twilio configuration is missing. Please check your .env file.'
                );
        }

        $client = new Client(
            $sid,
            $token
        );

        $results = [];

        $errors = [];

        /*
        |--------------------------------------------------------------------------
        | Send messages
        |--------------------------------------------------------------------------
        */

        foreach ($validPhones as $phone) {

            $phoneMessageText =
                $this->replaceTemplateVariables(
                    $messageText,
                    $name,
                    $phone
                );

            $to = 'whatsapp:+91' . $phone;

            try {

                $messageData = [
                    'body' => $phoneMessageText,
                ];

                /*
                |--------------------------------------------------------------------------
                | Attach media
                |--------------------------------------------------------------------------
                */

                if ($mediaPath) {

                    $messageData['mediaUrl'] = [
                        asset(
                            'storage/' .
                                ltrim($mediaPath, '/')
                        ),
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | Send through Twilio
                |--------------------------------------------------------------------------
                */

                $client->messages->create(
                    $to,
                    [
                        'from' => $from,
                    ] + $messageData
                );

                /*
                |--------------------------------------------------------------------------
                | Store successful message
                |--------------------------------------------------------------------------
                */

                Message::create([
                    'user_id' => auth()->id(),
                    'phone' => $phone,
                    'message' => $phoneMessageText,
                    'status' => 'sent',
                    'media_path' => $mediaPath,
                    'sent_at' => now(),
                ]);

                $results[] = [
                    'phone' => $phone,
                    'status' => 'sent',
                ];
            } catch (\Exception $e) {

                /*
                |--------------------------------------------------------------------------
                | Store failed message
                |--------------------------------------------------------------------------
                */

                Message::create([
                    'user_id' => auth()->id(),
                    'phone' => $phone,
                    'message' => $phoneMessageText,
                    'status' => 'failed',
                    'media_path' => $mediaPath,
                    'sent_at' => now(),
                ]);

                $results[] = [
                    'phone' => $phone,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];

                $errors[] =
                    "{$phone}: " .
                    $e->getMessage();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Sending statistics
        |--------------------------------------------------------------------------
        */

        $failed = collect($results)
            ->where('status', 'failed')
            ->count();

        $success = collect($results)
            ->where('status', 'sent')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Build summary
        |--------------------------------------------------------------------------
        */

        $summary =
            "{$success} message(s) sent successfully.";

        if ($failed > 0) {

            $summary .=
                " {$failed} message(s) failed.";
        }

        if ($invalidPhones->isNotEmpty()) {

            $summary .=
                " " .
                $invalidPhones->count() .
                " invalid number(s) skipped.";
        }

        if ($duplicatePhones->isNotEmpty()) {

            $summary .=
                " " .
                $duplicatePhones
                ->unique()
                ->count() .
                " duplicate number(s) skipped.";
        }

        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        if ($failed === 0) {

            return back()
                ->with('success', $summary);
        }

        /*
        |--------------------------------------------------------------------------
        | Failed
        |--------------------------------------------------------------------------
        */

        $errorMsg = $summary;

        if (!empty($errors)) {

            $errorMsg .=
                ' Errors: ' .
                implode('; ', $errors);
        }

        return back()
            ->with('error', $errorMsg);
    }

    /**
     * Normalize and validate Indian phone numbers.
     */
    private function normalizeIndianPhone(
        string $phone
    ): ?string {

        /*
        |--------------------------------------------------------------------------
        | Remove formatting
        |--------------------------------------------------------------------------
        */

        $phone = preg_replace(
            '/[\s\-\(\)]/',
            '',
            trim($phone)
        );

        if (!$phone) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Remove +
        |--------------------------------------------------------------------------
        */

        $phone = ltrim(
            $phone,
            '+'
        );

        /*
        |--------------------------------------------------------------------------
        | Remove Indian country code
        |--------------------------------------------------------------------------
        */

        if (
            str_starts_with($phone, '91') &&
            strlen($phone) === 12
        ) {
            $phone = substr(
                $phone,
                2
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Indian mobile number
        |--------------------------------------------------------------------------
        */

        if (
            !preg_match(
                '/^[6-9][0-9]{9}$/',
                $phone
            )
        ) {
            return null;
        }

        return $phone;
    }

    /**
     * Replace template variables.
     */
    private function replaceTemplateVariables(
        ?string $text,
        string $name,
        string $phone
    ): string {

        $text = $text ?? '';

        $text = str_replace(
            '{name}',
            $name,
            $text
        );

        $text = str_replace(
            '{phone}',
            $phone,
            $text
        );

        $text = str_replace(
            '{number}',
            $phone,
            $text
        );

        return $text;
    }

    /**
     * Display message history.
     */
    /**
     * Display message history.
     *
     * Features:
     * - Search by phone/message
     * - Status filter
     * - Date range filter
     * - Dynamic pagination
     */
    public function history(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:sent,failed',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'nullable|integer|in:5,10,20,50,100',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 5);

        $query = Message::with('user');

        /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

        if (
            auth()->user() &&
            auth()->user()->role !== 'admin'
        ) {
            $query->where(
                'user_id',
                auth()->id()
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

        if (!empty($validated['search'])) {

            $search = $validated['search'];

            $query->where(function ($q) use ($search) {

                $q->where(
                    'phone',
                    'like',
                    '%' . $search . '%'
                )
                    ->orWhere(
                        'message',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }

        /*
    |--------------------------------------------------------------------------
    | Status Filter
    |--------------------------------------------------------------------------
    */

        if (!empty($validated['status'])) {

            $query->where(
                'status',
                $validated['status']
            );
        }

        /*
    |--------------------------------------------------------------------------
    | From Date
    |--------------------------------------------------------------------------
    */

        if (!empty($validated['from_date'])) {

            $query->whereDate(
                'sent_at',
                '>=',
                $validated['from_date']
            );
        }

        /*
    |--------------------------------------------------------------------------
    | To Date
    |--------------------------------------------------------------------------
    */

        if (!empty($validated['to_date'])) {

            $query->whereDate(
                'sent_at',
                '<=',
                $validated['to_date']
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

        $messages = $query
            ->orderBy(
                'sent_at',
                'desc'
            )
            ->paginate($perPage)
            ->withQueryString();

        return view(
            'messages.history',
            [
                'messages' => $messages,
            ]
        );
    }

    /**
     * Retry a failed WhatsApp message.
     */
    public function retry(
        Message $message
    ) {

        /*
        |--------------------------------------------------------------------------
        | Authorization
        |--------------------------------------------------------------------------
        */

        if (
            auth()->user()->role !== 'admin' &&
            $message->user_id !== auth()->id()
        ) {
            abort(
                403,
                'You are not authorized to retry this message.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Only failed messages can be retried
        |--------------------------------------------------------------------------
        */

        if ($message->status !== 'failed') {

            return back()->with(
                'error',
                'Only failed messages can be retried.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate phone
        |--------------------------------------------------------------------------
        */

        $phone = $this->normalizeIndianPhone(
            $message->phone
        );

        if (!$phone) {

            return back()->with(
                'error',
                'The phone number stored for this message is invalid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Twilio configuration
        |--------------------------------------------------------------------------
        */

        $sid = config('services.twilio.sid');

        $token = config('services.twilio.token');

        $from =
            'whatsapp:' .
            config('services.twilio.whatsapp_from');

        try {

            $client = new Client(
                $sid,
                $token
            );

            $messageData = [
                'body' => $message->message,
            ];

            /*
            |--------------------------------------------------------------------------
            | Reuse media
            |--------------------------------------------------------------------------
            */

            if ($message->media_path) {

                $messageData['mediaUrl'] = [
                    asset(
                        'storage/' .
                            ltrim(
                                $message->media_path,
                                '/'
                            )
                    ),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Send retry
            |--------------------------------------------------------------------------
            */

            $client->messages->create(
                'whatsapp:+91' . $phone,
                [
                    'from' => $from,
                ] + $messageData
            );

            /*
            |--------------------------------------------------------------------------
            | Update existing failed record
            |--------------------------------------------------------------------------
            */

            $message->update([
                'phone' => $phone,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return back()->with(
                'success',
                "WhatsApp message to {$phone} was successfully retried."
            );
        } catch (\Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | Keep failed
            |--------------------------------------------------------------------------
            */

            $message->update([
                'status' => 'failed',
                'sent_at' => now(),
            ]);

            return back()->with(
                'error',
                "Retry failed for {$phone}: " .
                    $e->getMessage()
            );
        }
    }

    /**
     * Display templates.
     */
    public function templatesIndex()
    {
        $templates = Template::orderBy(
            'created_at',
            'desc'
        )->get();

        return view(
            'templates.index',
            [
                'templates' => $templates,
            ]
        );
    }

    /**
     * Display create template form.
     */
    public function templatesCreate()
    {
        return view('templates.create');
    }

    /**
     * Store template.
     */
    public function templatesStore(
        Request $request
    ) {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'variables' => 'nullable',
            'is_active' => 'boolean',
        ]);

        if ($request->filled('variables')) {

            $validated['variables'] =
                collect(
                    explode(
                        ',',
                        $request->input('variables')
                    )
                )
                ->map(
                    fn($variable) =>
                    trim($variable)
                )
                ->filter()
                ->values()
                ->toArray();
        } else {

            $validated['variables'] = [];
        }

        $validated['is_active'] =
            $request->boolean('is_active');

        Template::create($validated);

        return redirect()
            ->route('templates.index')
            ->with(
                'success',
                'Template created successfully!'
            );
    }

    /**
     * Edit template.
     */
    public function templatesEdit(
        Template $template
    ) {

        return view(
            'templates.edit',
            [
                'template' => $template,
            ]
        );
    }

    /**
     * Update template.
     */
    public function templatesUpdate(
        Request $request,
        Template $template
    ) {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'variables' => 'nullable',
            'is_active' => 'boolean',
        ]);

        if ($request->filled('variables')) {

            $validated['variables'] =
                collect(
                    explode(
                        ',',
                        $request->input('variables')
                    )
                )
                ->map(
                    fn($variable) =>
                    trim($variable)
                )
                ->filter()
                ->values()
                ->toArray();
        } else {

            $validated['variables'] = [];
        }

        $validated['is_active'] =
            $request->boolean('is_active');

        $template->update($validated);

        return redirect()
            ->route('templates.index')
            ->with(
                'success',
                'Template updated successfully!'
            );
    }

    /**
     * Delete template.
     */
    public function templatesDestroy(
        Template $template
    ) {

        $template->delete();

        return redirect()
            ->route('templates.index')
            ->with(
                'success',
                'Template deleted successfully!'
            );
    }

    /**
     * WhatsApp webhook.
     */
    public function webhook(
        Request $request
    ) {

        $data = $request->all();

        IncomingMessage::create([
            'from' =>
            $data['entry'][0]['changes'][0]['value']['messages'][0]['from']
                ?? 'unknown',

            'message' =>
            $data['entry'][0]['changes'][0]['value']['messages'][0]['text']['body']
                ?? '',

            'message_type' =>
            $data['entry'][0]['changes'][0]['value']['messages'][0]['type']
                ?? 'text',

            'received_at' => now(),
        ]);

        return response()->json([
            'status' => 'received',
        ], 200);
    }

    /**
     * Dashboard.
     */
    public function dashboard()
    {
        $totalSent = Message::where(
            'status',
            'sent'
        )->count();

        $totalFailed = Message::where(
            'status',
            'failed'
        )->count();

        $todaySent = Message::where(
            'status',
            'sent'
        )
            ->whereDate(
                'sent_at',
                today()
            )
            ->count();

        $todayFailed = Message::where(
            'status',
            'failed'
        )
            ->whereDate(
                'sent_at',
                today()
            )
            ->count();

        $messagesByDay = Message::selectRaw(
            'DATE(sent_at) as date,
            COUNT(*) as count,
            SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_count,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count'
        )
            ->where(
                'sent_at',
                '>=',
                now()->subDays(30)
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view(
            'dashboard',
            [
                'totalSent' => $totalSent,
                'totalFailed' => $totalFailed,
                'todaySent' => $todaySent,
                'todayFailed' => $todayFailed,
                'messagesByDay' => $messagesByDay,
            ]
        );
    }


    /**
     * Display a single WhatsApp message.
     */
    public function show(Message $message)
    {
        /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

        if (
            auth()->user()->role !== 'admin' &&
            $message->user_id !== auth()->id()
        ) {
            abort(
                403,
                'You are not authorized to view this message.'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Load related user
    |--------------------------------------------------------------------------
    */

        $message->load('user');

        return view(
            'messages.show',
            [
                'message' => $message,
            ]
        );
    }

    /**
     * Delete a single message.
     */
    public function destroy(Message $message)
    {
        /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

        if (
            auth()->user()->role !== 'admin' &&
            $message->user_id !== auth()->id()
        ) {
            abort(
                403,
                'You are not authorized to delete this message.'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Delete attached media
    |--------------------------------------------------------------------------
    */

        if ($message->media_path) {

            Storage::disk('public')->delete(
                $message->media_path
            );
        }

        $message->delete();

        return back()->with(
            'success',
            'Message deleted successfully.'
        );
    }

    /**
     * Delete multiple messages.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'message_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'message_ids.*' => [
                'integer',
                'exists:messages,id',
            ],
        ]);

        /*
    |--------------------------------------------------------------------------
    | Find selected messages
    |--------------------------------------------------------------------------
    */

        $query = Message::whereIn(
            'id',
            $validated['message_ids']
        );

        /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

        if (
            auth()->user()->role !== 'admin'
        ) {

            $query->where(
                'user_id',
                auth()->id()
            );
        }

        $messages = $query->get();

        if ($messages->isEmpty()) {

            return back()->with(
                'error',
                'No authorized messages were selected.'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Delete media
    |--------------------------------------------------------------------------
    */

        foreach ($messages as $message) {

            if ($message->media_path) {

                Storage::disk('public')->delete(
                    $message->media_path
                );
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Delete records
    |--------------------------------------------------------------------------
    */

        $count = $messages->count();

        foreach ($messages as $message) {
            $message->delete();
        }

        return back()->with(
            'success',
            "{$count} message(s) deleted successfully."
        );
    }

    /**
     * Export message history to CSV.
     *
     * Current filters are preserved.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',

            'status' => [
                'nullable',
                'in:sent,failed',
            ],

            'from_date' => [
                'nullable',
                'date',
            ],

            'to_date' => [
                'nullable',
                'date',
                'after_or_equal:from_date',
            ],
        ]);

        $query = Message::with('user');

        /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

        if (
            auth()->user()->role !== 'admin'
        ) {

            $query->where(
                'user_id',
                auth()->id()
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

        if (!empty($validated['search'])) {

            $search = $validated['search'];

            $query->where(function ($q) use ($search) {

                $q->where(
                    'phone',
                    'like',
                    '%' . $search . '%'
                )
                    ->orWhere(
                        'message',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }

        /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

        if (!empty($validated['status'])) {

            $query->where(
                'status',
                $validated['status']
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Date range
    |--------------------------------------------------------------------------
    */

        if (!empty($validated['from_date'])) {

            $query->whereDate(
                'sent_at',
                '>=',
                $validated['from_date']
            );
        }

        if (!empty($validated['to_date'])) {

            $query->whereDate(
                'sent_at',
                '<=',
                $validated['to_date']
            );
        }

        $messages = $query
            ->orderBy(
                'sent_at',
                'desc'
            )
            ->get();

        $fileName =
            'whatsapp-messages-' .
            now()->format('Y-m-d-H-i-s') .
            '.csv';

        return response()->streamDownload(
            function () use ($messages) {

                $handle = fopen(
                    'php://output',
                    'w'
                );

                /*
            |----------------------------------------------------------------------
            | CSV Header
            |----------------------------------------------------------------------
            */

                fputcsv(
                    $handle,
                    [
                        'ID',
                        'Date',
                        'Phone',
                        'Message',
                        'Status',
                        'Media',
                        'User',
                    ]
                );

                /*
            |----------------------------------------------------------------------
            | CSV Rows
            |----------------------------------------------------------------------
            */

                foreach ($messages as $message) {

                    fputcsv(
                        $handle,
                        [
                            $message->id,

                            $message->sent_at
                                ? $message->sent_at->format(
                                    'Y-m-d H:i:s'
                                )
                                : '',

                            '+91 ' . $message->phone,

                            $message->message,

                            ucfirst(
                                $message->status
                            ),

                            $message->media_path
                                ? 'Yes'
                                : 'No',

                            $message->user
                                ? $message->user->name
                                : 'N/A',
                        ]
                    );
                }

                fclose($handle);
            },
            $fileName,
            [
                'Content-Type' =>
                'text/csv; charset=UTF-8',
            ]
        );
    }
}
