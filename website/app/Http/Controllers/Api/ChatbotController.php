<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    public function kirim(Request $request, ChatbotService $bot)
    {
        if ($request->user()->role !== 'owner') {
            return response()->json(['message' => 'Hanya Owner yang dapat memakai chatbot'], 403);
        }

        $data = $request->validate([
            'pesan' => 'required|string|max:1000',
            'riwayat' => 'nullable|array|max:20',
            'riwayat.*.role' => 'required_with:riwayat|in:user,bot',
            'riwayat.*.text' => 'required_with:riwayat|string|max:2000',
        ]);

        try {
            $jawaban = $bot->tanya($data['pesan'], $data['riwayat'] ?? []);
        } catch (\Throwable $e) {
            Log::error('Chatbot error: ' . $e->getMessage());
            return response()->json(['message' => 'Chatbot sedang tidak tersedia'], 502);
        }

        return response()->json(['jawaban' => $jawaban]);
    }
}