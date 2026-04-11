# 08. Advanced Features

SPP comes with several powerful modules that extend its core capabilities for enterprise usage, AI integration, and real-time communication.

---

## SPPAuth: Authentication & Roles

The `sppauth` module provides a comprehensive security layer for your application. It supports:
-   **User Management**: Create, update, and delete users.
-   **Roles & Rights**: Define custom user roles and assign specific rights (permissions).
-   **Session Security**: Hardened session management with `SPPSession`.

### Example Login
```php
if (\SPPMod\SPPAuth\SPPAuth::login($username, $password)) {
    // User is authenticated
}
```

### Checking Permissions
```php
if (\SPPMod\SPPAuth\SPPAuth::hasRight('admin_access')) {
    // Only admins can see this
}
```

---

## SPPLogger: Flexible Logging

The `spplogger` module provides advanced logging capabilities, including:
-   **Database and/or File Logging**: Choose where logs are stored via `config.yml`.
-   **Log Rotation**: Automatic size-based rotation of log files.
-   **Log Priority**: Set log levels (INFO, WARNING, ERROR).

```php
\SPPMod\SPPLogger\SPP_Logger::error("Database connection failed!");
\SPPMod\SPPLogger\SPP_Logger::info("User {uname} logged in.", ['uname' => 'John']);
```

---

## SPPAI: Vector ORM Integration

SPP integrates with vector models to provide AI-driven search and embedding capabilities.

```php
$results = \SPPMod\SPPAI\SPPAI::searchNatural("Who are my students from Department A?");
foreach ($results as $r) {
    echo $r['name'];
}
```

When an entity is saved, `SPPAI` can automatically generate and store embeddings for its attributes, enabling powerful semantic search within your application.

---

## SPPLive: Real-time WebSockets

The `spplive` module integrates a WebSocket engine to enable real-time features such as:
-   **Live Notifications**: Push alerts to users instantly.
-   **Real-time Collaboration**: Synchronize data across multiple clients.
-   **Dashboard Updates**: Update UI charts and tables as data changes in the backend.

---

## SPPNexus: Edge Compiler

For maximum performance, the **SPPNexus** compiler can bundle your application core and modules into a single, optimized "Edge Node." This reduces I/O overhead and significantly speeds up request handling in high-traffic environments.

```bash
php spp/spp.php build:edge
```

---

## Final Thoughts

You've now covered the basics of the SPP framework! For more advanced implementations, refer to the source code of the core modules in `spp/modules/spp/`.

Happy coding with **Satya Portal Pack**!
