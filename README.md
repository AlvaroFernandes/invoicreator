# Invoice Creator

Invoice Creator is a PHP-based web application for generating and managing invoices. It is built with modern PHP tooling and follows common PHP coding and testing standards. The repository includes development tools (PHPCS, Psalm, PHPUnit) and optional Docker support for easy local development.

**Key Features**

- **Invoice Generation:** Create and store invoices via the web UI.
- **Configuration:** Modular configuration via `config/` and `module/` directories.
- **Developer Tools:** Integrates `phpcs`, `psalm`, and `phpunit` for quality and correctness checks.
- **Docker Support:** `docker-compose.yml` is included for quick containerized development.

**Requirements**

- **PHP:** 8.0+ (verify `composer.json` for exact constraint). 
- **Composer:** For dependency management.
- **Extensions:** Typical extensions like `ext-json`, `ext-mbstring` (see `composer.json`).
- **Optional:** Docker & Docker Compose for containerized development.

**Quick Start (Local)**

1. Clone the repository and install dependencies:

```powershell
composer install
```

2. Create or copy local configuration if needed (see `config/autoload/`):

```powershell
copy .\config\autoload\local.php.dist .\config\autoload\local.php
# Edit the copied file as needed
```

3. Start the PHP built-in server (for development):

```powershell
php -S localhost:8080 -t public
```

4. Open your browser at `http://localhost:8080`.

**Using Docker (optional)**

If you prefer Docker, run:

```powershell
docker-compose up --build
```

This will build and start containers configured in `docker-compose.yml`.

**Running Tests & Static Analysis**

- Run unit tests with `phpunit`:

```powershell
vendor\bin\phpunit
```

- Run static analysis with `psalm`:

```powershell
vendor\bin\psalm
```

- Check coding standards with `phpcs`:

```powershell
vendor\bin\phpcs
```

- Fixable issues with `phpcbf`:

```powershell
vendor\bin\phpcbf
```

**Project Structure (important paths)**

- `public/` — Web document root (entry point `public/index.php`).
- `module/` — Application modules and PHP source.
- `config/` — Configuration files and autoloaded configs.
- `data/cache/` — Cache files (gitignored in most setups).
- `vendor/` — Composer dependencies (do not commit manual changes).

**Configuration**

Edit `config/autoload/*.php` or create environment-specific files in `config/autoload/` (for local overrides copy `*.dist` files to remove the `.dist` suffix).

**Coding & Contribution Guidelines**

- Follow PSR-12 and run `phpcs` before submitting changes.
- Use `phpunit` for tests; add tests for new features.
- Open issues and pull requests with a clear description and steps to reproduce.

**CI / Automation**

This repository already includes configuration files such as `phpcs.xml`, `phpunit.xml.dist`, and `psalm.xml` to help CI pipelines run checks consistently. Add a CI workflow to run `composer install`, `vendor/bin/phpunit`, `vendor/bin/psalm`, and `vendor/bin/phpcs` on pull requests.

**License**

Project license: see `LICENSE.md` in the repository root.

**Contact / Maintainer**

For questions or contributions, open an issue or contact the maintainer listed in the repository metadata.

---

If you'd like, I can also:

- Add badges (CI, license) to the top of this `README.md`.
- Create a minimal `CONTRIBUTING.md` or a developer quick-start script.
- Add example environment/local configuration templates.
