<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RabbitMQJsonService;

class RabbitJsonController extends Controller
{
    /**
     * Envia tarefa de envio de email
     */
    public function enviarEmail(Request $request)
    {
        $rabbit = new RabbitMQJsonService();
        
        $rabbit->sendTask('enviar_email', [
            'destinatario' => $request->input('email', 'usuario@example.com'),
            'assunto' => $request->input('assunto', 'Bem-vindo!'),
            'corpo' => $request->input('corpo', 'Obrigado por se cadastrar!'),
            'template' => 'welcome'
        ], 5);
        
        return response()->json([
            'status' => 'ok',
            'mensagem' => 'Tarefa de email enviada para fila'
        ]);
    }

    /**
     * Envia tarefa de processamento de pedido
     */
    public function processarPedido(Request $request)
    {
        $rabbit = new RabbitMQJsonService();
        
        $rabbit->sendTask('processar_pedido', [
            'pedido_id' => $request->input('pedido_id', rand(1000, 9999)),
            'cliente' => [
                'nome' => $request->input('nome', 'João Silva'),
                'email' => $request->input('email', 'joao@example.com'),
                'cpf' => $request->input('cpf', '123.456.789-00')
            ],
            'items' => [
                ['produto' => 'Notebook', 'quantidade' => 1, 'preco' => 2999.90],
                ['produto' => 'Mouse', 'quantidade' => 1, 'preco' => 49.90]
            ],
            'total' => 3049.80,
            'pagamento' => [
                'metodo' => 'cartao_credito',
                'parcelas' => 3
            ]
        ], 8); // Alta prioridade
        
        return response()->json([
            'status' => 'ok',
            'mensagem' => 'Pedido enviado para processamento'
        ]);
    }

    /**
     * Envia tarefa de processamento de imagem
     */
    public function processarImagem(Request $request)
    {
        $rabbit = new RabbitMQJsonService();
        
        $rabbit->sendTask('processar_imagem', [
            'arquivo' => $request->input('arquivo', 'foto.jpg'),
            'operacao' => $request->input('operacao', 'redimensionar'),
            'largura' => $request->input('largura', 800),
            'altura' => $request->input('altura', 600),
            'qualidade' => $request->input('qualidade', 85),
            'formato_saida' => 'webp'
        ], 3);
        
        return response()->json([
            'status' => 'ok',
            'mensagem' => 'Imagem enviada para processamento'
        ]);
    }

    /**
     * Envia evento (Pub/Sub)
     */
    public function publicarEvento(Request $request)
    {
        $rabbit = new RabbitMQJsonService();
        
        $evento = $request->input('evento', 'pedido.criado');
        
        $payloads = [
            'pedido.criado' => [
                'pedido_id' => rand(1000, 9999),
                'cliente' => 'Maria Santos',
                'valor' => 299.90,
                'status' => 'aguardando_pagamento'
            ],
            'usuario.cadastrado' => [
                'usuario_id' => rand(100, 999),
                'nome' => 'Carlos Oliveira',
                'email' => 'carlos@example.com',
                'plano' => 'premium'
            ],
            'pagamento.aprovado' => [
                'transacao_id' => uniqid('txn_'),
                'valor' => 199.90,
                'metodo' => 'pix'
            ]
        ];
        
        $payload = $payloads[$evento] ?? ['mensagem' => 'Evento genérico'];
        
        $rabbit->sendEvent($evento, $payload);
        
        return response()->json([
            'status' => 'ok',
            'evento' => $evento,
            'payload' => $payload
        ]);
    }

    /**
     * Envia dados customizados
     */
    public function enviarCustomizado(Request $request)
    {
        $rabbit = new RabbitMQJsonService();
        
        $dados = $request->all();
        
        $rabbit->sendJson($dados);
        
        return response()->json([
            'status' => 'ok',
            'mensagem' => 'Dados customizados enviados',
            'dados' => $dados
        ]);
    }

    /**
     * Recebe mensagem JSON
     */
    public function receberJson()
    {
        $rabbit = new RabbitMQJsonService();
        
        $data = $rabbit->getJson();
        
        if ($data) {
            return response()->json([
                'status' => 'ok',
                'data' => $data
            ]);
        }
        
        return response()->json([
            'status' => 'no_messages',
            'mensagem' => 'Nenhuma mensagem na fila'
        ]);
    }
}
