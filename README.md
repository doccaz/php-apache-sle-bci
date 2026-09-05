# php-apache-sle-bci

[![Build and publish image](https://github.com/doccaz/php-apache-sle-bci/actions/workflows/build.yml/badge.svg)](https://github.com/doccaz/php-apache-sle-bci/actions/workflows/build.yml)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE)

A small, self-contained PHP + Apache sample built on SUSE's **SLE BCI
php-apache** base image, replacing the older
[`php-apache`](https://github.com/doccaz/sle-bci-containers/tree/main/php-apache)
example in [`sle-bci-containers`](https://github.com/doccaz/sle-bci-containers)
with an updated, leaner mold shared with the other `*-sle-bci` samples
([`aspnet-8-sle-bci`](https://github.com/doccaz/aspnet-8-sle-bci),
[`wildfly-sle-bci`](https://github.com/doccaz/wildfly-sle-bci), ...).

## What this is

The original `php-apache` sample layered on a lot of bespoke, subscription-gated
plumbing (an `SUSEConnect` registration, the `sle-module-legacy` extension, a
Microsoft package repo for `mssql-tools`, an Oracle Instant Client RPM
install) just to demonstrate PECL drivers most PHP apps never touch. This
version drops all of that: everything it installs comes straight from the
public repo the `bci/php-apache` image already has configured, no SUSE
subscription needed.

- Base image: `registry.suse.com/bci/php-apache` — Apache + `mod_php`,
  pre-loaded with `curl`, `mbstring`, `openssl`, `session`, `zip`
- Extra extensions layered on top: `pdo` + `sqlite` (for the sample app's
  visit counter), `opcache`, `gd`, `intl` — the last two are literally the
  upstream image's own "how to add extensions" example
- [Composer](https://getcomposer.org/), installed via the official
  installer script with its signature verified before it runs
- A tiny sample app (`app/index.php`) that reads/writes a SQLite database
  through `PDO` on every request — proof the container's writable storage
  and the `pdo_sqlite` extension both actually work, not just that PHP boots
- `app/phpinfo.php` (classic `phpinfo()`) and `app/healthz.php` (JSON health
  endpoint listing loaded extensions), for parity with the other samples in
  this repo

## Supported versions

[`versions.json`](versions.json) is the source of truth: it lists every PHP
tag this repo builds and which one gets the floating `latest` tag. CI reads
this file and builds one image per entry.

| PHP tag | Image tags |
|---|---|
| `8` | `8`, `latest` |

`bci/php-apache` currently only publishes a single major-version tag (`8`,
pinned to whatever PHP 8.3.x point release SUSE ships that month — there's
no per-minor `8.1`/`8.2`/`8.3` split upstream), so `versions.json` has one
entry today. It's still a list, and CI still drives entirely off it, so
adding a second major version later (once/if SUSE publishes one) is just a
new entry — see "Updating" below.

## Layout

```
Dockerfile              # parameterized image definition (PHP_TAG build arg)
versions.json           # source of truth: which PHP tags get built
app/
  index.php             # sample page: extension list + SQLite visit counter
  phpinfo.php           # classic phpinfo()
  healthz.php           # JSON health check
```

## Pulling the pre-built image

Every push to `main` builds and publishes all versions listed in
`versions.json` to GitHub Container Registry via
[`.github/workflows/build.yml`](.github/workflows/build.yml):

```bash
podman pull ghcr.io/doccaz/php-apache-sle-bci:latest
podman pull ghcr.io/doccaz/php-apache-sle-bci:8      # exact major tag
```

## Building locally

```bash
podman build --build-arg PHP_TAG=8 -t php-apache-sle-bci:8 .
```

Omitting the build arg falls back to the `Dockerfile` default (`PHP_TAG=8`,
currently the only tag `bci/php-apache` publishes).

## Running

```bash
podman run -d --name php-demo -p 8080:80 ghcr.io/doccaz/php-apache-sle-bci:latest
```

Then:

```bash
curl http://localhost:8080/            # visit counter, increments each call
curl http://localhost:8080/healthz.php # JSON: php version, sapi, loaded extensions
```

Open http://localhost:8080/phpinfo.php in a browser for the full `phpinfo()`
output.

### Mounting your own app instead

Like the base image itself, you can skip the build entirely and mount your
own code straight into the document root:

```bash
podman run -d -p 8080:80 -v ./your-app/:/srv/www/htdocs:Z \
  registry.suse.com/bci/php-apache:8
```

## Updating

### Adding a new PHP version

1. Confirm SUSE has actually published a new major tag for
   `bci/php-apache` (check `registry.suse.com/repositories/bci-php-apache`
   or query the registry's `tags/list` API — the upstream image has
   historically only ever carried a single `8` tag).
2. Build and smoke-test it locally **before** touching `versions.json`:
   ```bash
   podman build --build-arg PHP_TAG=<tag> -t php-apache-sle-bci:test .
   podman run -d --name php-test -p 18080:80 php-apache-sle-bci:test
   curl -i http://127.0.0.1:18080/
   curl -i http://127.0.0.1:18080/healthz.php
   podman rm -f php-test
   ```
3. Add an entry to `versions.json` with the confirmed `php_tag` and `major`
   tag string, moving `"latest": true` to whichever entry should own the
   floating `latest` tag (only one entry should have it).
4. Push to `main` — CI builds and publishes every entry in `versions.json`,
   including the new one.

### Replacing the sample app

Drop your own PHP files into `app/` (replacing or adding to the existing
ones) and rebuild — the Dockerfile copies everything in that directory into
`/srv/www/htdocs`. If your app needs extra extensions, add them to the
`zypper install` line in the Dockerfile; check what's available first with
`zypper se php8` inside the base image (no SUSE subscription required for
anything in the base image's own repo).

### Rebasing on a newer SLE BCI point release

SUSE periodically republishes `bci/php-apache` against newer point releases.
Nothing to change here — the next CI run on `main` rebuilds against
whatever the tag currently resolves to. To force a local rebuild against the
latest point release:

```bash
podman pull registry.suse.com/bci/php-apache:8
podman build --pull --build-arg PHP_TAG=8 -t php-apache-sle-bci:8 .
```

## Verified

- Builds cleanly with no SUSE subscription/registration of any kind
- `gd`, `intl`, `pdo`, `pdo_sqlite`, `opcache` all load correctly alongside
  the base image's built-in `curl`/`mbstring`/`openssl`/`session`/`zip`
- Composer installs and reports its version correctly
- `/` persists and increments a request counter across repeated calls via
  `PDO` + SQLite, confirming both the extension and the container's
  writable storage work (the base image runs Apache's worker processes as
  `wwwrun`, not root — the app's `data/` directory is `chown`ed to
  `wwwrun:www` in the Dockerfile so that user can write to it, no
  world-writable permissions needed)
- `/healthz.php` returns valid JSON with the full loaded-extension list
- `/phpinfo.php` renders the standard `phpinfo()` page

## CI/CD

[`.github/workflows/build.yml`](.github/workflows/build.yml) reads
`versions.json`, then builds and pushes one image per entry to
`ghcr.io/doccaz/php-apache-sle-bci` on every push to `main` and on manual
dispatch; pull requests build every version but don't push. The workflow
uses the repo's own `GITHUB_TOKEN`, so no extra secrets are needed.

The published package inherits this repository's visibility, so it's
pullable anonymously — no `podman login`/`docker login` needed. If that
ever changes, visibility can be set explicitly from the package's GitHub
settings page
(`github.com/doccaz/php-apache-sle-bci/pkgs/container/php-apache-sle-bci` →
**Package settings** → **Change visibility**).

## License

Licensed under the [GNU General Public License v3.0](LICENSE).
