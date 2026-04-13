# 03. Modular Architecture

SPP is built around a robust, flexible modular architecture. Every major feature in the framework — from database handling (`sppdb`) to authentication (`sppauth`) — is a self-contained **Module**.

---

## What is a Module?

A module is a directory containing at least:
1.  **`module.xml`**: Defines metadata, class autoloading, and configuration.
2.  **`class.<modname>.php`**: The main module class.

Modules are typically located in the `spp/modules/spp/` directory.

---

## Module Configuration (`module.xml`)

Each module's behavior is controlled by its `module.xml` file. Here is a typical structure:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<module>
    <name>sppdb</name>
    <author>Satya Prakash Shukla</author>
    <version>1.2</version>
    <description>Core Database Handler Module</description>
    <namespace>SPPMod\SPPDB</namespace>
    <autoload>
        <class name="SPPDB" file="class.sppdb.php"/>
        <class name="SPPSequence" file="class.sppsequence.php"/>
    </autoload>
</module>
```

---

## The Module Class

A standard SPP module class should extend `\SPP\SPPObject`.

```php
namespace SPPMod\MyModule;

class MyModule extends \SPP\SPPObject
{
    public function __construct() {
        parent::__construct();
    }
}
```

---

## Consuming Modules

The framework automatically registers and loads enabled modules during bootstrapping. You can interact with modules using the `\SPP\Module` core class.

### 1. Check if a Module is Enabled
```php
if (\SPP\Module::isEnabled('sppauth')) {
    // Perform authentication logic
}
```

### 2. Get Module Configuration
Modules can have custom settings defined in YAML or XML. Use `getConfig` to retrieve them:
```php
$dbhost = \SPP\Module::getConfig('dbhost', 'sppdb');
```

---

## Creating a Custom Module
While you can manually create the directory and files, it's recommended to use the CLI:

```bash
php spp/spp.php make:module reports
```
This scaffolds a basic `reports` module, including its namespace and base class.

---

[**Next: Data Modeling with YAML Entities**](04_data_models.md)
