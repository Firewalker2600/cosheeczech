# CosheeCzech — Mini E-commerce Cart & Order API

A small HTTP API for managing shopping carts and orders, implemented from the
`ec-api.yml` OpenAPI specification.

* **PHP 8.4** (strict types, readonly value objects)
* **Slim 4** (PSR-7 / PSR-15) + **PHP-DI** (PSR-11) — framework-agnostic domain core
* **SQLite** persistence (PDO), transactional writes
* **Address geocoding** via the free OpenStreetMap Nominatim API (external API integration)
* **TDD** (60 tests), **PHPStan level 9** (zero errors)

---

## Requirements

The only hard requirement is **Docker** (with Compose). Everything else runs inside the
container on **PHP 8.4**. No PHP, Composer, or SQLite needed on the host.

## Quick start

```bash
docker compose up --build
```

The API is served on http://localhost:8000 (override the host port with `APP_PORT` if
8000 is already in use). On first request the SQLite schema and a small demo catalog are
created automatically.

> **Note (Fedora/RHEL):** the compose file mounts the project with `:z` to relabel it
> for SELinux. This is a no-op on other Linux/Windows/macOS hosts.

### Without Docker (optional)

Requires PHP 8.4 + `pdo_sqlite`:

```bash
composer install
php -S 0.0.0.0:8000 -t public
```

---

## API

Base URL: `http://localhost:8000`. All bodies are `application/json`.

| Method | Path              | Purpose                     | Success | Errors        |
|--------|-------------------|-----------------------------|---------|---------------|
| POST   | `/api/cart`       | Create an empty cart        | 200     | 500           |
| GET    | `/api/cart/{id}`  | Get a cart by ID            | 200     | 400, 404, 500 |
| POST   | `/api/cart/add`   | Add a product to a cart     | 200     | 400, 404, 500 |
| POST   | `/api/cart/remove`| Remove a product from a cart| 200     | 400, 404, 500 |
| POST   | `/api/orders`     | Create an order from a cart | 200     | 400, 404, 500 |
| GET    | `/api/orders`     | List all orders             | 200     | 500           |
| GET    | `/api/orders/{id}`| Get an order by ID          | 200     | 400, 404, 500 |

### Example flow

```bash
# 1. Create a cart
curl -X POST http://localhost:8000/api/cart
# → {"id":"…","items":[],"item_count":0,"total_quantity":0,"total":0}

# 2. Add a product (SKUs below are seeded on first run)
curl -X POST http://localhost:8000/api/cart/add \
  -H 'Content-Type: application/json' \
  -d '{"cart_id":"<id>","sku":"SOAP-LAVENDER","quantity":2}'

# 3. Create an order from the cart (address is geocoded into geo_location)
curl -X POST http://localhost:8000/api/orders \
  -H 'Content-Type: application/json' \
  -d '{"cart_id":"<id>","shipping_address":"Václavské náměstí 1, Prague"}'

# 4. Read it back
curl http://localhost:8000/api/orders/<order_id>
```

### Seeded demo catalog

| SKU            | Name                    | Price |
|----------------|-------------------------|-------|
| SOAP-LAVENDER  | Lavender soap           | 89.00 |
| SOAP-HONEY     | Honey & oat soap        | 95.00 |
| BALM-SHEA      | Shea butter balm        | 129.00 |
| SALT-BATH      | Bath salts              | 149.00 |
| OIL-LAVENDER   | Lavender essential oil  | 89.90 |

### Error shape

```json
{ "error": "Cart \"abc\" not found" }
```

The spec's missing `404` responses were added (cart/order/product not found). The
original `ec-api.yml` was updated in `spec/ec-api.yml`.

---

## Development

```bash
# Tests (60 tests, unit + API integration against in-memory SQLite)
docker compose run --rm app php vendor/bin/phpunit

# Static analysis (level 9, zero errors)
docker compose run --rm app php vendor/bin/phpstan analyse --memory-limit=1G
```

---

## Architecture

```
src/
├── Domain/                  pure domain — no framework, no DB imports
│   ├── Model/               value objects (Cart, Order, Money, Sku, Quantity, …)
│   ├── Repository/          tiny persistence interfaces (Cart/Order/Product)
│   ├── Service/             CartService, OrderService (stateless business logic)
│   └── Exception/           domain exceptions (mapped to 400/404)
├── Application/             ports for external concerns
│   └── GeocoderInterface.php
├── Infrastructure/          adapters
│   ├── Persistence/         SQLite repositories + schema + Row mapper
│   └── Geocoding/           NominatimGeocoder, NoopGeocoder
└── Http/                    Slim adapter (controllers, presenters, middleware, DI)
```

Design decisions worth calling out:

* **Money is integer minor units** (cents) in a `Money` value object. The legacy code
  accumulated `float` prices (`$price * $quantity`), which drifts (`0.1 + 0.2 ≠ 0.3`).
  Floats appear only at the JSON boundary.
* **Interfaces are tiny** (ISP): each repository is 2–3 methods; the geocoder is a
  single-method port. Adapters translate, they don't orchestrate.
* **Everything is injected** via constructor DI (PHP-DI autowiring). No `new` in services.
* **Domain exceptions → HTTP mapping** lives in one `ErrorHandlingMiddleware`, not
  scattered through controllers.
* **Type-safe persistence**: PDO returns `mixed` rows, so a `Row` mapper narrows every
  column to its declared type and fails loudly on schema drift instead of blind-casting.

### External API — address geocoding

The `Order.geo_location` field is the natural hook, so
orders geocode their shipping address via **Nominatim** (OpenStreetMap, free, no key):

```php
interface GeocoderInterface {
    public function geocode(string $address): ?GeoLocation;
}
```

* `NominatimGeocoder` — PSR-18 HTTP client + PSR-17 factory, best-effort (returns `null`
  on network failure; never breaks order creation).
* `NoopGeocoder` — offline/test fallback.

Disable geocoding (offline) with `GEOCODER=noop` in `docker-compose.yml` or the
environment. The `User-Agent` header is configurable via `GEOCODER_USER_AGENT`
(Nominatim's usage policy requires one).

## Commentary

**What I focused on, and why.** Correctness of the money handling first (the float bug is
the one that silently corrupts data), then a clean separation between a framework-free
domain core and a thin HTTP adapter — so the business logic is testable without
bootstrapping a web server. Type safety everywhere (`final readonly` value objects,
backed enums, PHPStan level 9), because the assignment is explicitly about PHP 8.4 and
"bug-free" code. API-first: the contract tests were written against the OpenAPI spec and
the missing 404s added to the spec before implementing.

**What I'd do next, given more time.** (1) Add a `ProductCatalog` service + product
management endpoints (the catalog is currently seed-only); (2) optimistic locking /
version column on carts; (3) a `CartId`/`OrderId` value object instead of raw strings;
(4) OpenAPI contract testing (e.g. validate responses against the schema in CI); (5)
ramsey/uuid instead of the hand-rolled v4 generator if the extra dependency is acceptable.
