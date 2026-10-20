# Development

## Requirements

PHP 8.1 or later and Composer, or only Docker. Dependencies are installed into `.Build/`, which is
neither committed nor packaged.

## Test

```bash
composer install && composer test
```

runs the unit tests. Locally without PHP on the host:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 sh -c 'composer install && composer test'
```

They cover the verification of CaptchaFox answers, the language mapping and the captcha service, and run
on every push and pull request (GitHub Actions). End-to-end tests against the TYPO3 versions of this
line run before every release.
