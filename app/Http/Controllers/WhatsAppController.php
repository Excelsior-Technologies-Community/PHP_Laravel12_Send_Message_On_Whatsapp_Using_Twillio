<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Message;
use App\Models\Template;
use App\Models\IncomingMessage;
use Twilio\Rest\Client;

class WhatsAppController extends Controller
{
    public function index()
    {
        $templates = Template::where('is_active', true)->get();

        return view('whatsapp', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required_without:phones|string',
            'phones' => 'nullable|array',
            'phones.*' => 'string',
            'message' => 'required_without:template_id|string',
            'template_id' => 'nullable|exists:templates,id',
            'media' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        $phones = collect();

        if ($request->filled('phones') && is_array($request->input('phones'))) {
            foreach ($request->input('phones') as $phoneLine) {
                $lines = explode("\n", $phoneLine);
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if ($trimmed !== '') {
                        $phones->push($trimmed);
                    }
                }
            }
        }

        if ($phones->isEmpty() && $request->filled('phone')) {
            $phones->push($request->input('phone'));
        }

        $messageText = $request->input('message');

        if ($request->filled('template_id')) {
            $template = Template::findOrFail($request->template_id);
            $messageText = $template->content;
        }

        $name = $request->input('name', $request->input('phone'));

        $messageText = $this->replaceTemplateVariables($messageText, $name, $request->input('phone'));

        $mediaPath = null;
        if ($request->hasFile('media')) {
            $mediaPath = $request->file('media')->store('media', 'public');
        }

        $sid    = config('services.twilio.sid');
        $token  = config('services.twilio.token');
        $from   = 'whatsapp:' . config('services.twilio.whatsapp_from');

        $client = new Client($sid, $token);

        $results = [];
        $errors = [];

        foreach ($phones as $phone) {
            $phone = trim($phone);
            if (empty($phone)) {
                continue;
            }

            $to = 'whatsapp:+91' . $phone;
            $phoneMessageText = $this->replaceTemplateVariables($messageText, $name, $phone);

            try {
                $messageData = ['body' => $phoneMessageText];

                if ($mediaPath) {
                    $messageData['mediaUrl'] = asset('storage/' . ltrim($mediaPath, '/'));
                }

                $client->messages->create($to, [
                    'from' => $from,
                ] + $messageData);

                Message::create([
                    'user_id' => auth()->id(),
                    'phone' => $phone,
                    'message' => $phoneMessageText,
                    'status' => 'sent',
                    'media_path' => $mediaPath,
                    'sent_at' => now(),
                ]);

                $results[] = ['phone' => $phone, 'status' => 'sent'];
            } catch (\Exception $e) {
                Message::create([
                    'user_id' => auth()->id(),
                    'phone' => $phone,
                    'message' => $phoneMessageText,
                    'status' => 'failed',
                    'media_path' => $mediaPath,
                    'sent_at' => now(),
                ]);

                $results[] = ['phone' => $phone, 'status' => 'failed', 'error' => $e->getMessage()];
                $errors[] = "{$phone}: " . $e->getMessage();
            }
        }

        $failed = collect($results)->where('status', 'failed')->count();
        $success = collect($results)->where('status', 'sent')->count();

        if ($failed === 0) {
            return back()->with('success', "{$success} message(s) sent successfully!");
        }

        $errorMsg = "{$success} sent, {$failed} failed.";
        if (!empty($errors)) {
            $errorMsg .= " Errors: " . implode('; ', $errors);
        }

        return back()->with('error', $errorMsg);
    }

    private function replaceTemplateVariables(string $text, string $name, string $phone): string
    {
        $text = str_replace('{name}', $name, $text);
        $text = str_replace('{phone}', $phone, $text);
        $text = str_replace('{number}', $phone, $text);

        return $text;
    }

    public function history()
    {
        $messages = Message::with('user')
            ->when(auth()->user() && auth()->user()->role !== 'admin', function ($query) {
                return $query->where('user_id', auth()->id());
            })
            ->orderBy('sent_at', 'desc')
            ->paginate(20);

        return view('messages.history', [
            'messages' => $messages,
        ]);
    }

    public function templatesIndex()
    {
        $templates = Template::orderBy('created_at', 'desc')->get();

        return view('templates.index', [
            'templates' => $templates,
        ]);
    }

    public function templatesCreate()
    {
        return view('templates.create');
    }

    public function templatesStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        Template::create($validated);

        return redirect()->route('templates.index')->with('success', 'Template created successfully!');
    }

    public function templatesEdit(Template $template)
    {
        return view('templates.edit', [
            'template' => $template,
        ]);
    }

    public function templatesUpdate(Request $request, Template $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return redirect()->route('templates.index')->with('success', 'Template updated successfully!');
    }

    public function templatesDestroy(Template $template)
    {
        $template->delete();

        return redirect()->route('templates.index')->with('success', 'Template deleted successfully!');
    }

    public function webhook(Request $request)
    {
        $data = $request->all();

        IncomingMessage::create([
            'from' => $data['entry'][0]['changes'][0]['value']['messages'][0]['from'] ?? 'unknown',
            'message' => $data['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'] ?? '',
            'message_type' => $data['entry'][0]['changes'][0]['value']['messages'][0]['type'] ?? 'text',
            'received_at' => now(),
        ]);

        return response()->json(['status' => 'received'], 200);
    }

    public function dashboard()
    {
        $totalSent = Message::where('status', 'sent')->count();
        $totalFailed = Message::where('status', 'failed')->count();
        $todaySent = Message::where('status', 'sent')
            ->whereDate('sent_at', today())
            ->count();
        $todayFailed = Message::where('status', 'failed')
            ->whereDate('sent_at', today())
            ->count();
        $messagesByDay = Message::selectRaw('DATE(sent_at) as date, COUNT(*) as count, SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_count, SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count')
            ->where('sent_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('dashboard', [
            'totalSent' => $totalSent,
            'totalFailed' => $totalFailed,
            'todaySent' => $todaySent,
            'todayFailed' => $todayFailed,
            'messagesByDay' => $messagesByDay,
        ]);
    }
}