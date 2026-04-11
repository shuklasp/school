# 01. Introduction to SPP Framework

The **Satya Portal Pack (SPP)** is a powerful, lightweight PHP framework designed to streamline the development of modular web applications and enterprise-grade portals.

---

## What is SPP?

SPP is an **event-driven**, **modular** framework that prioritizes "Pinnacle Architecture" — a design philosophy focusing on separation of concerns, high performance, and rapid application development through declarative configurations (YAML/XML).

At its core, SPP is:
-   **Modular**: Every feature is a self-contained module.
-   **Declarative**: Many framework behaviors (routing, database schema, forms) are defined in configuration files.
-   **Extensible**: A robust event system allows for custom hooks into core framework processes.

---

## Core Philosophy

### 1. Separation of Concerns
Application logic is strictly separated from the framework core. Developers focus on building **Modules** and **Entities**, while the core handles bootstrapping, session management, and routing.

### 2. "Drop and Play" SPA Architecture
SPP is designed to automatically "augment" static pages with Single Page Application (SPA) features such as AJAX-based fragment routing and instant form validation, without requiring complex front-end frameworks like React or Vue.

### 3. Automated Data Management
Using the **SPPEntity** system, you define your data models in YAML. The framework then automatically handles database table creation, updates, and CRUD operations.

---

## Key Features

1.  **Modules Container**: A unified registry for loading and managing independent framework components.
2.  **SPPEvents**: A global event bus to fire and handle system-wide hooks.
3.  **SPPDB & SPPEntity**: A sophisticated ORM-like layer supporting both traditional SQL and AI-driven vector models.
4.  **Form Augmentation**: Convert simple HTML forms into fully validated, AJAX-ready components using XML/YAML definitions.
5.  **SPPLive & SPPNexus**: Integrated WebSocket Support and an optimized Edge Compiler for high-speed delivery.

---

[**Next: Getting Started**](02_getting_started.md)
