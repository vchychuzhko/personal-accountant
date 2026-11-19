# Personal Accountant

![version](https://img.shields.io/badge/version-1.0-success)
[![demo](https://img.shields.io/badge/Demo-live-blue)](https://pa-demo.vchychuzhko.com)
[![dockerhub](https://img.shields.io/badge/Docker%20Hub-live-blue)](https://hub.docker.com/r/vchychuzhko/personal-accountant)

App to keep all balances and transactions organized.

> Do not trust anyone, selfhost

## Table of Contents

- [Deploy](#deploy)
  - [Requirements](#requirements)
  - [Docker](#docker)
  - [Set local variables](#set-local-variables)
  - [Install Packages](#install-packages)
  - [Generate Assets](#generate-assets)
  - [Create Admin User](#create-admin-user)
- [Usage](#usage)
  - [Web](#web)
  - [Entities](#entities)
  - [Commands](#commands)

## Deploy

### Requirements

- PHP 8.4
- Composer 2
- MariaDB 10.11

### Docker

[Docker documentation](.docker/README.md)

### Set local variables

Set database credentials in `.env.local` file.

```bash
cp .env .env.local
```

### Install Packages

```bash
composer install --no-dev
```

Drop `--no-dev` flag for development.

### Generate Assets

```bash
php bin/console assets:install --env=prod
php bin/console importmap:install --env=prod
php bin/console asset-map:compile --env=prod
```

Drop `--env=prod` flag for development.

[AssetMapper](https://symfony.com/doc/current/frontend/asset_mapper.html) is used.

### Create Admin User

To create the initial admin user, use this command:

```bash
php bin/console app:create-admin
```

## Usage

### Web

Panel is available by default at - https://your.domain/admin

* On the Configuration page you can set API keys for [Currency API](https://currencyapi.com/) and [EODHD API](https://eodhd.com/) services.
* Dashboard charts are cacheable and can be refreshed manually on the Configuration page.
* Apps page has a deposit calculator and currency converter.
* All dates are stored in UTC. You can set your timezone in Configuration.

### Entities

|    Name    | Description                                       |
|:----------:|---------------------------------------------------|
|  Currency  | Core entity that allows managing currencies       |
|  Balance   | Balance representation                            |
|   Income   | Income related to Balance, Deposit or Investement. |
|  Payment   | Payment related to Balance or Investment          |
|  Exchange  | Transfer between Balances                         |
|  Deposit   | Open or completed Deposit                         |
| Investment | Record of purchased shares                        |
|    Tag     | Payment tag for expenses organizing               |

* Creating Income, Payment, Exchange or Deposit will update the related Balance amount.
* On Deposit completion, the initial amount is returned to the Balance and Income with interest is created.
* Payment and Income can be linked to Investment, in that case share will be extracted from transaction name (e.g. `XTB GOOG 2.5` -> 2.5 shares of Google on XTB platform) 
* Income can have "Dividend" type, this will affect its display on Deposit and Investment pages.

### Commands

#### Sync Payment or Income IDs according to the created_at field

```bash
php bin/console app:sync-ids
```

Use `-i` or `-p` flags to specify income or payment entity.

*Database backup is recommended before running this command*

---

###### Built with [Symfony 7.4](https://symfony.com/doc/7.4/index.html)
