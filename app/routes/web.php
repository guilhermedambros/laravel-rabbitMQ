<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RabbitController;
use App\Http\Controllers\RabbitJsonController;


Route::get('/', function () {
    return view('welcome');
});

// Rotas antigas (string simples)
Route::post('/send-message', [RabbitController::class, 'send']);
Route::get('/receive-message', [RabbitController::class, 'receive']);

// Rotas JSON (objetos/arrays)
Route::post('/json/enviar-email', [RabbitJsonController::class, 'enviarEmail']);
Route::post('/json/processar-pedido', [RabbitJsonController::class, 'processarPedido']);
Route::post('/json/processar-imagem', [RabbitJsonController::class, 'processarImagem']);
Route::post('/json/publicar-evento', [RabbitJsonController::class, 'publicarEvento']);
Route::post('/json/customizado', [RabbitJsonController::class, 'enviarCustomizado']);
Route::get('/json/receber', [RabbitJsonController::class, 'receberJson']);