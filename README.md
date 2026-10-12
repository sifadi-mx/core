# sifadi/core

> 🇲🇽 **Made for Mexico — read this first.**
> This is the official PHP SDK for **Sifadi**, a Mexican electronic-invoicing service
> (**CFDI 4.0 / SAT**). It exists **exclusively to issue tax invoices in Mexico**, so its
> documentation, examples and field names are written in **Spanish** on purpose — that is
> the language of the SAT and of the Mexican businesses and developers who use it.
> If you are not issuing Mexican CFDI, this package is not for you.

---

SDK de PHP (**framework-agnostic**) para la **API de Sifadi**: timbra CFDI 4.0, cancela, descarga el XML/PDF y consulta tu saldo de timbres — sin armar las peticiones HTTP a mano.

> ¿Usas Laravel? Este es el core. El wrapper con ServiceProvider, Facade y config vive en **[`sifadi/laravel`](https://packagist.org/packages/sifadi/laravel)** (depende de este paquete).

## Requisitos

- PHP **8.1 o superior**
- Una cuenta de Sifadi y un **token**: un *access token* de OAuth ("Conectar con Sifadi") o una **API key** personal (Sifadi → Ajustes → API Keys).

## Instalación

```bash
composer require sifadi/core
```

## Cómo usarlo

Crea el cliente con tu token (la URL base es opcional; por defecto apunta a producción):

```php
use Sifadi\Core\SifadiClient;

$sifadi = new SifadiClient('TU_ACCESS_TOKEN');
```

### Crear y timbrar un CFDI de Ingreso

Las claves van **en español, como el SAT**. Sifadi calcula importes, subtotal, impuestos y total — **no los mandes**.

```php
$cfdi = $sifadi->invoices()->create([
    'serie' => 'G',
    'folio' => '1001',
    'tipo_de_comprobante' => 'I',
    'exportacion' => '01',
    'forma_pago' => '01',
    'metodo_pago' => 'PUE',
    'moneda' => 'MXN',
    'emisor' => ['rfc' => 'EKU9003173C9'],
    'receptor' => [
        'rfc' => 'XAXX010101000',
        'nombre' => 'PUBLICO EN GENERAL',
        'domicilio_fiscal_receptor' => '66039',
        'regimen_fiscal_receptor' => '616',
        'uso_cfdi' => 'S01',
    ],
    'conceptos' => [[
        'clave_prod_serv' => '01010101',
        'clave_unidad' => 'ACT',
        'cantidad' => '1',
        'descripcion' => 'Venta',
        'valor_unitario' => '589.00',
        'objeto_imp' => '02',
        'traslados' => [
            ['impuesto' => '002', 'tipo_factor' => 'Tasa', 'tasa_o_cuota' => '0.160000'],
        ],
    ]],
], idempotencyKey: '4b0f-...'); // recomendado: hace que reintentar "Timbrar" sea seguro

echo $cfdi['uuid'];                            // folio fiscal del SAT
$xml = $sifadi->invoices()->xml($cfdi['id']);  // XML timbrado
$pdf = $sifadi->invoices()->pdf($cfdi['id']);  // bytes del PDF
```

### Otras llamadas

```php
// Emisores (RFCs) de la cuenta — checa que su CSD esté vigente antes de timbrar
$sifadi->issuers()->list();
$sifadi->issuers()->find($id);

// Saldo de timbres ("sin saldo, no se timbra")
$sifadi->stamps()->balance();     // { available, included, recharge }
$sifadi->stamps()->available();   // solo el número usable ahora

// Ciclo de vida del CFDI
$sifadi->invoices()->createDraft([...]);   // crea borrador (opciones.timbrar = false)
$sifadi->invoices()->sign($id);            // firma un borrador con el CSD
$sifadi->invoices()->stamp($id);           // timbra un CFDI firmado
$sifadi->invoices()->cancel($id, '02');    // cancela (motivo 01 requiere uuid_sustitucion)
$sifadi->invoices()->stampInfo($id);       // uuid, sello_sat, no_certificado_sat, qr_code

// Identidad + plan del token
$sifadi->me();
```

### Factura global (público en general)

Agrega el bloque `informacion_global` y el receptor genérico:

```php
$cfdi = $sifadi->invoices()->create([
    'tipo_de_comprobante' => 'I',
    'exportacion' => '01',
    'metodo_pago' => 'PUE',
    'moneda' => 'MXN',
    'informacion_global' => ['periodicidad' => '04', 'meses' => '05', 'año' => 2026],
    'emisor' => ['rfc' => 'EKU9003173C9'],
    'receptor' => [
        'rfc' => 'XAXX010101000', 'nombre' => 'PUBLICO EN GENERAL',
        'domicilio_fiscal_receptor' => '66039',
        'regimen_fiscal_receptor' => '616', 'uso_cfdi' => 'S01',
    ],
    'conceptos' => [[/* ... */]],
]);
```

## Manejo de errores

Cada error de la API se lanza como una **excepción tipada** (todas extienden `Sifadi\Core\Exceptions\SifadiException`):

| Excepción | HTTP | Cuándo |
|---|---|---|
| `AuthenticationException` | 401 | Token ausente / inválido / expirado |
| `InsufficientStampsException` | 402 | Sin saldo de timbres (`->available()`, `->required()`) |
| `AuthorizationException` | 403 | Falta el scope o acción no permitida |
| `NotFoundException` | 404 | El emisor/recurso no existe en la cuenta |
| `ConflictException` | 409 | El emisor no tiene CSD vigente |
| `ValidationException` | 422 | Validación (`->errors()` con el detalle por campo) |
| `RateLimitException` | 429 | Límite de peticiones (`->retryAfter()`) |
| `PacException` | 502 | El PAC/SAT rechazó el timbrado o la cancelación |
| `ConnectionException` | — | No hubo respuesta (red / timeout / TLS) |

```php
use Sifadi\Core\Exceptions\ValidationException;
use Sifadi\Core\Exceptions\InsufficientStampsException;

try {
    $cfdi = $sifadi->invoices()->create($document);
} catch (ValidationException $e) {
    foreach ($e->errors() as $campo => $mensajes) { /* muéstralos al usuario */ }
} catch (InsufficientStampsException $e) {
    // avisa: recarga timbres. $e->available(), $e->required()
}
```

## Configuración

```php
new SifadiClient($token, 'https://app.sifadi.test', [
    'timeout' => 30,                 // segundos
    'user_agent' => 'mi-app/1.0',
    // 'client' => $guzzleClient,    // inyecta tu propio cliente Guzzle (tests, proxies…)
]);
```

## Alcance (v1)

CFDI de **Ingreso** (`tipo_de_comprobante = I`), incluida la **factura global** (bloque `informacion_global`). Firmar/timbrar por separado, cancelar, descargar XML/PDF, listar RFCs y consultar saldo. Más tipos de comprobante llegarán conforme la API los exponga.

## Pruebas

```bash
composer install
composer test   # PHPUnit
composer lint   # Laravel Pint
```

## Licencia

MIT.
