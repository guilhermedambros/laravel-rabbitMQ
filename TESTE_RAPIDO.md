# 🧪 Teste Rápido - 5 Minutos

Guia para testar **todo o sistema** em apenas 5 minutos!

---

## ⏱️ Cronômetro: 5 Minutos

```
00:00 - Preparação
01:00 - Teste 1: Mensagem Simples
02:00 - Teste 2: Work Queue
03:00 - Teste 3: Pub/Sub
04:00 - Teste 4: JSON Worker
05:00 - ✅ Concluído!
```

---

## 🚀 Preparação (30 segundos)

### 1. Iniciar containers

```powershell
docker-compose up -d
```

### 2. Verificar status

```powershell
docker-compose ps
```

**Deve mostrar 3 containers rodando:**
- ✅ laravel_app
- ✅ rabbitmq
- ✅ composer

### 3. Aguardar inicialização (20-30 segundos)

```powershell
# Ver logs até aparecer "ready"
docker-compose logs -f laravel_app
```

**Pronto!** Pressione `CTRL+C` quando ver logs normais.

---

## 📨 Teste 1: Mensagem Simples (1 minuto)

### Enviar e Receber

**Enviar:**
```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Teste 1'"
```

**Resultado esperado:**
```json
{
  "status": "ok",
  "message": "Teste 1"
}
```

**Receber:**
```powershell
docker-compose exec laravel_app bash -c "curl http://localhost/receive-message"
```

**Resultado esperado:**
```json
{
  "status": "ok",
  "message": "Teste 1",
  "received_at": "2025-10-07T..."
}
```

### ✅ Verificação
- [ ] Mensagem enviada com sucesso
- [ ] Mensagem recebida com sucesso
- [ ] JSON válido retornado

---

## 📦 Teste 2: Work Queue - 3 Workers (1 minuto)

### Cenário
3 workers + 6 mensagens = Cada worker processa 2 mensagens

**Terminal 1:**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:worker
```

**Terminal 2 (nova janela):**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:worker
```

**Terminal 3 (nova janela):**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:worker
```

**Terminal 4 - Enviar 6 mensagens:**
```powershell
1..6 | ForEach-Object {
    docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=Task $_'"
}
```

### Resultado Esperado

```
Terminal 1: Task 1, Task 4
Terminal 2: Task 2, Task 5
Terminal 3: Task 3, Task 6
```

### ✅ Verificação
- [ ] Cada worker recebeu mensagens diferentes
- [ ] Distribuição round-robin funcionando
- [ ] Total: 6 mensagens processadas

**Para parar workers:** `CTRL+C` em cada terminal

---

## 📡 Teste 3: Pub/Sub - Broadcasting (1 minuto)

### Cenário
3 subscribers + 1 evento = Todos recebem a mesma mensagem

**Terminal 1:**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:subscribe
```

**Terminal 2 (nova janela):**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:subscribe
```

**Terminal 3 (nova janela):**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:subscribe
```

**Terminal 4 - Publicar evento:**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:publish "Evento de teste"
```

### Resultado Esperado

```
Subscriber 1: Evento de teste
Subscriber 2: Evento de teste
Subscriber 3: Evento de teste
```

### ✅ Verificação
- [ ] Todos os 3 subscribers receberam
- [ ] Todos receberam a MESMA mensagem
- [ ] Broadcasting funcionando

**Para parar subscribers:** `CTRL+C` em cada terminal

---

## 🎯 Teste 4: JSON Worker (1 minuto)

### Cenário
Worker JSON processa diferentes tipos de mensagens

**Terminal 1 - Iniciar worker:**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:json-worker
```

**Terminal 2 - Enviar tarefas:**

```powershell
# Tarefa: Enviar email
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/enviar-email"
```

**Aguarde 2 segundos, veja processamento no Terminal 1**

```powershell
# Tarefa: Processar pedido
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-pedido"
```

**Aguarde 2 segundos, veja processamento no Terminal 1**

```powershell
# Evento: Pedido criado
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/publicar-evento -d 'evento=pedido.criado'"
```

### Resultado Esperado (Terminal 1)

```
╔══════════════════════════════════════════════╗
║     📧 NOVA TAREFA: enviar_email             ║
╚══════════════════════════════════════════════╝
✅ Email enviado com sucesso!

╔══════════════════════════════════════════════╗
║     📦 NOVA TAREFA: processar_pedido         ║
╚══════════════════════════════════════════════╝
Pedido ID: 1234
Cliente: João Silva
✅ Pedido processado com sucesso!

╔══════════════════════════════════════════════╗
║     📡 EVENTO: pedido.criado                 ║
╚══════════════════════════════════════════════╝
✅ Evento processado!
```

### ✅ Verificação
- [ ] Worker processou tarefa de email
- [ ] Worker processou tarefa de pedido
- [ ] Worker processou evento
- [ ] Saída formatada e visual

---

## 🎨 Teste Bônus: RabbitMQ Management UI (30 segundos)

### Acessar Interface

```powershell
Start-Process "http://localhost:15672"
```

**Login:**
- Usuário: `guest`
- Senha: `guest`

### O Que Ver

1. **Queues** tab:
   - Veja fila `fila_teste`
   - Quantidade de mensagens
   - Taxa de mensagens/segundo

2. **Exchanges** tab:
   - Veja exchange `eventos` (fanout)

3. **Connections** tab:
   - Veja conexões ativas dos workers

### ✅ Verificação
- [ ] Interface abriu corretamente
- [ ] Login funcionou
- [ ] Fila `fila_teste` aparece
- [ ] Exchange `eventos` aparece

---

