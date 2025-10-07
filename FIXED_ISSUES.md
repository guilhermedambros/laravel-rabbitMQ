# 🔧 Problemas Corrigidos - Documentação Técnica

Este documento registra todos os problemas encontrados durante o desenvolvimento e suas soluções.

## 📋 Índice

- [Erro: bcmod() undefined function](#erro-bcmod-undefined-function)
- [Erro: is_consuming() não existe](#erro-is_consuming-não-existe)
- [Timeout em endpoints HTTP](#timeout-em-endpoints-http)
- [Comandos curl no Windows PowerShell](#comandos-curl-no-windows-powershell)
- [Workers com timeout exception](#workers-com-timeout-exception)

---

## ❌ Erro: bcmod() undefined function

### Sintoma

```
PHP Fatal error: Uncaught Error: Call to undefined function PhpAmqpLib\Wire\bcmod()
```

### Causa Raiz

A biblioteca `php-amqplib` requer a extensão `bcmath` do PHP para operações matemáticas de precisão arbitrária. Além disso, o código tenta chamar `bcmod()` dentro do namespace `PhpAmqpLib\Wire\`, mas a função nativa do PHP não existe neste namespace.

### Solução

**1. Instalar extensão bcmath no Dockerfile:**

```dockerfile
RUN docker-php-ext-install zip pdo pdo_mysql bcmath
```

**2. Criar arquivo de compatibilidade de namespace:**

Arquivo: `app/Support/phpamqp_compat.php`

```php
<?php

namespace PhpAmqpLib\Wire;

if (!function_exists('PhpAmqpLib\Wire\bcmod')) {
    function bcmod($x, $mod)
    {
        return \bcmod($x, $mod);
    }
}

if (!function_exists('PhpAmqpLib\Wire\bcdiv')) {
    function bcdiv($left, $right, $scale = 0)
    {
        return \bcdiv($left, $right, $scale);
    }
}
```

**3. Autoload do arquivo no composer.json:**

```json
{
    "autoload": {
        "files": [
            "app/Support/phpamqp_compat.php"
        ]
    }
}
```

**4. Regenerar autoload:**

```powershell
docker-compose exec laravel_app composer dump-autoload -o
```

### Resultado

✅ Extensão bcmath instalada  
✅ Funções disponíveis no namespace correto  
✅ php-amqplib funciona perfeitamente  

---

## ❌ Erro: is_consuming() não existe

### Sintoma

```
PHP Fatal error: Call to undefined method PhpAmqpLib\Channel\AMQPChannel::is_consuming()
```

### Causa Raiz

A biblioteca `php-amqplib` v2.x não possui o método `is_consuming()`. Este método existe apenas em versões mais recentes (v3.x+).

### Solução

Substituir a verificação `is_consuming()` por uma verificação do array `callbacks`:

**Antes:**
```php
while ($this->channel->is_consuming()) {
    $this->channel->wait();
}
```

**Depois:**
```php
while (count($this->channel->callbacks)) {
    $this->channel->wait();
}
```

### Explicação

O array `$channel->callbacks` contém todos os consumers ativos. Se estiver vazio, não há consumers rodando.

### Resultado

✅ Código compatível com php-amqplib 2.x  
✅ Verificação funcional de consumers ativos  

---

## ❌ Timeout em endpoints HTTP

### Sintoma

Ao acessar endpoint `/receive-message`, a requisição HTTP fica travada indefinidamente e eventualmente retorna timeout (30-60 segundos).

### Causa Raiz

O método `consumeMessages()` é **bloqueante** - ele entra em um loop infinito aguardando mensagens. Isso é ideal para workers CLI, mas fatal para endpoints HTTP que precisam responder rapidamente.

**Código problemático:**
```php
public function receive()
{
    $rabbit = new RabbitMQService();
    
    // Isso BLOQUEIA a requisição!
    $rabbit->consumeMessages(function($msg) {
        return $msg->body;
    });
}
```

### Solução

Criar método **não-bloqueante** que verifica mensagens e retorna imediatamente:

**Método novo no RabbitMQService:**
```php
public function getMessage()
{
    $connection = new AMQPStreamConnection(
        env('RABBITMQ_HOST'),
        env('RABBITMQ_PORT'),
        env('RABBITMQ_USER'),
        env('RABBITMQ_PASSWORD')
    );
    
    $channel = $connection->channel();
    $channel->queue_declare($this->queue, false, true, false, false);
    
    // basic_get NÃO bloqueia - retorna null se não houver mensagem
    $message = $channel->basic_get($this->queue);
    
    if ($message) {
        $channel->basic_ack($message->getDeliveryTag());
        $body = $message->body;
        
        $channel->close();
        $connection->close();
        
        return $body;
    }
    
    $channel->close();
    $connection->close();
    
    return null;
}
```

**Controller atualizado:**
```php
public function receive()
{
    $rabbit = new RabbitMQService();
    $message = $rabbit->getMessage();
    
    if ($message) {
        return response()->json([
            'status' => 'ok',
            'message' => $message,
            'received_at' => now()
        ]);
    }
    
    return response()->json([
        'status' => 'no_messages',
        'message' => 'No messages available in queue'
    ]);
}
```

### Diferença: basic_consume vs basic_get

| Método | Comportamento | Uso |
|--------|---------------|-----|
| `basic_consume()` | **Bloqueante** - aguarda mensagens indefinidamente | Workers CLI, processamento contínuo |
| `basic_get()` | **Não-bloqueante** - retorna imediatamente | HTTP endpoints, verificações pontuais |

### Resultado

✅ Endpoints HTTP respondem instantaneamente  
✅ Zero timeouts  
✅ `consumeMessages()` ainda disponível para workers CLI  

---

## ❌ Comandos curl no Windows PowerShell

### Sintoma

Comandos curl falham no Windows PowerShell com erros estranhos:

```powershell
curl -X POST http://localhost:8000/send-message -d "mensagem=teste"
# Erro: Invoke-WebRequest : Cannot bind parameter 'Headers'...
```

### Causa Raiz

No Windows PowerShell, `curl` é um **alias** para `Invoke-WebRequest`, que tem sintaxe completamente diferente do curl Unix/Linux.

### Solução

**Opção 1: Executar curl dentro do container (RECOMENDADO)**

```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=teste'"
```

**Opção 2: Usar Invoke-WebRequest (sintaxe PowerShell)**

```powershell
Invoke-WebRequest -Uri "http://localhost:8000/send-message" -Method POST -Body "mensagem=teste"
```

**Opção 3: Instalar curl real no Windows**

```powershell
# Via chocolatey
choco install curl

# Depois use curl.exe explicitamente
curl.exe -X POST http://localhost:8000/send-message -d "mensagem=teste"
```

### Documentação Criada

Criamos o arquivo `COMANDOS_WINDOWS.md` com todos os comandos adaptados para PowerShell.

### Resultado

✅ Comandos funcionam no Windows  
✅ Documentação específica para Windows  
✅ Usuários sabem usar curl dentro do container  

---

## ❌ Workers com timeout exception

### Sintoma

Workers param de funcionar depois de ~60 segundos com exceção:

```
PhpAmqpLib\Exception\AMQPTimeoutException: 
The connection timed out after 60 sec while awaiting incoming data
```

### Causa Raiz

O método `wait()` sem timeout aguarda indefinidamente. Quando não há mensagens na fila por muito tempo, a conexão AMQP expira.

**Código problemático:**
```php
while (count($this->channel->callbacks)) {
    $this->channel->wait(); // Sem timeout!
}
```

### Solução

Adicionar timeout ao `wait()` e tratar exceção:

```php
public function consumeMessages(callable $callback, int $timeout = 3)
{
    $connection = new AMQPStreamConnection(
        env('RABBITMQ_HOST'),
        env('RABBITMQ_PORT'),
        env('RABBITMQ_USER'),
        env('RABBITMQ_PASSWORD')
    );
    
    $channel = $connection->channel();
    $channel->queue_declare($this->queue, false, true, false, false);
    
    $channel->basic_consume(
        $this->queue,
        '',
        false,
        false,
        false,
        false,
        $callback
    );
    
    echo "Aguardando mensagens. Pressione CTRL+C para parar.\n";
    
    while (count($channel->callbacks)) {
        try {
            // Timeout de 3 segundos
            $channel->wait(null, false, $timeout);
        } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
            // Timeout é normal quando não há mensagens
            // Continua o loop
            continue;
        }
    }
    
    $channel->close();
    $connection->close();
}
```

### Explicação

- `wait(null, false, 3)` aguarda até 3 segundos por mensagem
- Se não receber nada em 3s, lança `AMQPTimeoutException`
- Capturamos a exceção e continuamos o loop
- Worker permanece ativo indefinidamente

### Resultado

✅ Workers não crasham mais  
✅ Funcionam indefinidamente  
✅ Processam mensagens normalmente quando chegam  

---

## 📊 Resumo de Correções

| Problema | Impacto | Complexidade | Status |
|----------|---------|--------------|--------|
| bcmod() undefined | 🔴 Crítico | Média | ✅ Resolvido |
| is_consuming() | 🔴 Crítico | Baixa | ✅ Resolvido |
| HTTP timeout | 🟠 Alto | Média | ✅ Resolvido |
| curl Windows | 🟡 Médio | Baixa | ✅ Resolvido |
| Worker timeout | 🟠 Alto | Baixa | ✅ Resolvido |

---

## 🎓 Lições Aprendidas

### 1. Extensões PHP Importam
Sempre verifique dependências de extensões antes de usar bibliotecas. `php-amqplib` requer `bcmath`.

### 2. Namespace Matters
Funções globais do PHP (`bcmod()`) não existem automaticamente em namespaces customizados. Crie wrappers quando necessário.

### 3. Blocking vs Non-Blocking
**Regra de ouro:**
- HTTP endpoints → `basic_get()` (não-bloqueante)
- CLI workers → `basic_consume()` (bloqueante)

### 4. Platform-Specific Issues
Sempre documente diferenças entre plataformas (Windows vs Linux/Mac). `curl` é diferente no PowerShell!

### 5. Timeouts São Normais
Em workers que aguardam mensagens, timeouts são comportamento esperado quando a fila está vazia. Trate como caso normal, não erro.

---

## 🔍 Debugging Tips

### Verificar extensão bcmath
```powershell
docker-compose exec laravel_app php -m | grep bcmath
# Deve retornar: bcmath
```

### Ver logs do RabbitMQ
```powershell
docker-compose logs -f rabbitmq
```

### Ver logs do Laravel
```powershell
docker-compose exec laravel_app tail -f storage/logs/laravel.log
```

### Testar conexão RabbitMQ
```powershell
docker-compose exec laravel_app php artisan tinker
```

```php
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
echo "Conectado!\n";
$connection->close();
```

### Verificar filas
Acesse: http://localhost:15672 (guest/guest)

---

## 📚 Referências

- [php-amqplib Documentation](https://github.com/php-amqplib/php-amqplib)
- [RabbitMQ Tutorials](https://www.rabbitmq.com/getstarted.html)
- [PHP bcmath Extension](https://www.php.net/manual/en/book.bc.php)
- [AMQP 0-9-1 Protocol](https://www.rabbitmq.com/tutorials/amqp-concepts.html)

---

**Última atualização:** Outubro 2025  
**Ambiente:** Docker + PHP 8.2 + Laravel 12.x + RabbitMQ 3.x + php-amqplib 2.x
