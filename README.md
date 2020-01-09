
[![SymfonyInsight](https://insight.symfony.com/projects/0892be64-8bd2-4794-977b-dfb221e3fd2c/big.svg)](https://insight.symfony.com/projects/0892be64-8bd2-4794-977b-dfb221e3fd2c)

Make sure to add the code below:

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
