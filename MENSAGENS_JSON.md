# 📦 Mensagens JSON - Guia Completo

## 🎯 Visão Geral

Agora você pode enviar **objetos complexos** nas mensagens RabbitMQ, não apenas strings simples!

### Diferenças:

| Mensagem Simples | Mensagem JSON |
|-----------------|---------------|
| `"Olá mundo"` | `{"cliente": "João", "valor": 99.90}` |
| String única | Objeto com propriedades |
| Sem estrutura | Estruturado e tipado |

## 📝 Tipos de Mensagens JSON

### 1. **Tarefa** (Task)
Usado para processar trabalhos em segundo plano

```json
{
  "tipo": "enviar_email",
  "dados": {
    "destinatario": "usuario@example.com",
    "assunto": "Bem-vindo!"
  },
  "prioridade": 5
}
```

### 2. **Evento** (Event)
Usado para notificar que algo aconteceu (Pub/Sub)

```json
{
  "evento": "pedido.criado",
  "payload": {
    "pedido_id": 1234,
    "cliente": "Maria Santos",
    "valor": 299.90
  },
  "timestamp": "2024-01-15T14:30:00Z"
}
```

### 3. **Customizado**
Você envia qualquer estrutura JSON

```json
{
  "qualquer": "coisa",
  "arrays": [1, 2, 3],
  "objetos": {
    "aninhados": true
  }
}
```

## 🚀 Como Usar

### **Opção 1: Via HTTP (Browser/Postman/Insomnia)**

#### Enviar Email
```powershell
# Windows PowerShell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/enviar-email -d 'email=joao@example.com&assunto=Ola&corpo=Mensagem'"
```

#### Processar Pedido
```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-pedido -d 'pedido_id=5678&nome=Maria&email=maria@example.com&cpf=123.456.789-00'"
```

#### Processar Imagem
```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-imagem -d 'arquivo=foto.jpg&operacao=redimensionar&largura=800&altura=600'"
```

#### Publicar Evento
```powershell
# Pedido criado
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/publicar-evento -d 'evento=pedido.criado'"

# Usuário cadastrado
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/publicar-evento -d 'evento=usuario.cadastrado'"

# Pagamento aprovado
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/publicar-evento -d 'evento=pagamento.aprovado'"
```

#### Enviar Dados Customizados
```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/customizado -d 'nome=João&idade=30&ativo=true'"
```

#### Receber Mensagem
```powershell
docker-compose exec laravel_app bash -c "curl http://localhost/json/receber"
```

### **Opção 2: Via Código PHP**

```php
use App\Services\RabbitMQJsonService;

$rabbit = new RabbitMQJsonService();

// Enviar tarefa de email
$rabbit->sendTask('enviar_email', [
    'destinatario' => 'usuario@example.com',
    'assunto' => 'Bem-vindo!',
    'corpo' => 'Obrigado por se cadastrar!'
], 5); // prioridade 5

// Enviar pedido completo
$rabbit->sendTask('processar_pedido', [
    'pedido_id' => 1234,
    'cliente' => [
        'nome' => 'João Silva',
        'email' => 'joao@example.com'
    ],
    'items' => [
        ['produto' => 'Notebook', 'preco' => 2999.90],
        ['produto' => 'Mouse', 'preco' => 49.90]
    ],
    'total' => 3049.80
], 8); // alta prioridade

// Publicar evento
$rabbit->sendEvent('pedido.criado', [
    'pedido_id' => 5678,
    'valor' => 299.90,
    'status' => 'aguardando_pagamento'
]);

// Enviar JSON customizado
$rabbit->sendJson([
    'qualquer_campo' => 'valor',
    'array' => [1, 2, 3],
    'objeto' => ['foo' => 'bar']
]);
```

### **Opção 3: Via Artisan (Terminal)**

```powershell
# Processar mensagens JSON
docker-compose exec laravel_app php artisan rabbitmq:json-worker
```

## 🎬 Exemplo Completo: Pedido de E-commerce

### 1. Enviar Pedido
```php
$rabbit->sendTask('processar_pedido', [
    'pedido_id' => 9876,
    'data' => '2024-01-15 14:30:00',
    
    'cliente' => [
        'nome' => 'João Silva',
        'email' => 'joao@example.com',
        'cpf' => '123.456.789-00',
        'telefone' => '11 98765-4321'
    ],
    
    'endereco_entrega' => [
        'rua' => 'Av. Paulista',
        'numero' => '1000',
        'complemento' => 'Apto 101',
        'bairro' => 'Bela Vista',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
        'cep' => '01310-100'
    ],
    
    'items' => [
        [
            'produto_id' => 1,
            'nome' => 'Notebook Dell',
            'quantidade' => 1,
            'preco_unitario' => 2999.90,
            'subtotal' => 2999.90
        ],
        [
            'produto_id' => 2,
            'nome' => 'Mouse Logitech',
            'quantidade' => 2,
            'preco_unitario' => 49.90,
            'subtotal' => 99.80
        ]
    ],
    
    'subtotal' => 3099.70,
    'desconto' => 49.90,
    'frete' => 15.00,
    'total' => 3064.80,
    
    'pagamento' => [
        'metodo' => 'cartao_credito',
        'bandeira' => 'Visa',
        'parcelas' => 3,
        'valor_parcela' => 1021.60
    ],
    
    'observacoes' => 'Entregar na portaria'
], 10); // Prioridade máxima
```

