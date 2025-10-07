# 💻 Comandos Windows PowerShell

Guia completo de comandos para usar o projeto no **Windows PowerShell**.

---

## ⚠️ Importante: curl no Windows

No Windows PowerShell, `curl` é um **alias** para `Invoke-WebRequest`, que tem sintaxe diferente do curl Unix/Linux.

### ❌ Isso NÃO funciona no PowerShell:
```powershell
curl -X POST http://localhost:8000/send-message -d "mensagem=teste"
# Erro: Cannot bind parameter 'Headers'...
```

### ✅ Solução Recomendada:

**Executar curl DENTRO do container Docker:**

```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=teste'"
```

**Por quê?**
- ✅ Sintaxe Unix/Linux funciona
- ✅ Não precisa instalar nada
- ✅ Funciona igual em todos os sistemas
- ✅ Mesmos comandos da documentação

---

## 🚀 Comandos Básicos

### Iniciar Projeto

```powershell
# Build e iniciar containers
docker-compose build
docker-compose up -d

# Verificar status
docker-compose ps

# Ver logs
docker-compose logs -f laravel_app
docker-compose logs -f rabbitmq
```

### Parar Projeto

```powershell
# Parar containers
docker-compose down

# Parar e remover volumes
docker-compose down -v
```

---

## 📨 Mensagens Simples (String)

### Enviar Mensagem

```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Olá RabbitMQ'"
```

**Variação com mensagem personalizada:**
```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Teste 123'"
```

### Receber Mensagem

```powershell
docker-compose exec laravel_app bash -c "curl http://localhost/receive-message"
```

---

## 📦 Mensagens JSON

### Enviar Email

```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/enviar-email -d 'email=usuario@example.com&assunto=Bem-vindo&corpo=Obrigado por se cadastrar'"
```

**Com valores customizados:**
```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/enviar-email -d 'email=joao@teste.com&assunto=Teste&corpo=Mensagem de teste'"
```

### Processar Pedido

```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-pedido -d 'pedido_id=1234&nome=João Silva&email=joao@example.com&cpf=123.456.789-00'"
```

**Valores padrão (teste rápido):**
```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-pedido"
```

### Processar Imagem

```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-imagem -d 'arquivo=foto.jpg&operacao=redimensionar&largura=800&altura=600&qualidade=85'"
```

**Com valores padrão:**
```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-imagem"
```

### Publicar Evento

**Pedido criado:**
```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/publicar-evento -d 'evento=pedido.criado'"
```

**Usuário cadastrado:**
```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/publicar-evento -d 'evento=usuario.cadastrado'"
```

**Pagamento aprovado:**
```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/publicar-evento -d 'evento=pagamento.aprovado'"
```

### Enviar Dados Customizados

```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/customizado -d 'nome=João&idade=30&cidade=São Paulo&ativo=true'"
```

### Receber Mensagem JSON

```powershell
docker-compose exec laravel_app bash -c "curl http://localhost/json/receber"
```

---

## 👷 Workers (Consumidores)

### Worker Work Queue (String)

```powershell
docker-compose exec laravel_app php artisan rabbitmq:worker
```

**Parar:** Pressione `CTRL + C`

### Worker JSON

```powershell
docker-compose exec laravel_app php artisan rabbitmq:json-worker
```

**Parar:** Pressione `CTRL + C`

---

## 📡 Pub/Sub

### Publisher (Publicar Evento)

```powershell
docker-compose exec laravel_app php artisan rabbitmq:publish "Meu evento de teste"
```

**Exemplo com evento de pedido:**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:publish "Pedido #5678 foi criado"
```

### Subscriber (Assinar Eventos)

```powershell
docker-compose exec laravel_app php artisan rabbitmq:subscribe
```

**Parar:** Pressione `CTRL + C`

---

## 🧪 Teste Completo: Work Queue

### Cenário: 3 Workers + 5 Mensagens

**Terminal 1:**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:worker
```

**Terminal 2:**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:worker
```

**Terminal 3:**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:worker
```

**Terminal 4 - Enviar mensagens:**
```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Task 1'"
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Task 2'"
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Task 3'"
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Task 4'"
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Task 5'"
```

**Resultado Esperado:**
```
Worker 1: Task 1
Worker 2: Task 2
Worker 3: Task 3
Worker 1: Task 4
Worker 2: Task 5
```

---

## 🧪 Teste Completo: Pub/Sub

### Cenário: 3 Subscribers + 1 Evento

**Terminal 1:**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:subscribe
```

**Terminal 2:**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:subscribe
```

**Terminal 3:**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:subscribe
```

**Terminal 4 - Publicar evento:**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:publish "Evento importante para todos"
```

**Resultado Esperado:**
```
Subscriber 1: Evento importante para todos
Subscriber 2: Evento importante para todos
Subscriber 3: Evento importante para todos
```

---

## 🧪 Teste Completo: JSON Worker

**Terminal 1 - Iniciar worker:**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:json-worker
```

**Terminal 2 - Enviar tarefas:**
```powershell
# Enviar email
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/enviar-email"

# Processar pedido
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-pedido"

# Processar imagem
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-imagem"

# Publicar evento
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/publicar-evento -d 'evento=pedido.criado'"
```

**Ver processamento no Terminal 1**

---

## 🔁 Enviar Múltiplas Mensagens (Loop)

### Enviar 10 mensagens

```powershell
1..10 | ForEach-Object {
    docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Mensagem $_'"
}
```

### Enviar 100 mensagens

```powershell
1..100 | ForEach-Object {
    docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Teste $_'"
    Write-Host "Enviado: $_/100" -ForegroundColor Green
}
```

### Enviar pedidos em lote

```powershell
1..50 | ForEach-Object {
    docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-pedido -d 'pedido_id=$_'"
}
```

---

## 🛠️ Comandos Úteis

### Laravel Artisan

```powershell
# Limpar cache
docker-compose exec laravel_app php artisan cache:clear

