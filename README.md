# Personal Accountant

App to keep all balances and transactions organized.

> Do not trust anyone, selfhost

Demo - https://pa-demo.vchychuzhko.com

## Table of Contents

- [Deploy](#deploy)
  - [Requirements](#requirements)
  - [Set local variables](#set-local-variables)
  - [Install Packages](#install-packages)
  - [Generate Assets](#generate-assets)
  - [Create Admin User](#create-admin-user)
  - [Docker](#docker)
- [Usage](#usage)
  - [Web](#web)
  - [Entities](#entities)
  - [Commands](#commands)

## Deploy

### Requirements

- PHP 8.4
- Composer 2
- MariaDB 10.11
- Node 22

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

To create initial admin user, use this command:

```bash
php bin/console app:create-admin
```

### Docker

There is an official production-ready image you can use for local deployment - [Docker Hub](https://hub.docker.com/r/vchychuzhko/personal-accountant).

[docker-compose.prod.yml](./docker-compose.prod.yml) contains a ready-to-use configuration, that can be used in, for example, Portainer Stack.

Pay attention to default values:
- Database credentials: `app:app`
- Port: `8996`

## Usage

### Web

Panel is available by default at - https://&lt;your-localhost&gt;/admin

* On Configuration page you can set API key for [Currency API](https://currencyapi.com/) and [EODHD API](https://eodhd.com/) services.
* Dashboard charts are cacheable and can be refreshed manually on Configuration page.
* Apps page has deposit calculator and currency converter.
* All dates are stored in UTC. You can set your timezone for frontend representation and form inputs in Configuration.

### Entities

|    Name    | Description                                 |
|:----------:|---------------------------------------------|
|  Currency  | Core entity that allows managing currencies |
|  Balance   | Balance representation                      |
|   Income   | Income attached to Balance                  |
|  Payment   | Payment or transaction attached to Balance  |
|  Exchange  | Transfer between Balances                   |
|  Deposit   | Open or completed deposits                  |
| Investment | Records of purchased shares                 |
|    Loan    | Loans owed by person                        |
|    Tag     | Payment tags for expenses organizing        |

* Creating Income, Payment, Exchange or Deposit will update related Balance amount.
* On Deposit completion, initial amount is returned to the Balance and Income with interest is created.
* Payment and Income can be linked to Investment, in that case share will be extracted from transaction name (e.g. `XTB GOOG 2.5` -> 2.5 shares of Google on XTB platform) 

### Commands

#### Sync Payment or Income IDs according to created_at field

```bash
php bin/console app:sync-ids
```

Use `-i` or `-p` flags to specify income or payment entity.

*Database backup is recommended before running this command*

---

###### Built with [Symfony 7.4](https://symfony.com/doc/7.4/index.html)
