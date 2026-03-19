# MyAdmin ZoneMTA Mail Plugin

[![Tests](https://github.com/detain/myadmin-zonemta-mail/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-zonemta-mail/actions/workflows/tests.yml)
[![License: LGPL-2.1](https://img.shields.io/badge/License-LGPL%202.1-blue.svg)](https://opensource.org/licenses/LGPL-2.1)

A MyAdmin plugin that provides ZoneMTA-based mail service provisioning and lifecycle management. This plugin integrates with MongoDB for SMTP user account management and the MyAdmin event system for service activation, reactivation, deactivation, and termination workflows.

## Features

- ZoneMTA mail service provisioning via MongoDB user management
- Full service lifecycle support (activate, reactivate, deactivate, terminate)
- IP change management for mail services
- Configurable settings for ZoneMTA, ClickHouse, MySQL, and rSPAMd backends
- API endpoint registration for automated ZoneMTA login
- MXToolBox integration support

## Requirements

- PHP 8.2 or higher
- ext-curl
- MongoDB PHP driver (for production use)
- Symfony EventDispatcher 5.x

## Installation

```bash
composer require detain/myadmin-zonemta-mail
```

## Testing

```bash
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the [LGPL-2.1](LICENSE).
