# Changelog

All notable changes to `sifadi/core` are documented here.
This project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- `SifadiClient` entry point with `invoices()`, `issuers()`, `stamps()` and `me()`.
- Invoices: `create()` (create + stamp), `createDraft()`, `sign()`, `stamp()`,
  `cancel()`, `stampInfo()`, `xml()`, `pdf()`, with optional `Idempotency-Key`.
- Issuers: `list()`, `find()`. Stamps: `balance()`, `available()`.
- Typed exceptions mapped to the API status codes (401/402/403/404/409/422/429/502)
  plus `ConnectionException` for transport failures.
- Guzzle-based transport with an injectable client.