## 📊 Resumo do Teste

### O Que Foi Testado

| Teste | Padrão | Status |
|-------|--------|--------|
| Mensagem Simples | Basic | ✅ |
| Work Queue | Round-robin | ✅ |
| Pub/Sub | Broadcasting | ✅ |
| JSON Worker | Structured | ✅ |
| Management UI | Monitoring | ✅ |

### Funcionalidades Validadas

✅ **Envio de mensagens** - HTTP POST funcionando  
✅ **Recebimento não-bloqueante** - HTTP GET funcionando  
✅ **Work Queue** - Distribuição round-robin  
✅ **Pub/Sub** - Broadcasting para todos  
✅ **JSON Messages** - Mensagens estruturadas  
✅ **Workers** - Processamento em background  
✅ **RabbitMQ** - Servidor funcionando  
✅ **Docker** - Containers orquestrados  

---

## 🔄 Teste de Carga Rápido (Bônus)

### Enviar 100 mensagens rapidamente

```powershell
Write-Host "Enviando 100 mensagens..." -ForegroundColor Cyan

$stopwatch = [System.Diagnostics.Stopwatch]::StartNew()

1..100 | ForEach-Object {
    docker-compose exec laravel_app bash -c "curl -s -X POST http://localhost/send-message -d 'mensagem=Load $_'" > $null
    if ($_ % 10 -eq 0) {
        Write-Host "$_ mensagens enviadas..." -ForegroundColor Green
    }
}

$stopwatch.Stop()
Write-Host "✅ 100 mensagens enviadas em $($stopwatch.Elapsed.TotalSeconds) segundos" -ForegroundColor Green
```

### Processar com múltiplos workers

**Abra 5 terminais e execute em cada:**
```powershell
docker-compose exec laravel_app php artisan rabbitmq:worker
```

**Veja as 100 mensagens sendo distribuídas entre os 5 workers!**

---

## 🐛 Troubleshooting Rápido

### Problema: Mensagens não aparecem

**Verificar fila:**
```powershell
docker-compose exec rabbitmq rabbitmqctl list_queues
```

**Limpar fila:**
```powershell
docker-compose exec rabbitmq rabbitmqctl purge_queue fila_teste
```

### Problema: Worker não recebe mensagens

**Verificar se fila existe:**
```powershell
# Enviar uma mensagem primeiro
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/send-message -d 'mensagem=teste'"

# Depois iniciar worker
docker-compose exec laravel_app php artisan rabbitmq:worker
```

### Problema: Timeout no curl

**Causa:** Container ainda inicializando

**Solução:**
```powershell
# Aguardar mais 30 segundos
Start-Sleep -Seconds 30

# Tentar novamente
docker-compose exec laravel_app bash -c "curl http://localhost"
```

### Problema: Docker não responde

**Restart containers:**
```powershell
docker-compose restart
```

**Ou rebuild:**
```powershell
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

---

## ✅ Checklist Final

Após completar todos os testes:

### Funcionalidades Testadas
- [ ] Envio de mensagem simples
- [ ] Recebimento de mensagem simples
- [ ] Work Queue com múltiplos workers
- [ ] Pub/Sub com múltiplos subscribers
- [ ] JSON worker com diferentes tipos
- [ ] RabbitMQ Management UI

### Conceitos Validados
- [ ] Entendi diferença Work Queue vs Pub/Sub
- [ ] Sei quando usar cada padrão
- [ ] Workers funcionam sem travar
- [ ] Mensagens JSON estruturadas funcionam
- [ ] Docker e RabbitMQ orquestrados

### Próximos Passos
- [ ] Ler documentação completa
- [ ] Customizar para meu projeto
- [ ] Testar em produção
- [ ] Adicionar novos tipos de mensagens

---

## 🎓 Aprendizados em 5 Minutos

Se você completou todos os testes, agora você:

✅ Sabe enviar e receber mensagens  
✅ Entende Work Queue (distribuição)  
✅ Entende Pub/Sub (broadcasting)  
✅ Pode processar mensagens JSON estruturadas  
✅ Consegue rodar workers em background  
✅ Conhece o RabbitMQ Management UI  

**Parabéns! 🎉 Você está pronto para usar RabbitMQ!**

---

## 📚 Continuar Aprendendo

- 📖 [`GUIA_MULTIPLOS_CONSUMIDORES.md`](GUIA_MULTIPLOS_CONSUMIDORES.md) - Entenda os padrões a fundo
- ⚡ [`DECISAO_RAPIDA.md`](DECISAO_RAPIDA.md) - Quando usar cada padrão
- 💻 [`COMANDOS_WINDOWS.md`](COMANDOS_WINDOWS.md) - Todos os comandos Windows
- 📦 [`MENSAGENS_JSON.md`](MENSAGENS_JSON.md) - Mensagens JSON completas
- 🔧 [`FIXED_ISSUES.md`](FIXED_ISSUES.md) - Problemas e soluções

---

## ⏰ Tempo Total

```
✅ Preparação:        30s
✅ Teste 1:           1min
✅ Teste 2:           1min
✅ Teste 3:           1min
✅ Teste 4:           1min
✅ Management UI:     30s
────────────────────────
   TOTAL:            5min
```

**Sistema 100% funcional testado em apenas 5 minutos!** ⚡

---

## 🎯 Objetivo Alcançado!

Se todos os testes passaram:

✅ **Seu ambiente está configurado corretamente**  
✅ **Todos os componentes estão funcionando**  
✅ **Você entende os conceitos básicos**  
✅ **Está pronto para desenvolver!**

**Hora de construir algo incrível com RabbitMQ! 🚀**
