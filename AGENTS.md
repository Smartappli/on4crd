# ON4CRD contributor guidance

## Project overview

ON4CRD is a PHP 8.5 radio-club website. Its public entry point is `index.php`;
page controllers live in `pages/`, shared application helpers in `app/`,
configuration in `config/`, and database schema in `schema/`.

## Working conventions

- Preserve the existing procedural PHP style and start new PHP files with
  `declare(strict_types=1);`.
- Keep user-facing text compatible with the French, English, Dutch, and German
  translation workflow in `app/i18n/`.
- Treat authentication, CSRF, uploads, CSP, and database access as security
  sensitive. Reuse existing helpers and parameterized queries rather than
  introducing parallel implementations.
- Do not commit secrets or local configuration. Use
  `config/config.sample.php` as the configuration template; `config/config.php`
  is environment-specific.
- Make narrowly scoped changes and add or update PHPUnit tests for changed
  behavior when practical.

## Validation

Run the checks relevant to the change before handing it off:

```bash
composer lint
composer duplication
composer test
composer analyse
```

For browser-facing changes, also run `npm run test:e2e`. Run
`npm run test:selenium` only against a disposable local or staging database;
admin scenarios can create and delete test data. `./scripts/check-tools.sh`
provides a toolbox smoke check.

## Local development

Use `docker compose up --build` for the full local stack. It initializes MySQL
from `schema/schema.sql`. Copy or generate local configuration as documented in
the README; never overwrite another developer's existing `config/config.php`.
