# ⚡ Decisão Rápida: Qual Padrão Usar?

Guia rápido para escolher entre **Work Queue** e **Pub/Sub** em segundos.

---

## 🎯 Pergunta Única

### "Múltiplos serviços precisam REAGIR ao mesmo evento?"

```
┌─────────────────────────────────────────┐
│                                         │
│  Múltiplos serviços precisam reagir    │
│  ao mesmo evento?                       │
│                                         │
│         ┌─────────┐                     │
│         │  SIM    │                     │
│         └────┬────┘                     │
│              ↓                          │
│         📡 PUB/SUB                      │
│    (Broadcasting/Eventos)               │
│                                         │
│         ┌─────────┐                     │
│         │  NÃO    │                     │
│         └────┬────┘                     │
│              ↓                          │
│         📦 WORK QUEUE                   │
│    (Distribuição/Tarefas)               │
│                                         │
└─────────────────────────────────────────┘
```

---

## 📦 Use Work Queue Se:

✅ Quer **dividir trabalho** entre workers  
✅ Cada tarefa processada **uma vez**  
✅ Objetivo é **performance/paralelização**  
✅ Tarefa é **pesada** (emails, imagens, relatórios)  

### Exemplos Rápidos:
- Enviar 1000 emails → Work Queue
- Processar 500 imagens → Work Queue
- Gerar relatórios → Work Queue
- Importar dados → Work Queue

---

## 📡 Use Pub/Sub Se:

✅ Quer **notificar** múltiplos serviços  
✅ Mesmo evento precisa de **múltiplas reações**  
✅ Objetivo é **desacoplamento**  
✅ É uma **notificação de algo que aconteceu**  

### Exemplos Rápidos:
- Pedido criado → Pub/Sub (email + analytics + shipping)
- Usuário cadastrado → Pub/Sub (email + CRM + analytics)
- Pagamento aprovado → Pub/Sub (nota fiscal + email + desbloqueio)
- Post publicado → Pub/Sub (notificar + indexar + cache)

---

## 🎬 Cenários Práticos

### Cenário 1: Sistema de E-commerce

| Situação | Padrão | Motivo |
|----------|--------|--------|
| Processar 100 pedidos | 📦 Work Queue | Dividir carga entre workers |
| Pedido foi criado | 📡 Pub/Sub | Email + Analytics + Shipping reagem |
| Enviar emails de carrinho abandonado | 📦 Work Queue | Cada email processado uma vez |
| Produto em promoção | 📡 Pub/Sub | Notificar todos os serviços |
| Redimensionar fotos de produtos | 📦 Work Queue | Tarefa pesada, paralelizar |

### Cenário 2: Sistema de Blog

| Situação | Padrão | Motivo |
|----------|--------|--------|
| Gerar thumbnails de imagens | 📦 Work Queue | Tarefa pesada |
| Post publicado | 📡 Pub/Sub | Notificar + Indexar + Cache |
| Enviar newsletter semanal | 📦 Work Queue | Milhares de emails |
| Comentário aprovado | 📡 Pub/Sub | Notificar autor + Moderadores |

### Cenário 3: Sistema de Notificações

| Situação | Padrão | Motivo |
|----------|--------|--------|
| Enviar push notifications | 📦 Work Queue | Milhares de devices |
| Evento crítico detectado | 📡 Pub/Sub | Email + SMS + Slack + PagerDuty |
| Processar fila de SMS | 📦 Work Queue | Um SMS por vez |

---

## 🚨 Sinais de Que Você Escolheu Errado

### ❌ Você escolheu Work Queue mas:
- Apenas UM serviço está sendo notificado (os outros não sabem)
- Quer que múltiplos serviços reajam ao mesmo evento
- Está tendo que "duplicar" mensagens manualmente

**→ Mude para Pub/Sub!**

### ❌ Você escolheu Pub/Sub mas:
- Mesma tarefa está sendo processada múltiplas vezes
- Está enviando emails/notificações duplicadas
- Performance está ruim (todos fazem tudo)

