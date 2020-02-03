
[![SymfonyInsight](https://insight.symfony.com/projects/0892be64-8bd2-4794-977b-dfb221e3fd2c/big.svg)](https://insight.symfony.com/projects/0892be64-8bd2-4794-977b-dfb221e3fd2c)

About
============
Groundwork Bundle has been developed to be a lightweight API to provide powerful alternatives to the larger oversized projects that have so much bloat and choices. Basic features of the Groundwork Bundle
- Singular API: Powerful and yet simplistic API 
- Mass Import: Import data directly into your database from spreadsheets. Fast and simple.
- LogIt: Simple and powerful logging of everything that happens in your project. Lightweight and comprehensive with templates to adjust logging.
- User Entity Deployment: Pre-setup your project with all of the tools you need instead of configuring your self. Base user entity that is extendable.

Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require lankerd/groundwork-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require lankerd/groundwork-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    Lankerd\GroundworkBundle\LankerdGroundworkBundle::class => ['all' => true],
];
```
