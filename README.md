# Personal Accountant

App to keep all balances and transactions organized.

*Do not trust anyone, host only locally*

Demo - https://pa-demo.vchychuzhko.com

## Table of Contents

- [Deploy](#deploy)
  - [Requirements](#requirements)
  - [Create Admin User](#create-admin-user)
- [Usage](#usage)
  - [Web](#web)
  - [Entities](#entities)
  - [Commands](#commands)

## Deploy

### Requirements

- PHP 8.3+
- Composer 2
- MySQL 8
- Node 22+

### Create Admin User

To create initial admin user, use this commands to generate password hash and insert a user into database:

```bash
php bin/console security:hash-password
php bin/console dbal:run-sql -q "INSERT INTO admin \
  (username, roles, password) \
  VALUES ('admin', '[\"ROLE_ADMIN\"]', \
  '\$2y\$13\$XAcEN5O1gAk78..wSc6E4utusgZ17L3hA7X4xP2PMqZOIMuSGe6lS')"
  
# escape all "$" chars with backslash - "\$"
```

## Usage

### Web

Panel is available by default at - https://<your-localhost>/admin

* On Configuration page you can set API key for [Currency API](https://currencyapi.com/) service.
* Dashboard charts are cacheable and can be refreshed manually on Configuration page.
* Apps page has deposit calculator and currency converter apps.

### Entities

|   Name   | Description                                 |
|:--------:|---------------------------------------------|
| Currency | Core entity that allows managing currencies |
| Balance  | Balance representation                      |
|  Income  | Income attached to Balance                  |
| Payment  | Payment or transaction attached to Balance  |
| Exchange | Transfer between Balances                   |
| Deposit  | Open or completed deposits                  |
|   Loan   | Loans owed by person                        |
|   Tag    | Payment tags for expenses organizing        |

* Creating Income, Payment, Exchange or Deposit will update related Balance amount.
* On Deposit completion, initial amount is returned to the Balance and Income with interest is created.

### Commands

#### Align Payment IDs according to created_at field

```bash
php bin/console app:fix-payment-ids
```

---

###### Built with [Symfony 6.4](https://symfony.com/doc/6.4/index.html)
