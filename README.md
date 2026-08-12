# Microservicio WhatsApp

Microservicio para integrar WhatsApp Cloud API de Meta. Permite enviar y recibir mensajes mediante webhooks.

## 📋 Tabla de Contenidos

1. [Estructura del Proyecto](#estructura-del-proyecto)
2. [Configuración de Meta](#configuración-de-meta)
3. [Variables de Entorno (.env)](#variables-de-entorno-env)
4. [Rutas del API](#rutas-del-api)
5. [Webhook](#webhook)
6. [Instalación Local](#instalación-local)
7. [Deployment en Render](#deployment-en-render)

---

## 📁 Estructura del Proyecto

```
src/whatsap/
├── WhatsApp.php              # Script de demo/prueba (testing)
├── WhatsAppClient.php        # Cliente para la API de WhatsApp
├── WhatsAppController.php    # Controlador que intermedia entre webhook y cliente
└── WhatsAppWebhook.php       # Maneja webhooks entrantes de WhatsApp

storage/
└── webhooks/                 # Almacena eventos de webhooks (JSON)

Dockerfile                    # Configuración para Docker
index.php                     # Punto de entrada principal
.env.example                  # Plantilla de variables de entorno
```

### 📝 Descripción de Archivos Principales

**WhatsAppClient.php**
- Conecta con la API de WhatsApp Cloud
- Métodos para enviar textos y plantillas
- Gestiona las peticiones HTTP hacia Meta

**WhatsAppController.php**
- Intermediario entre webhook y cliente
- Simplifica la lógica de envío de mensajes
- Patrón MVC de control

**WhatsAppWebhook.php**
- Recibe eventos de WhatsApp en tiempo real
- Valida la firma de Meta (seguridad)
- Procesa mensajes entrantes

**index.php**
- Router principal de la aplicación
- Define las rutas del API
- Carga `.env` automáticamente

---

## ⚙️ Configuración de Meta

Sigue estos pasos para conectar tu app con WhatsApp.

### Paso 1: Crear Portafolio en Business Facebook

1. Ve a [business.facebook.com](https://business.facebook.com)
2. Click en "Crear cuenta" (si no tienes)
3. Ingresa nombre de empresa y datos
4. Confirma tu email
5. **Guarda el ID de tu Business Account** (lo necesitarás después)

### Paso 2: Crear App en Meta Developer

1. Ve a [developers.facebook.com](https://developers.facebook.com)
2. Abre tu Business Account
3. Click "Mis Apps" → "Crear aplicación"
4. Elige: **Tipo: Business**
5. Completa:
   - **Nombre de app**: `microservicio-whatsapp` (o lo que prefieras)
   - **Email de contacto**: Tu email
   - **App Purpose**: Elige "WhatsApp Business"
6. Click "Crear aplicación"

### Paso 3: Agregar WhatsApp al App

1. En tu app, busca "WhatsApp" en productos
2. Click "Configurar"
3. Selecciona tu Business Account
4. Aceptar términos

### Paso 4: Crear Usuario de Prueba

1. En tu app, ve a **Herramientas** → **Usuarios de prueba**
2. Click "Crear usuario de prueba"
3. Completa datos
4. **Guarda el número de prueba que te genera** (ej: `+1 (555) 157-5482`)

### Paso 5: Paso 1 - Test (Envío de Prueba)

1. Ve a **WhatsApp** → **Configuración básica** → **Paso 1. Probar**
2. Selecciona el número de prueba que creaste
3. Escribe un mensaje de prueba
4. Click "Enviar mensaje"
5. **Recibirás el mensaje en tu WhatsApp personal** ✅

Este paso prueba que tu app puede ENVIAR mensajes.

### Paso 6: Paso 2 - Configurar Webhook

1. Ve a **WhatsApp** → **Paso 2. Configuración de producción** → **Configurar Webhooks**

2. Completa los campos:

   **URL de devolución de llamada (Callback URL):**
   ```
   https://tu-dominio-en-render.onrender.com/webhook
   ```
   
   **Identificador de verificación (Verify Token):**
   ```
   tu_verify_token_aqui
   ```
   (Este debe coincidir con `WHATSAPP_VERIFY_TOKEN` en tu `.env`)

3. Click "Verificar y guardar"
   - Meta hará un GET a tu webhook para verificarlo
   - Si todo está bien, mostrará ✅ "Verificado"

4. Asegúrate de que el webhook está **VERIFICADO** (verde)

### Paso 7: Suscribirse al Evento "messages"

1. En la misma página de Webhooks, mira **"Campos de webhook"**
2. Busca `messages` en la lista
3. Si no tiene un checkbox azul "Suscrito", click en **"Suscribirse"**
4. Confirma que `messages` muestre estado "Suscrito" ✅

**Esto es CRÍTICO** - Sin suscribirse, no recibirás los eventos de mensajes.

---

## 🔐 Variables de Entorno (.env)

1. **Copia la plantilla:**
   ```bash
   cp .env.example .env
   ```

2. **Edita `.env` con TUS credenciales de Meta:**
   ```env
   WHATSAPP_PHONE_NUMBER_ID="tu_phone_number_id_aqui"
   WHATSAPP_ACCESS_TOKEN="tu_access_token_aqui"
   WHATSAPP_VERIFY_TOKEN="tu_verify_token_aqui"
   WHATSAPP_APP_SECRET="tu_app_secret_aqui"
   ```

### ¿De dónde saco cada valor?

| Variable | Dónde obtenerla | Formato |
|----------|-----------------|---------|
| `WHATSAPP_PHONE_NUMBER_ID` | Meta > WhatsApp > Settings > Phone Numbers | Números largos (ej: `123456789...`) |
| `WHATSAPP_ACCESS_TOKEN` | Meta > API Setup o Settings > User Access Tokens | String muy largo que empieza con `EAA...` |
| `WHATSAPP_VERIFY_TOKEN` | Tú lo configuras en Meta (Paso 2) - Puede ser cualquier string seguro | Cualquier combinación de números/letras |
| `WHATSAPP_APP_SECRET` | Meta > Settings > Basic > App Secret | String alfanumérico (meta te lo genera) |

**⚠️ IMPORTANTE:**
- **NUNCA** subas el `.env` a Git (está en `.gitignore`)
- En Render, agrega estas variables en: **Settings** → **Environment**
- Usa `.env.example` como plantilla para otros desarrolladores

---

## 🛣️ Rutas del API

### 1. Health Check (Verificar que está online)

```http
GET /
GET /health
```

**Respuesta:**
```json
{
  "success": true,
  "message": "PruebaMicroservicio está en línea",
  "timestamp": "2026-08-12T21:43:17+00:00"
}
```

---

### 2. Enviar Mensaje de Texto

```http
POST /send
Content-Type: application/json

{
  "to": "573188888888",
  "message": "¡Hola! Este es un mensaje de prueba"
}
```

**Parámetros:**
- `to` (string, requerido): Número de teléfono en formato internacional (sin `+`)
- `message` (string, requerido): Contenido del mensaje

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "data": {
    "status": 200,
    "data": {
      "messages": [
        {
          "id": "wamid.xxxxx",
          "message_status": "accepted"
        }
      ]
    }
  }
}
```

**Error (400):**
```json
{
  "success": false,
  "error": "Parámetros requeridos: to, message"
}
```

---

### 3. Webhook de WhatsApp (Recibir Mensajes)

```http
POST /webhook
Content-Type: application/json
X-Hub-Signature-256: sha256=abcdef123456...
```

**GET (Verificación de Meta):**
```
GET /webhook?hub_mode=subscribe&hub_verify_token=xxxxx&hub_challenge=yyyyy
```

Devuelve el `challenge` si el token es válido.

---

## 🔗 Webhook

### ¿Qué es?

Un webhook es un callback automático. Cuando algo sucede en WhatsApp (llega un mensaje), Meta te envía un POST a tu URL automáticamente.

### ¿Cómo funciona?

1. **Configuración:**
   - Le dices a Meta: "Cuando llegue un mensaje, envíame un POST a: `https://mi-app.com/webhook`"

2. **Verificación inicial:**
   - Meta hace un GET a `/webhook` con parámetros
   - Tu app responde con el `challenge`
   - Meta confirma que tu app existe y responde

3. **Eventos en tiempo real:**
   - Llega un mensaje a tu número
   - Meta envía un POST a `/webhook` con los datos del mensaje
   - Tu app procesa y responde con 200 OK

### ¿Qué datos recibo?

Ejemplo de evento que Meta te envía:

```json
{
  "object": "whatsapp_business_account",
  "entry": [
    {
      "id": "XXXX",
      "changes": [
        {
          "value": {
            "messaging_product": "whatsapp",
            "messages": [
              {
                "from": "573188888888",
                "id": "wamid.xxxxx",
                "timestamp": "1691865632",
                "type": "text",
                "text": {
                  "body": "Hola, esto es un mensaje de prueba"
                }
              }
            ],
            "contacts": [
              {
                "profile": {
                  "name": "Juan Quintero"
                },
                "wa_id": "573188888888"
              }
            ]
          }
        }
      ]
    }
  ]
}
```

### Validación de Firma (Seguridad)

Meta firma cada webhook con `X-Hub-Signature-256`:

```
X-Hub-Signature-256: sha256=abcdef123456789...
```

Tu app valida:
1. Calcula: `hash = HMAC-SHA256(body, APP_SECRET)`
2. Compara con la firma de Meta
3. Si no coincide → ❌ Rechaza (403 Forbidden)
4. Si coincide → ✅ Procesa

Esto garantiza que el POST viene realmente de Meta.

### Debugging el Webhook

Agregué `error_log()` en el código. Para ver los logs:

**En Render:**
- Ve a tu servicio
- Click "Logs"
- Busca `=== WEBHOOK POST RECIBIDO ===`

**Localmente (Docker):**
```bash
docker logs nombre_contenedor
```

---

## 🚀 Instalación Local

### Requisitos

- PHP 8.1+
- Docker (recomendado)
- Acceso a Meta Developer Console

### Sin Docker

1. **Clonar repo:**
   ```bash
   git clone https://github.com/tu-usuario/microservicio-whatsapp.git
   cd microservicio-whatsapp
   ```

2. **Crear `.env`:**
   ```bash
   cp .env.example .env
   # Edita .env con tus credenciales de Meta
   ```

3. **Instalar dependencias (opcional):**
   ```bash
   composer install
   ```

4. **Ejecutar servidor PHP:**
   ```bash
   php -S localhost:8000
   ```

5. **Probar:**
   ```bash
   curl http://localhost:8000/health
   ```

### Con Docker (Recomendado)

1. **Crear `.env`:**
   ```bash
   cp .env.example .env
   # Edita .env
   ```

2. **Construir imagen:**
   ```bash
   docker build -t microservicio-whatsapp .
   ```

3. **Ejecutar contenedor:**
   ```bash
   docker run -p 8080:80 microservicio-whatsapp
   ```

4. **Probar:**
   ```bash
   curl http://localhost:8080/health
   ```

---

## 🌐 Deployment en Render

### Pasos

1. **Sube tu repo a GitHub:**
   ```bash
   git add .
   git commit -m "Initial commit"
   git push origin main
   ```

2. **Ve a [render.com](https://render.com)**

3. **Crea nuevo "Web Service":**
   - Click "New" → "Web Service"
   - Conecta tu repo de GitHub
   - Selecciona rama `main`

4. **Configura el servicio:**
   - **Name**: `microservicio-whatsapp`
   - **Region**: Elige la más cercana
   - **Runtime**: Docker
   - **Build Command**: Automático
   - **Start Command**: Automático
   - **Plan**: Free (o el que prefieras)

5. **Agrega variables de entorno:**
   - Abre el servicio
   - Ve a **Settings** → **Environment**
   - Agrega cada variable de tu `.env`:
     ```
     WHATSAPP_PHONE_NUMBER_ID=tu_phone_number_id
     WHATSAPP_ACCESS_TOKEN=tu_access_token_aqui
     WHATSAPP_VERIFY_TOKEN=tu_verify_token
     WHATSAPP_APP_SECRET=tu_app_secret
     ```

6. **Deploy:**
   - Click "Deploy"
   - Espera a que termine (2-5 minutos)
   - Verás tu URL: `https://microservicio-whatsapp.onrender.com`

7. **Actualiza Meta:**
   - Ve a Meta for Developers
   - Paso 2 → Webhook URL:
     ```
     https://microservicio-whatsapp.onrender.com/webhook
     ```
   - Click "Verificar y guardar"

8. **Prueba:**
   ```bash
   curl https://microservicio-whatsapp.onrender.com/health
   ```

---

## 📱 Pruebas

### Test 1: Health Check

```bash
curl https://microservicio-whatsapp.onrender.com/health
```

### Test 2: Enviar Mensaje

```bash
curl -X POST https://microservicio-whatsapp.onrender.com/send \
  -H "Content-Type: application/json" \
  -d '{"to":"573188888888","message":"¡Hola desde el API!"}'
```

### Test 3: Webhook (Recibir)

1. Ve a Meta → Paso 1 → Envía un test
2. Revisa logs de Render
3. Deberías ver `=== WEBHOOK POST RECIBIDO ===`

---

## 🐛 Troubleshooting

### "Webhook not verified"

- ✅ Verifica que la URL es correcta
- ✅ Verifica que el token coincide con `WHATSAPP_VERIFY_TOKEN`
- ✅ Revisa logs: `Error: Firma inválida`

### "No recibo mensajes en el webhook"

- ✅ ¿El webhook muestra verde "Verificado"?
- ✅ ¿Está suscrito a `messages`?
- ✅ Revisa los logs de Render
- ✅ Escribe al número de prueba (no a otro número)

### "Access token inválido"

- ✅ Copia exactamente del Panel de Meta (sin espacios)
- ✅ En Render, copia sin comillas

---

## 📞 Contacto

Para soporte o reportar errores, abre un issue en GitHub.

---

**Última actualización:** 12 de Agosto, 2026