# Listar rotas
docker-compose exec laravel_app php artisan route:list

# Listar comandos
docker-compose exec laravel_app php artisan list

# Tinker (REPL)
docker-compose exec laravel_app php artisan tinker
```

### Composer

```powershell
# Instalar dependências
docker-compose exec laravel_app composer install

# Atualizar dependências
docker-compose exec laravel_app composer update

# Dump autoload
docker-compose exec laravel_app composer dump-autoload -o
```

### RabbitMQ Management

```powershell
# Listar filas
docker-compose exec rabbitmq rabbitmqctl list_queues

# Limpar fila específica
docker-compose exec rabbitmq rabbitmqctl purge_queue fila_teste

# Listar exchanges
docker-compose exec rabbitmq rabbitmqctl list_exchanges

# Status
docker-compose exec rabbitmq rabbitmqctl status
```

### Logs

```powershell
# Laravel logs (tempo real)
docker-compose exec laravel_app tail -f storage/logs/laravel.log

# RabbitMQ logs
docker-compose logs -f rabbitmq

# Todos os logs
docker-compose logs -f

# Últimas 100 linhas
docker-compose logs --tail=100 laravel_app
```

### Docker

```powershell
# Entrar no container Laravel (bash)
docker-compose exec laravel_app bash

# Entrar no container RabbitMQ
docker-compose exec rabbitmq bash

# Ver containers rodando
docker-compose ps

# Rebuild sem cache
docker-compose build --no-cache

# Remover tudo e recomeçar
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

---

## 🌐 Acessar Interfaces

### Laravel Application

```powershell
# Abrir no navegador
Start-Process "http://localhost:8000"
```

### RabbitMQ Management UI

```powershell
# Abrir no navegador
Start-Process "http://localhost:15672"
# Login: guest / guest
```

---

## 🔍 Debugging

### Verificar extensão bcmath

```powershell
docker-compose exec laravel_app php -m | Select-String "bcmath"
```

### Testar conexão RabbitMQ

```powershell
docker-compose exec laravel_app php artisan tinker
```

Dentro do tinker:
```php
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
echo "Conectado!\n";
$connection->close();
```

### Ver informações do PHP

```powershell
docker-compose exec laravel_app php -i
```

### Ver versão do PHP

```powershell
docker-compose exec laravel_app php -v
```

---

## 🎯 Scripts Úteis

### Script: Testar tudo rapidamente

Salve como `teste-rapido.ps1`:

```powershell
Write-Host "=== Teste Rápido RabbitMQ ===" -ForegroundColor Cyan

Write-Host "`n1. Enviando mensagem simples..." -ForegroundColor Yellow
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Teste PowerShell'"

Write-Host "`n2. Recebendo mensagem..." -ForegroundColor Yellow
docker-compose exec laravel_app bash -c "curl http://localhost/receive-message"

Write-Host "`n3. Enviando tarefa JSON..." -ForegroundColor Yellow
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-pedido"

Write-Host "`n4. Publicando evento..." -ForegroundColor Yellow
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/publicar-evento -d 'evento=pedido.criado'"

Write-Host "`n=== Teste Concluído! ===" -ForegroundColor Green
```

**Executar:**
```powershell
.\teste-rapido.ps1
```

---

## 📝 Alternativa: Usar Invoke-WebRequest

Se preferir usar comandos PowerShell nativos:

### Enviar Mensagem

```powershell
Invoke-WebRequest -Uri "http://localhost:8000/send-message" -Method POST -Body "mensagem=Teste PowerShell"
```

### Receber Mensagem

```powershell
Invoke-WebRequest -Uri "http://localhost:8000/receive-message" -Method GET
```

### Ver resposta formatada (JSON)

```powershell
$response = Invoke-WebRequest -Uri "http://localhost:8000/receive-message" -Method GET
$response.Content | ConvertFrom-Json | ConvertTo-Json
```

---

## ✅ Checklist Pré-Teste

Antes de executar comandos, verifique:

```powershell
# 1. Docker está rodando?
docker version

# 2. Containers estão up?
docker-compose ps

# 3. Laravel respondendo?
docker-compose exec laravel_app bash -c "curl http://localhost"

# 4. RabbitMQ respondendo?
Start-Process "http://localhost:15672"
```

Se todos retornarem OK, pode testar! ✅

---

## 🆘 Problemas Comuns

### Erro: "Cannot bind parameter 'Headers'"

**Causa:** Usando `curl` direto no PowerShell

**Solução:** Use `docker-compose exec laravel_app bash -c "curl ..."`

### Erro: "docker-compose: command not found"

**Causa:** Docker não instalado ou não no PATH

**Solução:** 
```powershell
# Verificar instalação
docker --version
docker-compose --version
```

### Erro: "No such container"

**Causa:** Containers não estão rodando

**Solução:**
```powershell
docker-compose up -d
```

---

## 📚 Próximos Passos

- 🧪 [`TESTE_RAPIDO.md`](TESTE_RAPIDO.md) - Guia de teste em 5 minutos
- 📦 [`MENSAGENS_JSON.md`](MENSAGENS_JSON.md) - Mensagens JSON completas
- 👥 [`GUIA_MULTIPLOS_CONSUMIDORES.md`](GUIA_MULTIPLOS_CONSUMIDORES.md) - Work Queue vs Pub/Sub

---

**Agora você tem todos os comandos prontos para Windows PowerShell!** 💻✨
