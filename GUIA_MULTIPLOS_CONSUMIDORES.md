# 👥 Guia: Múltiplos Consumidores no RabbitMQ

Este guia explica como trabalhar com múltiplos consumidores e a diferença crucial entre **Work Queue** e **Pub/Sub**.

## 📋 Índice

- [Conceitos Fundamentais](#conceitos-fundamentais)
- [Work Queue (Fila de Trabalho)](#work-queue-fila-de-trabalho)
- [Pub/Sub (Publicação/Assinatura)](#pubsub-publicaçãoassinatura)
- [Quando Usar Cada Um](#quando-usar-cada-um)
- [Exemplos Práticos](#exemplos-práticos)

---

## 🎯 Conceitos Fundamentais

### O Problema

Você tem **3 workers** rodando e envia **3 mensagens**. O que acontece?

**Depende do padrão que você está usando!**

---

## 📦 Work Queue (Fila de Trabalho)

### Como Funciona

```
┌─────────────────────────────────────────┐
│                                         │
│         [Fila de Mensagens]             │
│                                         │
│   ┌────┐  ┌────┐  ┌────┐               │
│   │ M1 │  │ M2 │  │ M3 │               │
│   └─┬──┘  └─┬──┘  └─┬──┘               │
│     │       │       │                   │
│     ↓       ↓       ↓                   │
│  Worker1  Worker2  Worker3              │
│                                         │
└─────────────────────────────────────────┘

Resultado: Cada worker recebe UMA mensagem DIFERENTE
```

### Características

- ✅ **Distribuição Round-Robin** - Mensagens distribuídas igualmente
- ✅ **Cada mensagem processada UMA vez** - Por apenas um worker
- ✅ **Load Balancing** - Carga distribuída entre workers
- ✅ **Paralelização** - Múltiplos workers processam simultaneamente

### Quando a Mensagem é Removida

A mensagem é **removida da fila** assim que um worker a consome (com ACK).

### Implementação

**Service:** `RabbitMQService.php`

```php
class RabbitMQService
{
    private $queue = 'fila_teste';
    
    public function sendMessage(string $message)
    {
        // Envia para a fila
        $channel->basic_publish($msg, '', $this->queue);
    }
    
    public function consumeMessages(callable $callback)
    {
        // Consome da fila (round-robin automático)
        $channel->basic_consume($this->queue, '', false, false, false, false, $callback);
    }
}
```

**Worker Command:** `RabbitMQWorker.php`

```php
class RabbitMQWorker extends Command
{
    protected $signature = 'rabbitmq:worker';
    
    public function handle()
    {
        $rabbit = new RabbitMQService();
        
        $rabbit->consumeMessages(function ($msg) {
            $this->info("Processando: {$msg->body}");
            // Processa a mensagem...
        });
    }
}
```

### Como Usar

```powershell
# Terminal 1 - Worker 1
docker-compose exec laravel_app php artisan rabbitmq:worker

# Terminal 2 - Worker 2
docker-compose exec laravel_app php artisan rabbitmq:worker

# Terminal 3 - Worker 3
docker-compose exec laravel_app php artisan rabbitmq:worker

# Terminal 4 - Enviar mensagens
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Task 1'"
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Task 2'"
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Task 3'"
```

### Resultado

```
Worker 1 recebe: "Task 1"
Worker 2 recebe: "Task 2"
Worker 3 recebe: "Task 3"
```

**Cada worker processa uma mensagem diferente!**

---

## 📡 Pub/Sub (Publicação/Assinatura)

### Como Funciona

```
┌─────────────────────────────────────────┐
│                                         │
│         [Exchange: Fanout]              │
│                  │                      │
│         ┌────────┼────────┐             │
│         ↓        ↓        ↓             │
│      Queue1   Queue2   Queue3           │
│         │        │        │             │
│         ↓        ↓        ↓             │
│   Subscriber1 Subscriber2 Subscriber3   │
│                                         │
└─────────────────────────────────────────┘

Resultado: TODOS os subscribers recebem a MESMA mensagem
```

### Características

- ✅ **Broadcasting** - Mensagem copiada para todos
- ✅ **Cada subscriber recebe TODAS as mensagens** - Cópia independente
- ✅ **Event-Driven** - Múltiplos serviços reagem ao mesmo evento
- ✅ **Desacoplamento** - Serviços não sabem uns dos outros

### Quando a Mensagem é Removida

A mensagem é **replicada** para cada subscriber. Cada um tem sua própria cópia na sua própria fila.

### Implementação

**Service:** `RabbitMQPubSubService.php`

```php
class RabbitMQPubSubService
{
    private $exchange = 'eventos';
    
    public function publish(string $message)
    {
        // Publica no exchange (fanout)
        $channel->exchange_declare($this->exchange, 'fanout', false, false, false);
        $channel->basic_publish($msg, $this->exchange);
    }
    
    public function subscribe(callable $callback)
    {
        $channel->exchange_declare($this->exchange, 'fanout', false, false, false);
        
        // Cria fila EXCLUSIVA para este subscriber
        list($queueName, ,) = $channel->queue_declare('', false, false, true, false);
        
        // Bind fila ao exchange
        $channel->queue_bind($queueName, $this->exchange);
        
        // Consome
        $channel->basic_consume($queueName, '', false, true, false, false, $callback);
    }
}
```

**Publisher Command:** `RabbitMQPublisher.php`

```php
class RabbitMQPublisher extends Command
{
    protected $signature = 'rabbitmq:publish {mensagem}';
    
    public function handle()
    {
        $rabbit = new RabbitMQPubSubService();
        $rabbit->publish($this->argument('mensagem'));
        $this->info('Evento publicado!');
    }
}
```

**Subscriber Command:** `RabbitMQSubscriber.php`

```php
class RabbitMQSubscriber extends Command
{
    protected $signature = 'rabbitmq:subscribe';
    
    public function handle()
    {
        $rabbit = new RabbitMQPubSubService();
        
        $rabbit->subscribe(function ($msg) {
            $this->info("Evento recebido: {$msg->body}");
            // Reage ao evento...
        });
    }
}
```

### Como Usar

```powershell
# Terminal 1 - Subscriber 1
docker-compose exec laravel_app php artisan rabbitmq:subscribe

# Terminal 2 - Subscriber 2
docker-compose exec laravel_app php artisan rabbitmq:subscribe

# Terminal 3 - Subscriber 3
docker-compose exec laravel_app php artisan rabbitmq:subscribe

# Terminal 4 - Publicar evento
docker-compose exec laravel_app php artisan rabbitmq:publish "Pedido #1234 criado"
```

### Resultado

```
Subscriber 1 recebe: "Pedido #1234 criado"
Subscriber 2 recebe: "Pedido #1234 criado"
Subscriber 3 recebe: "Pedido #1234 criado"
```

**Todos os subscribers recebem a mesma mensagem!**

---

## ⚖️ Quando Usar Cada Um

### Use Work Queue Quando:

✅ **Distribuir carga** - Processar muitas tarefas em paralelo  
✅ **Tarefas pesadas** - Redimensionar imagens, gerar relatórios  
✅ **Processamento único** - Cada tarefa deve ser processada UMA vez  
✅ **Load balancing** - Múltiplos workers, melhor performance  

**Exemplos:**
- 📧 Enviar 10.000 emails
- 🖼️ Processar 1.000 imagens
- 📦 Processar pedidos de e-commerce
- 📊 Gerar relatórios grandes
- 💾 Importar dados em lote

### Use Pub/Sub Quando:

✅ **Notificar múltiplos serviços** - Evento único, múltiplas reações  
✅ **Event-Driven Architecture** - Serviços independentes reagem  
✅ **Broadcasting** - Todos precisam saber do evento  
✅ **Desacoplamento** - Serviços não se conhecem  

**Exemplos:**
- 🛒 Pedido criado → (Email + Analytics + Shipping + Invoice)
- 👤 Usuário cadastrado → (Email boas-vindas + CRM + Analytics)
- 💳 Pagamento aprovado → (Desbloqueio + Recibo + Nota fiscal)
- 📝 Post publicado → (Notificar seguidores + Indexar busca + Cache)
- 🔔 Alerta crítico → (Email admin + SMS + Slack + PagerDuty)

---

## 📊 Comparação Lado a Lado

| Aspecto | Work Queue | Pub/Sub |
|---------|------------|---------|
| **Distribuição** | Round-robin | Broadcasting |
| **Mensagem** | Uma vez (um worker) | N vezes (todos subscribers) |
| **Objetivo** | Paralelizar tarefas | Notificar eventos |
| **Fila** | Compartilhada | Exclusiva por subscriber |
| **Exchange** | Não usa (default) | Fanout exchange |
| **Escalabilidade** | + workers = + velocidade | + subscribers = + reações |
| **Exemplo** | Processar 1000 emails | Notificar 5 serviços |

---

## 🎬 Exemplos Práticos

### Exemplo 1: E-commerce - Processar Pedidos (Work Queue)

**Cenário:** 100 pedidos chegam simultaneamente

```powershell
# Iniciar 5 workers
for ($i=1; $i -le 5; $i++) {
    Start-Process powershell -ArgumentList "docker-compose exec laravel_app php artisan rabbitmq:worker"
}

# Enviar 100 pedidos
1..100 | ForEach-Object {
    docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Pedido $_'"
}
```

**Resultado:**
- Worker 1: Processa pedidos 1, 6, 11, 16... (20 pedidos)
- Worker 2: Processa pedidos 2, 7, 12, 17... (20 pedidos)
- Worker 3: Processa pedidos 3, 8, 13, 18... (20 pedidos)
- Worker 4: Processa pedidos 4, 9, 14, 19... (20 pedidos)
- Worker 5: Processa pedidos 5, 10, 15, 20... (20 pedidos)

**Cada pedido processado UMA vez, carga distribuída!**

---

### Exemplo 2: E-commerce - Pedido Criado (Pub/Sub)

**Cenário:** Pedido criado precisa acionar múltiplos serviços

```powershell
# Subscriber 1: Email Service
docker-compose exec laravel_app php artisan rabbitmq:subscribe
# Envia email de confirmação

# Subscriber 2: Analytics Service  
docker-compose exec laravel_app php artisan rabbitmq:subscribe
# Registra métrica de venda

# Subscriber 3: Shipping Service
docker-compose exec laravel_app php artisan rabbitmq:subscribe
# Cria etiqueta de envio

# Subscriber 4: Invoice Service
docker-compose exec laravel_app php artisan rabbitmq:subscribe
# Gera nota fiscal

# Publicar evento
docker-compose exec laravel_app php artisan rabbitmq:publish "Pedido #1234 criado"
```

**Resultado:**
- Email Service: Envia email ✉️
- Analytics: Registra venda 📊
- Shipping: Cria etiqueta 📦
- Invoice: Gera NF 📄

**Todos os serviços reagiram ao MESMO evento!**

---

## 🚨 Erro Comum: Usar Padrão Errado

### ❌ Problema: Usar Work Queue para Eventos

```php
// ERRADO: Quer notificar 3 serviços sobre pedido criado
// Usa Work Queue

// Terminal 1: Email Service
php artisan rabbitmq:worker

// Terminal 2: Analytics Service
php artisan rabbitmq:worker

// Terminal 3: Shipping Service
php artisan rabbitmq:worker

// Envia evento
curl -X POST /send-message -d "mensagem=Pedido criado"
```

**Resultado:**
- ❌ Apenas UM serviço recebe (ex: Email Service)
- ❌ Analytics e Shipping não são notificados
- ❌ Comportamento imprevisível

**Solução:** Usar Pub/Sub!

---

### ❌ Problema: Usar Pub/Sub para Tarefas

```php
// ERRADO: Quer processar 100 emails
// Usa Pub/Sub

// Terminal 1-3: Workers
php artisan rabbitmq:subscribe

// Envia 100 emails
for i in 1..100
    rabbitmq:publish "Enviar email $i"
end
```

**Resultado:**
- ❌ TODOS os 3 workers processam TODOS os 100 emails
- ❌ 300 emails enviados (duplicados!)
- ❌ Desperdício de recursos

**Solução:** Usar Work Queue!

---

## 📝 Checklist de Decisão

Responda estas perguntas:

1. **Múltiplos serviços precisam reagir ao mesmo evento?**
   - ✅ Sim → **Pub/Sub**
   - ❌ Não → Work Queue

2. **Cada mensagem deve ser processada apenas uma vez?**
   - ✅ Sim → **Work Queue**
   - ❌ Não → Pub/Sub

3. **Quer distribuir carga entre workers?**
   - ✅ Sim → **Work Queue**
   - ❌ Não → Pub/Sub

4. **É uma notificação de evento que aconteceu?**
   - ✅ Sim → **Pub/Sub**
   - ❌ Não → Work Queue

5. **Quer paralelizar processamento pesado?**
   - ✅ Sim → **Work Queue**
   - ❌ Não → Pub/Sub

---

## 🎓 Resumo Final

### Work Queue = Dividir Trabalho
- Uma fila compartilhada
- Mensagens distribuídas (round-robin)
- Cada mensagem → Um worker
- Para: Tasks, jobs, processamento

### Pub/Sub = Compartilhar Notícia
- Exchange + filas exclusivas
- Mensagens replicadas (broadcast)
- Cada mensagem → Todos subscribers
- Para: Eventos, notificações, broadcasting

---

## 📚 Próximos Passos

- 📖 Leia: [`DECISAO_RAPIDA.md`](DECISAO_RAPIDA.md) - Fluxograma de decisão
- 🧪 Teste: [`TESTE_RAPIDO.md`](TESTE_RAPIDO.md) - Teste ambos os padrões
- 📦 Aprenda: [`MENSAGENS_JSON.md`](MENSAGENS_JSON.md) - Mensagens estruturadas

---

**Entendeu a diferença? Escolha o padrão certo e seu sistema funcionará perfeitamente!** 🎯
