<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function index()
    {
        return view('chat');
    }

    public function fetchMessages()
    {
        return Message::latest()->take(50)->get()->reverse()->values();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $message = Message::create($validated);

        try {
            Http::post('http://localhost:3000/broadcast', [
                'username' => $message->username,
                'message' => $message->message,
                'created_at' => $message->created_at->toISOString(), // Ensure consistent format
            ]);
        } catch (\Exception $e) {
            // Log error or fail silently if Node is down
        }

        return response()->json($message);
    }
}
