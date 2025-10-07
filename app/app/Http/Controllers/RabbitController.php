<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RabbitMQService;

class RabbitController extends Controller
{
    public function send(Request $request)
    {
        $message = $request->input('mensagem', 'Mensagem padrão');
        $rabbit = new RabbitMQService();
        $rabbit->sendMessage($message);

        return response()->json(['status' => 'ok', 'message' => $message]);
    }

    public function receive()
    {
        $rabbit = new RabbitMQService();
        
        // Try to get a single message without blocking
        $message = $rabbit->getMessage();
        
        if ($message) {
            return response()->json([
                'status' => 'ok',
                'message' => $message->body,
                'received_at' => now()->toIso8601String()
            ]);
        }
        
        return response()->json([
            'status' => 'no_messages',
            'message' => 'No messages available in queue'
        ], 200);
    }
}