**→ Mude para Work Queue!**

---

## ⚡ Cheat Sheet Ultra-Rápido

```
DIVIDIR trabalho?        → Work Queue
NOTIFICAR múltiplos?     → Pub/Sub

Uma vez só?              → Work Queue
Todos devem saber?       → Pub/Sub

Tarefa pesada?           → Work Queue
Evento aconteceu?        → Pub/Sub

Performance?             → Work Queue
Desacoplamento?          → Pub/Sub
```

---

## 🎯 Regra de Ouro

### Work Queue = VERBOS
- **Enviar** email
- **Processar** pedido
- **Gerar** relatório
- **Redimensionar** imagem

### Pub/Sub = EVENTOS
- Pedido **foi criado**
- Usuário **se cadastrou**
- Pagamento **foi aprovado**
- Post **foi publicado**

**Se você descreveu com VERBO → Work Queue**  
**Se você descreveu com EVENTO → Pub/Sub**

---

## 📝 Teste Rápido

Qual padrão usar?

1. **Enviar 5000 emails de marketing**
   <details>
   <summary>Resposta</summary>
   📦 Work Queue - Tarefa pesada, dividir entre workers
   </details>

2. **Usuário criou conta → Enviar email + Atualizar CRM + Registrar analytics**
   <details>
   <summary>Resposta</summary>
   📡 Pub/Sub - Múltiplos serviços reagem ao mesmo evento
   </details>

3. **Processar upload de 100 imagens**
   <details>
   <summary>Resposta</summary>
   📦 Work Queue - Tarefa pesada, paralelizar processamento
   </details>

4. **Pagamento aprovado → Gerar nota fiscal + Enviar recibo + Desbloquear conteúdo**
   <details>
   <summary>Resposta</summary>
   📡 Pub/Sub - Evento único, múltiplas reações
   </details>

5. **Gerar 50 relatórios PDF**
   <details>
   <summary>Resposta</summary>
   📦 Work Queue - Tarefa pesada, um relatório por worker
   </details>

---

## 🔄 Posso Usar Ambos?

**SIM!** É super comum usar ambos no mesmo sistema:

### Exemplo: Sistema de Pedidos

```
1. Cliente faz pedido
   ↓
2. 📡 PUB/SUB: "Pedido criado"
   ├─→ Email Service: Envia confirmação
   ├─→ Analytics: Registra venda
   ├─→ Invoice Service: Gera nota fiscal
   └─→ Shipping Service: Cria etiqueta
   
3. Shipping Service precisa gerar 100 etiquetas
   ↓
4. 📦 WORK QUEUE: "Gerar etiquetas"
   ├─→ Worker 1: Processa 33 etiquetas
   ├─→ Worker 2: Processa 33 etiquetas
   └─→ Worker 3: Processa 34 etiquetas
```

**Pub/Sub para notificar → Work Queue para processar!**

---

## 🎓 Resumo de 10 Segundos

**Work Queue:**
- Dividir trabalho
- Performance
- Uma vez só
- Tarefas pesadas

**Pub/Sub:**
- Notificar todos
- Eventos
- Múltiplas reações
- Desacoplamento

---

## 📚 Próximos Passos

- 📖 Entenda melhor: [`GUIA_MULTIPLOS_CONSUMIDORES.md`](GUIA_MULTIPLOS_CONSUMIDORES.md)
- 🧪 Teste na prática: [`TESTE_RAPIDO.md`](TESTE_RAPIDO.md)
- 💻 Comandos Windows: [`COMANDOS_WINDOWS.md`](COMANDOS_WINDOWS.md)

---

**Ainda em dúvida? Use esta regra:**

> "Se remover um consumer quebra o sistema → Pub/Sub  
> Se adicionar mais consumers melhora performance → Work Queue"

🎯 **Escolha o padrão certo e seu sistema funcionará perfeitamente!**