### 2. Worker Processa
O worker `RabbitMQJsonWorker` recebe e processa:

```
╔══════════════════════════════════════════════╗
║        📦 NOVA TAREFA: processar_pedido      ║
╚══════════════════════════════════════════════╝

Pedido ID: 9876
Cliente: João Silva (joao@example.com)
Items: 2 produtos
Total: R$ 3064.80

✅ Pedido processado com sucesso!
```

### 3. Resultado
O worker pode:
- Validar estoque
- Processar pagamento
- Enviar email de confirmação
- Atualizar banco de dados
- Publicar evento "pedido.processado"

## 📊 Tipos de Tarefas Suportadas

### `enviar_email`
```json
{
  "tipo": "enviar_email",
  "dados": {
    "destinatario": "usuario@example.com",
    "assunto": "Assunto do email",
    "corpo": "Conteúdo da mensagem",
    "template": "nome_do_template"
  }
}
```

### `processar_pedido`
```json
{
  "tipo": "processar_pedido",
  "dados": {
    "pedido_id": 1234,
    "cliente": { "nome": "...", "email": "..." },
    "items": [...],
    "total": 999.90
  }
}
```

### `gerar_relatorio`
```json
{
  "tipo": "gerar_relatorio",
  "dados": {
    "tipo_relatorio": "vendas",
    "periodo": "2024-01",
    "formato": "pdf"
  }
}
```

### `processar_imagem`
```json
{
  "tipo": "processar_imagem",
  "dados": {
    "arquivo": "foto.jpg",
    "operacao": "redimensionar",
    "largura": 800,
    "altura": 600
  }
}
```

## 📡 Tipos de Eventos Suportados

### `pedido.criado`
```json
{
  "evento": "pedido.criado",
  "payload": {
    "pedido_id": 1234,
    "valor": 299.90,
    "status": "aguardando_pagamento"
  }
}
```

### `usuario.cadastrado`
```json
{
  "evento": "usuario.cadastrado",
  "payload": {
    "usuario_id": 567,
    "nome": "João Silva",
    "plano": "premium"
  }
}
```

### `pagamento.aprovado`
```json
{
  "evento": "pagamento.aprovado",
  "payload": {
    "transacao_id": "txn_12345",
    "valor": 199.90,
    "metodo": "pix"
  }
}
```

## 🔄 Workflow Completo

```
┌─────────────┐         ┌──────────────┐         ┌──────────────┐
│   Cliente   │ ──POST──> │  Controller  │ ──JSON──> │   RabbitMQ   │
└─────────────┘         └──────────────┘         └──────────────┘
                                                          │
                                                          │
                                                          ▼
                                                  ┌──────────────┐
                                                  │    Worker    │
                                                  └──────────────┘
                                                          │
                                   ┌──────────────────────┼──────────────────────┐
                                   ▼                      ▼                      ▼
                            [Processar]           [Enviar Email]         [Atualizar BD]
```

## 🧪 Teste Rápido

### Passo 1: Iniciar Worker
```powershell
docker-compose exec laravel_app php artisan rabbitmq:json-worker
```

### Passo 2: Enviar Mensagem (outro terminal)
```powershell
docker-compose exec laravel_app bash -c "curl -X POST http://localhost/json/processar-pedido"
```

### Passo 3: Ver Resultado no Worker
```
╔══════════════════════════════════════════════╗
║     📦 NOVA TAREFA: processar_pedido         ║
╚══════════════════════════════════════════════╝

Pedido ID: 1234
Cliente: João Silva (joao@example.com)
Items: 2 produtos
Total: R$ 3049.80

✅ Pedido processado com sucesso!
```

## 🎯 Vantagens das Mensagens JSON

✅ **Estruturadas**: Objetos organizados com propriedades claras  
✅ **Tipadas**: Arrays, números, strings, booleanos  
✅ **Aninhadas**: Objetos dentro de objetos  
✅ **Metadata**: Timestamp, prioridade, tipo automático  
✅ **Validação**: Fácil validar estrutura esperada  
✅ **Extensível**: Adicionar novos campos sem quebrar código existente  

## 🆚 Comparação

### Antes (String Simples)
```php
// Enviar
$rabbit->sendMessage("processar_pedido:1234");

// Receber
$mensagem = $rabbit->getMessage(); // "processar_pedido:1234"
// Você precisa fazer parse manual!
```

### Agora (JSON)
```php
// Enviar
$rabbit->sendTask('processar_pedido', [
    'pedido_id' => 1234,
    'cliente' => 'João',
    'valor' => 299.90
]);

// Receber
$data = $rabbit->getJson();
// Array estruturado pronto para usar!
// $data['pedido_id'] => 1234
// $data['cliente'] => "João"
```

## 📚 Próximos Passos

1. ✅ Criar suas próprias tarefas customizadas
2. ✅ Adicionar novos eventos
3. ✅ Modificar o worker para suas necessidades
4. ✅ Integrar com seu banco de dados
5. ✅ Adicionar validação de dados
6. ✅ Implementar retry em caso de falha
7. ✅ Adicionar logs detalhados

## 💡 Dicas

- Use **prioridade** alta (8-10) para tarefas urgentes
- Use **eventos** para notificações (vários serviços interessados)
- Use **tarefas** para processamento (um único worker processa)
- Adicione **timestamp** para rastrear quando foi criado
- Inclua **IDs únicos** para rastreabilidade

---

✨ **Agora você pode enviar objetos complexos com facilidade!**
