# 02. Getting Started with SPP

In this section, we'll cover the SPP project structure, key constants, and how to use the framework's CLI tool for quick development.

---

## Directory Structure

A standard SPP project is organized as follows:

-   `spp/`: The core framework directory.
    -   `core/`: Internal framework classes and core logic.
    -   `modules/`: Standard and custom modules.
    -   `res/`: Core framework JS/CSS resources.
-   `etc/`: Application-level configuration files.
    -   `entities/`: YAML files defining your data models.
    -   `pages.yml`: The main routing configuration.
-   `src/`: Your application-specific PHP/HTML source files.
-   `var/`: Logs, caches, and temporary files.
-   `index.php`: The primary entry point for all web requests.

---

## Core Initialization

The framework is initialized via `spp/sppinit.php`. This file defines several critical constants you'll use throughout your development:

-   `SPP_BASE_DIR`: Absolute path to the `spp/` directory.
-   `SPP_CORE_DIR`: Path to the core classes.
-   `SPP_MODULES_DIR`: Path to the modules.
-   `SPP_ETC_DIR`: Global configuration directory.
-   `APP_ETC_DIR`: Application-specific config directory.
-   `SPP_APP_DIR`: The root directory of your application.

---

## The SPP CLI Tool

SPP comes with a powerful CLI tool, `spp.php`, located in the `spp/` directory. It helps you scaffold new components quickly.

### 1. Generating a Data Entity
To create a new data model (Entity), use the `make:entity` command:

```bash
php spp/spp.php make:entity Student
```
This will generate a `student.yml` file in `etc/entities/` with a basic schema.

### 2. Creating a New Module
To scaffold a new functional module:

```bash
php spp/spp.php make:module Inventory
```
This creates a new module directory in `spp/modules/spp/inventory/` containing a `module.xml` and a base class.

### 3. Compiling the Edge Core
For production environments, you can compile the core framework into a high-performance PHAR file:

```bash
php spp/spp.php build:edge
```
This generates `spp/build/spp_edge_core.phar`.

---

[**Next: Modular Architecture**](03_modules.md)
