# 🐰 Laravel + RabbitMQ - Sistema Completo de Mensageria

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)
![RabbitMQ](https://img.shields.io/badge/RabbitMQ-3.x-FF6600?logo=rabbitmq&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)

Sistema completo de mensageria assíncrona com Laravel e RabbitMQ, suportando **Work Queue**, **Pub/Sub** e **mensagens JSON estruturadas**, tudo orquestrado com Docker.

## 📋 Índice

- [Características](#-características)
- [Pré-requisitos](#-pré-requisitos)
- [Instalação Rápida](#-instalação-rápida)
- [Padrões de Mensageria](#-padrões-de-mensageria)
- [Mensagens JSON](#-mensagens-json)
- [Endpoints da API](#-endpoints-da-api)
- [Workers e Commands](#-workers-e-commands)
- [Guias Completos](#-guias-completos)
- [Estrutura do Projeto](#-estrutura-do-projeto)
- [Troubleshooting](#-troubleshooting)
- [Tecnologias](#-tecnologias)

## ✨ Características

### 📦 Funcionalidades Principais
- ✅ **Mensagens Simples** - Strings básicas para começar rápido
- ✅ **Mensagens JSON** - Envie objetos complexos com propriedades aninhadas
- ✅ **Work Queue** - Distribuição round-robin entre múltiplos workers
- ✅ **Pub/Sub** - Broadcasting de eventos para múltiplos consumidores
- ✅ **API RESTful** - Endpoints HTTP para integração fácil
- ✅ **Workers CLI** - Processamento em background via Artisan commands
- ✅ **Prioridades** - Mensagens com níveis de prioridade (1-10)
- ✅ **Metadata** - Timestamps, tipos, eventos automáticos

### 🛠️ Recursos Técnicos
- ✅ **Recebimento não-bloqueante** - Sem timeout em requisições HTTP
- ✅ **Interface de gerenciamento** RabbitMQ disponível
- ✅ **Docker Compose** - Setup completo em minutos
- ✅ **Suporte bcmath** - Extensão PHP instalada e configurada
- ✅ **Compatibilidade php-amqplib 2.x** - Namespace wrappers inclusos
- ✅ **Windows PowerShell** - Comandos prontos para Windows
- ✅ **Documentação em Português** - Guias completos e exemplos práticos

## 🔧 Pré-requisitos

- **Docker** >= 20.10
- **Docker Compose** >= 2.0
- **Git** (para clonar o repositório)
- Portas disponíveis: `8000`, `5672`, `15672`
- **Windows**: PowerShell (comandos inclusos para Windows)

## 🚀 Instalação Rápida

### 1. Clone e inicie

```powershell
# Clone o repositório
git clone <seu-repositorio>
cd laravel-rabbitmq

# Build e inicialização
docker-compose build
docker-compose up -d
```

### 2. Aguarde inicialização (30-60 segundos)

```powershell
# Verifique o status
docker-compose ps

# Você deve ver 3 containers rodando:
# - laravel_app
# - rabbitmq
# - laravel-rabbitmq-composer-1
```

### 3. Teste rápido

```powershell
# Enviar mensagem
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Olá RabbitMQ'"

# Receber mensagem
docker-compose exec laravel_app bash -c "curl http://localhost/receive-message"
```

✅ **Pronto!** Seu sistema de mensageria está funcionando!

## 📡 Padrões de Mensageria

Este projeto implementa **3 padrões** diferentes de mensageria:

### 1️⃣ Mensagens Simples (String)
**Uso:** Tarefas básicas, testes rápidos

```powershell
# Enviar
curl -X POST http://localhost:8000/send-message -d "mensagem=Hello"

# Receber
curl http://localhost:8000/receive-message
```

### 2️⃣ Work Queue (Fila de Trabalho)
**Uso:** Distribuir tarefas entre múltiplos workers (round-robin)

```powershell
# Iniciar 3 workers em terminais diferentes
docker-compose exec laravel_app php artisan rabbitmq:worker
docker-compose exec laravel_app php artisan rabbitmq:worker
docker-compose exec laravel_app php artisan rabbitmq:worker

# Enviar mensagens - cada worker recebe uma diferente
curl -X POST http://localhost:8000/send-message -d "mensagem=Task 1"
curl -X POST http://localhost:8000/send-message -d "mensagem=Task 2"
curl -X POST http://localhost:8000/send-message -d "mensagem=Task 3"
```

📘 **Leia mais:** [`GUIA_MULTIPLOS_CONSUMIDORES.md`](GUIA_MULTIPLOS_CONSUMIDORES.md)

### 3️⃣ Pub/Sub (Publicação/Assinatura)
**Uso:** Broadcasting de eventos para múltiplos serviços

```powershell
# Iniciar 3 subscribers em terminais diferentes
docker-compose exec laravel_app php artisan rabbitmq:subscribe
docker-compose exec laravel_app php artisan rabbitmq:subscribe
docker-compose exec laravel_app php artisan rabbitmq:subscribe

# Publicar evento - TODOS os subscribers recebem a mesma mensagem
docker-compose exec laravel_app php artisan rabbitmq:publish "Evento importante"
```

📘 **Leia mais:** [`DECISAO_RAPIDA.md`](DECISAO_RAPIDA.md) - Quando usar cada padrão

## 📦 Mensagens JSON

Envie **objetos complexos** com propriedades, não apenas strings!

### Tipos Disponíveis:

#### 🔹 Tarefas (Tasks)
Processamento em background com prioridade

```php
// Enviar email
POST /json/enviar-email
{
  "email": "usuario@example.com",
  "assunto": "Bem-vindo!",
  "corpo": "Obrigado por se cadastrar!"
}

// Processar pedido
POST /json/processar-pedido
{
  "pedido_id": 1234,
  "cliente": {
    "nome": "João Silva",
    "email": "joao@example.com"
  },
  "items": [
    {"produto": "Notebook", "preco": 2999.90}
  ],
  "total": 2999.90
}
```

#### 🔹 Eventos (Events)
Broadcasting para múltiplos serviços

```php
// Publicar evento
POST /json/publicar-evento
{
  "evento": "pedido.criado",
  "payload": {
    "pedido_id": 5678,
    "valor": 299.90
  }
}
```

#### 🔹 Customizado
Qualquer estrutura JSON

```php
POST /json/customizado
{
  "qualquer": "estrutura",
  "arrays": [1, 2, 3],
  "objetos": {
    "aninhados": true
  }
}
```

### Worker JSON

```powershell
# Processar mensagens JSON automaticamente
docker-compose exec laravel_app php artisan rabbitmq:json-worker
```

O worker roteia automaticamente por tipo:
- ✉️ `enviar_email` → Processa envio de emails
- 📦 `processar_pedido` → Processa pedidos
- 🖼️ `processar_imagem` → Processa imagens
- 📊 `gerar_relatorio` → Gera relatórios
- 📡 Eventos: `pedido.criado`, `usuario.cadastrado`, `pagamento.aprovado`

📘 **Leia mais:** [`MENSAGENS_JSON.md`](MENSAGENS_JSON.md) - Guia completo com exemplos

## ⚙️ Configuração

### Variáveis de Ambiente

As configurações do RabbitMQ são definidas no `docker-compose.yml`:

```yaml
environment:
  - RABBITMQ_HOST=rabbitmq
  - RABBITMQ_PORT=5672
  - RABBITMQ_USER=guest
  - RABBITMQ_PASSWORD=guest
  - RABBITMQ_QUEUE=fila_teste
```

### Acessos

| Serviço | URL | Credenciais |
|---------|-----|-------------|
| **Laravel App** | http://localhost:8000 | - |
| **RabbitMQ Management** | http://localhost:15672 | user: `guest` / pass: `guest` |
| **RabbitMQ AMQP** | localhost:5672 | user: `guest` / pass: `guest` |

## � Endpoints da API

### Mensagens Simples (String)

#### POST /send-message
Envia mensagem simples para fila

```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Olá RabbitMQ'"
```

**Resposta:**
```json
{
  "status": "ok",
  "message": "Olá RabbitMQ!"
}
```

#### GET /receive-message
Recebe mensagem da fila (não-bloqueante)

```powershell
docker-compose exec laravel_app bash -c "curl http://localhost/receive-message"
```

**Resposta:**
```json
{
  "status": "ok",
  "message": "Olá RabbitMQ!",
  "received_at": "2025-10-07T19:35:07+00:00"
}
```

### Mensagens JSON (Objetos)

#### POST /json/enviar-email
Envia tarefa de email

```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/enviar-email -d 'email=usuario@example.com&assunto=Teste&corpo=Mensagem'"
```

#### POST /json/processar-pedido
Envia pedido para processamento

```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-pedido -d 'pedido_id=1234&nome=João&email=joao@example.com'"
```

#### POST /json/processar-imagem
Processa imagem

```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-imagem -d 'arquivo=foto.jpg&operacao=redimensionar&largura=800'"
```

#### POST /json/publicar-evento
Publica evento (Pub/Sub)

```powershell
# Opções: pedido.criado, usuario.cadastrado, pagamento.aprovado
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/publicar-evento -d 'evento=pedido.criado'"
```

#### POST /json/customizado
Envia dados customizados

```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/customizado -d 'campo1=valor1&campo2=valor2'"
```

#### GET /json/receber
Recebe mensagem JSON

```powershell
docker-compose exec laravel_app bash -c "curl http://localhost/json/receber"
```

📘 **Mais exemplos:** [`COMANDOS_WINDOWS.md`](COMANDOS_WINDOWS.md)

## ⚡ Workers e Commands

### Work Queue Workers

```powershell
# Worker simples (processa strings)
docker-compose exec laravel_app php artisan rabbitmq:worker

# Worker JSON (processa objetos)
docker-compose exec laravel_app php artisan rabbitmq:json-worker
```

### Pub/Sub (Publicação/Assinatura)

```powershell
# Publisher - envia eventos
docker-compose exec laravel_app php artisan rabbitmq:publish "Meu evento"

# Subscriber - recebe eventos
docker-compose exec laravel_app php artisan rabbitmq:subscribe
```

### Teste Rápido

```powershell
# Terminal 1: Iniciar worker JSON
docker-compose exec laravel_app php artisan rabbitmq:json-worker

# Terminal 2: Enviar pedido
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-pedido"

# Ver processamento no Terminal 1:
# ╔══════════════════════════════════════════════╗
# ║     📦 NOVA TAREFA: processar_pedido         ║
# ╚══════════════════════════════════════════════╝
# Pedido ID: 1234
# Cliente: João Silva (joao@example.com)
# ✅ Pedido processado com sucesso!
```

📘 **Leia mais:** [`TESTE_RAPIDO.md`](TESTE_RAPIDO.md)

## 📚 Guias Completos

| Guia | Descrição |
|------|-----------|
| 📖 [`README.md`](README.md) | Este arquivo - visão geral do projeto |
| 🔧 [`FIXED_ISSUES.md`](FIXED_ISSUES.md) | Problemas corrigidos e soluções técnicas |
| 👥 [`GUIA_MULTIPLOS_CONSUMIDORES.md`](GUIA_MULTIPLOS_CONSUMIDORES.md) | Work Queue vs Pub/Sub - quando usar cada um |
| ⚡ [`DECISAO_RAPIDA.md`](DECISAO_RAPIDA.md) | Escolha rápida do padrão ideal |
| 💻 [`COMANDOS_WINDOWS.md`](COMANDOS_WINDOWS.md) | Comandos específicos para Windows PowerShell |
| 🧪 [`TESTE_RAPIDO.md`](TESTE_RAPIDO.md) | Guia de teste em 5 minutos |
| 📦 [`MENSAGENS_JSON.md`](MENSAGENS_JSON.md) | Mensagens JSON - objetos complexos |

## 📁 Estrutura do Projeto

```
laravel-rabbitmq/
├── 📄 README.md                                 # Este arquivo - visão geral
├── 📄 FIXED_ISSUES.md                           # Problemas corrigidos
├── 📄 GUIA_MULTIPLOS_CONSUMIDORES.md            # Work Queue vs Pub/Sub
├── 📄 DECISAO_RAPIDA.md                         # Qual padrão usar?
├── 📄 COMANDOS_WINDOWS.md                       # Comandos Windows PowerShell
├── 📄 TESTE_RAPIDO.md                           # Teste em 5 minutos
├── 📄 MENSAGENS_JSON.md                         # Mensagens JSON/objetos
│
├── 🐳 docker-compose.yml                        # Orquestração Docker
├── 🐳 Dockerfile                                # Imagem Laravel customizada
│
├── 📁 app/                                      # Aplicação Laravel
│   ├── 📁 app/
│   │   ├── 📁 Http/Controllers/
│   │   │   ├── RabbitController.php             # API mensagens simples
│   │   │   └── RabbitJsonController.php         # API mensagens JSON
│   │   │
│   │   ├── 📁 Services/
│   │   │   ├── RabbitMQService.php              # Serviço Work Queue
│   │   │   ├── RabbitMQPubSubService.php        # Serviço Pub/Sub
│   │   │   └── RabbitMQJsonService.php          # Serviço JSON
│   │   │
│   │   ├── 📁 Console/Commands/
│   │   │   ├── RabbitMQWorker.php               # Worker Work Queue
│   │   │   ├── RabbitMQPublisher.php            # Publisher Pub/Sub
│   │   │   ├── RabbitMQSubscriber.php           # Subscriber Pub/Sub
│   │   │   └── RabbitMQJsonWorker.php           # Worker JSON
│   │   │
│   │   └── 📁 Support/
│   │       └── phpamqp_compat.php               # Compatibilidade bcmath
│   │
│   ├── 📁 routes/
│   │   ├── api.php                              # Rotas API
│   │   └── web.php                              # Rotas web (JSON endpoints)
│   │
│   └── composer.json                            # Dependências PHP
│
└── 📁 vendor/                                   # Dependências instaladas
    └── php-amqplib/                             # Cliente RabbitMQ
```

### 🔑 Arquivos Principais

| Arquivo | Descrição |
|---------|-----------|
| **RabbitMQService.php** | Work Queue - sendMessage(), getMessage(), consumeMessages() |
| **RabbitMQPubSubService.php** | Pub/Sub - publish(), subscribe() com fanout exchange |
| **RabbitMQJsonService.php** | JSON - sendJson(), sendTask(), sendEvent(), getJson() |
| **RabbitController.php** | Endpoints HTTP para strings: /send-message, /receive-message |
| **RabbitJsonController.php** | Endpoints HTTP JSON: /json/enviar-email, /json/processar-pedido, etc |
| **RabbitMQWorker.php** | Command: `php artisan rabbitmq:worker` |
| **RabbitMQJsonWorker.php** | Command: `php artisan rabbitmq:json-worker` |
| **phpamqp_compat.php** | Resolve namespace bcmath para php-amqplib |

## 🐛 Troubleshooting

### ❌ Erro: "bcmod() undefined function"

**Solução:** A extensão bcmath já está incluída no Dockerfile. Rebuild:

```powershell
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### ❌ Erro: "Connection refused" ao acessar RabbitMQ

**Causa:** Container RabbitMQ ainda inicializando.

**Solução:** Aguarde 30-60 segundos. Verifique logs:

```powershell
docker-compose logs rabbitmq
```

### ❌ Worker com timeout/erro de conexão

**Solução:** Já implementado tratamento de timeout. Workers agora esperam 3 segundos e continuam em caso de timeout.

```powershell
# Ver logs do worker
docker-compose exec laravel_app tail -f storage/logs/laravel.log
```

### ❌ "Port already in use"

**Causa:** Portas 8000, 5672 ou 15672 já em uso.

**Solução:** Altere as portas no `docker-compose.yml`:

```yaml
ports:
  - "8080:8000"  # Altera porta externa para 8080
```

### ❌ Comandos curl no Windows não funcionam

**Solução:** Use dentro do container:

```powershell
# ✅ Correto (Windows PowerShell)
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=teste'"

# ❌ Errado (curl direto no PowerShell)
curl -X POST http://localhost:8000/send-message -d "mensagem=teste"
```

📘 **Ver mais:** [`COMANDOS_WINDOWS.md`](COMANDOS_WINDOWS.md)

### ❌ Mensagens não são recebidas no Work Queue

**Diagnóstico:**

1. **Verifique quantas mensagens estão na fila:**
   ```powershell
   # Acesse http://localhost:15672
   # Login: guest / guest
   # Vá em Queues > fila_teste
   ```

2. **Verifique se está usando o padrão correto:**
   - Work Queue → Mensagens são **consumidas** (desaparecem)
   - Pub/Sub → Mensagens são **copiadas** para todos

3. **Teste de envio:**
   ```powershell
   docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=teste'"
   ```

### 🧹 Limpar todas as mensagens da fila

**Via RabbitMQ Management:**
1. Acesse http://localhost:15672
2. Vá em **Queues** > `fila_teste`
3. Clique em **Purge Messages**

**Via CLI:**
```powershell
docker-compose exec rabbitmq rabbitmqctl purge_queue fila_teste
```

### 🔍 Ver logs detalhados

```powershell
# Laravel
docker-compose exec laravel_app tail -f storage/logs/laravel.log

# RabbitMQ
docker-compose logs -f rabbitmq

# Todos os containers
docker-compose logs -f
```

📘 **Problemas técnicos resolvidos:** [`FIXED_ISSUES.md`](FIXED_ISSUES.md)

## 🛠️ Comandos Úteis

### Docker Compose

```powershell
# Iniciar containers
docker-compose up -d

# Parar containers
docker-compose down

# Ver logs
docker-compose logs -f laravel_app
docker-compose logs -f rabbitmq

# Rebuild imagens
docker-compose build --no-cache

# Executar comando no container Laravel
docker-compose exec laravel_app bash

# Executar Artisan commands
docker-compose exec laravel_app php artisan <comando>
```

### Laravel Artisan - Workers

```powershell
# Work Queue - worker simples
docker-compose exec laravel_app php artisan rabbitmq:worker

# Work Queue - worker JSON
docker-compose exec laravel_app php artisan rabbitmq:json-worker

# Pub/Sub - publisher
docker-compose exec laravel_app php artisan rabbitmq:publish "Mensagem"

# Pub/Sub - subscriber
docker-compose exec laravel_app php artisan rabbitmq:subscribe
```

### Laravel Artisan - Utilitários

```powershell
# Limpar cache
docker-compose exec laravel_app php artisan cache:clear

# Listar rotas
docker-compose exec laravel_app php artisan route:list

# Executar tinker
docker-compose exec laravel_app php artisan tinker
```

### Composer

```powershell
# Instalar dependências
docker-compose exec laravel_app composer install

# Atualizar dependências
docker-compose exec laravel_app composer update

# Regenerar autoload
docker-compose exec laravel_app composer dump-autoload -o
```

### RabbitMQ Management

```powershell
# Listar filas
docker-compose exec rabbitmq rabbitmqctl list_queues

# Limpar fila
docker-compose exec rabbitmq rabbitmqctl purge_queue fila_teste

# Listar exchanges
docker-compose exec rabbitmq rabbitmqctl list_exchanges

# Status do RabbitMQ
docker-compose exec rabbitmq rabbitmqctl status
```

## 🧪 Exemplos de Teste

### Teste 1: Mensagem Simples

```powershell
# Enviar
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Hello World'"

# Receber
docker-compose exec laravel_app bash -c "curl http://localhost/receive-message"
```

### Teste 2: Work Queue (Múltiplos Workers)

```powershell
# Terminal 1
docker-compose exec laravel_app php artisan rabbitmq:worker

# Terminal 2
docker-compose exec laravel_app php artisan rabbitmq:worker

# Terminal 3 - Enviar mensagens
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Task 1'"
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Task 2'"
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Task 3'"

# Cada worker recebe uma mensagem diferente (round-robin)
```

### Teste 3: Pub/Sub (Broadcasting)

```powershell
# Terminal 1
docker-compose exec laravel_app php artisan rabbitmq:subscribe

# Terminal 2
docker-compose exec laravel_app php artisan rabbitmq:subscribe

# Terminal 3 - Publicar
docker-compose exec laravel_app php artisan rabbitmq:publish "Evento importante"

# TODOS os subscribers recebem a mesma mensagem
```

### Teste 4: Mensagens JSON

```powershell
# Terminal 1 - Worker JSON
docker-compose exec laravel_app php artisan rabbitmq:json-worker

# Terminal 2 - Enviar pedido
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-pedido"

# Terminal 2 - Enviar email
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/enviar-email -d 'email=teste@example.com&assunto=Ola'"

# Terminal 2 - Publicar evento
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/publicar-evento -d 'evento=pedido.criado'"
```

### Teste 5: Carga (100 mensagens)

```powershell
# PowerShell - enviar 100 mensagens
1..100 | ForEach-Object {
    docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Teste $_'"
}

# Verificar quantidade na fila
# Acesse: http://localhost:15672
```

## 📚 Tecnologias

| Tecnologia | Versão | Descrição |
|------------|--------|-----------|
| **PHP** | 8.2 | Linguagem de programação |
| **Laravel** | 12.x | Framework PHP |
| **RabbitMQ** | 3.x | Message broker AMQP |
| **php-amqplib** | 2.x | Cliente PHP para AMQP |
| **Docker** | 20.10+ | Containerização |
| **Docker Compose** | 2.0+ | Orquestração de containers |
| **Composer** | 2.x | Gerenciador de dependências PHP |

## 🎓 Conceitos Implementados

### Padrões de Mensageria

| Padrão | Implementação | Uso |
|--------|--------------|-----|
| **Work Queue** | `RabbitMQService` + `RabbitMQWorker` | Distribuir tarefas (round-robin) |
| **Pub/Sub** | `RabbitMQPubSubService` + fanout exchange | Broadcasting de eventos |
| **Message Format** | Strings simples + JSON estruturado | Flexibilidade de dados |

### Recursos Técnicos

✅ **Extensões PHP:** zip, pdo, pdo_mysql, bcmath  
✅ **Namespace Compatibility:** Wrappers para bcmath (`phpamqp_compat.php`)  
✅ **Non-blocking HTTP:** `getMessage()` não trava requisições  
✅ **Blocking CLI:** `consumeMessages()` para workers contínuos  
✅ **Timeout Handling:** Workers com tratamento de timeout (3s)  
✅ **Message Priority:** Tarefas com prioridade 1-10  
✅ **Metadata:** Timestamps, content-type, delivery-mode automáticos  

## 🎯 Casos de Uso

### Work Queue - Quando Usar?

- ✅ Processar pedidos de e-commerce
- ✅ Enviar emails em lote
- ✅ Redimensionar imagens
- ✅ Gerar relatórios
- ✅ Processar pagamentos
- ✅ Importar dados

**Característica:** Cada mensagem é processada por **apenas um** worker.

### Pub/Sub - Quando Usar?

- ✅ Notificar múltiplos serviços sobre evento
- ✅ Logs distribuídos
- ✅ Sincronização de dados
- ✅ Webhooks internos
- ✅ Analytics e métricas
- ✅ Auditoria

**Característica:** Cada mensagem é **copiada** para todos os subscribers.

📘 **Leia mais:** [`DECISAO_RAPIDA.md`](DECISAO_RAPIDA.md)

## ⚙️ Configuração Avançada

### Variáveis de Ambiente

As configurações do RabbitMQ estão no `docker-compose.yml`:

```yaml
environment:
  - RABBITMQ_HOST=rabbitmq
  - RABBITMQ_PORT=5672
  - RABBITMQ_USER=guest
  - RABBITMQ_PASSWORD=guest
  - RABBITMQ_QUEUE=fila_teste
```

### Acessos

| Serviço | URL | Credenciais |
|---------|-----|-------------|
| **Laravel App** | http://localhost:8000 | - |
| **RabbitMQ Management** | http://localhost:15672 | user: `guest` / pass: `guest` |
| **RabbitMQ AMQP** | localhost:5672 | user: `guest` / pass: `guest` |

### Personalizar Fila

Edite `app/Services/RabbitMQService.php`:

```php
private $queue = 'minha_fila_customizada';
```

### Adicionar Novo Tipo de Tarefa JSON

Edite `app/Console/Commands/RabbitMQJsonWorker.php`:

```php
protected function processTask($tipo, $dados)
{
    switch ($tipo) {
        case 'minha_tarefa':
            $this->info("✅ Processando minha tarefa customizada");
            // Seu código aqui
            break;
        // ... outros casos
    }
}
```

### Adicionar Novo Evento

Edite `app/Console/Commands/RabbitMQJsonWorker.php`:

```php
protected function processEvent($evento, $payload)
{
    switch ($evento) {
        case 'meu.evento':
            $this->info("📡 Evento customizado recebido");
            // Seu código aqui
            break;
        // ... outros casos
    }
}
```

## 📄 Licença

Este projeto é fornecido como está, para fins educacionais e de desenvolvimento.

## 🤝 Contribuindo

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/NovaFeature`)
3. Commit suas mudanças (`git commit -m 'Add NovaFeature'`)
4. Push para a branch (`git push origin feature/NovaFeature`)
5. Abra um Pull Request

## 📞 Suporte

### Documentação Disponível

Consulte os guias específicos conforme sua necessidade:

| Preciso... | Consulte |
|------------|----------|
| Visão geral do projeto | Este README.md |
| Resolver erros técnicos | [FIXED_ISSUES.md](FIXED_ISSUES.md) |
| Entender Work Queue vs Pub/Sub | [GUIA_MULTIPLOS_CONSUMIDORES.md](GUIA_MULTIPLOS_CONSUMIDORES.md) |
| Decidir qual padrão usar | [DECISAO_RAPIDA.md](DECISAO_RAPIDA.md) |
| Comandos Windows PowerShell | [COMANDOS_WINDOWS.md](COMANDOS_WINDOWS.md) |
| Testar rapidamente | [TESTE_RAPIDO.md](TESTE_RAPIDO.md) |
| Enviar objetos JSON | [MENSAGENS_JSON.md](MENSAGENS_JSON.md) |

### Problemas Comuns

1. **bcmod() erro:** Rebuild container com `--no-cache`
2. **Timeout:** Já tratado automaticamente nos workers
3. **Windows curl:** Use comandos do `COMANDOS_WINDOWS.md`
4. **Mensagens desaparecem:** Veja diferença Work Queue vs Pub/Sub

### Verificar Logs

```powershell
# Laravel
docker-compose exec laravel_app tail -f storage/logs/laravel.log

# RabbitMQ
docker-compose logs -f rabbitmq
```

### RabbitMQ Management UI

Acesse http://localhost:15672 (guest/guest) para:
- Ver filas e quantidade de mensagens
- Ver exchanges e bindings
- Purgar mensagens
- Ver estatísticas em tempo real

---

## 🚀 Quick Start (TL;DR)

```powershell
# 1. Iniciar
docker-compose up -d

# 2. Testar mensagem simples
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Ola'"
docker-compose exec laravel_app bash -c "curl http://localhost/receive-message"

# 3. Testar JSON worker
# Terminal 1:
docker-compose exec laravel_app php artisan rabbitmq:json-worker

# Terminal 2:
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-pedido"

# ✅ Pronto!
```

---

**Desenvolvido com ❤️ usando Laravel e RabbitMQ**

✨ **Sistema completo de mensageria assíncrona pronto para produção**
