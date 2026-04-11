# 07. The Event System

SPP is designed around a powerful, cross-cutting **Event-Driven Hook** system. This allows you to extend the core framework's behavior without modifying its internal code.

---

## What is an Event?

An event is a specific point in the application lifecycle where custom logic can be "hooked." For example:
-   `spp_init`: When the framework is bootstrapping.
-   `PageNotFound`: When a user requests a URL that doesn't exist.
-   `AttributeNotFoundException`: When an invalid model attribute is accessed.

---

## Registering and Firing Events

### 1. Registering a New Event
Events must be registered before they are fired to ensure the event hub knows how to handle them.

```php
\SPP\SPPEvent::registerEvent('my_custom_event');
```

### 2. Firing an Event
Events can be fired manually anywhere in your application:

```php
\SPP\SPPEvent::fireEvent('my_custom_event', ['data' => 'Hello World']);
```

---

## Handling Events (Hooks)

To listen for an event, you specify the event name and the function (or method) that should be called when the event fires.

```php
\SPP\SPPEvent::registerHandler('my_custom_event', 'handle_my_event_logic');

function handle_my_event_logic($payload) {
    echo "Event fired with data: " . $payload['data'];
}
```

### Class-based Handlers

You can also use static methods or object methods as handlers:

```php
\SPP\SPPEvent::registerHandler('PageNotFound', [\MyMod\MyHandler::class, 'handle404']);
```

---

## Real-world Examples

### 1. Adding Global CSS
The `event_spp_include_css_files` event allows you to inject CSS into the `ViewPage` header dynamically:

```php
\SPP\SPPEvent::registerHandler('event_spp_include_css_files', function() {
    \SPPMod\SPPView\ViewPage::addCssIncludeFile('res/custom_theme.css');
});
```

### 2. Dynamic Initialization
Use the `spp_init` event to configure modules or state during the bootstrap process:

```php
\SPP\SPPEvent::registerHandler('spp_init', function() {
    // Perform early-stage app setup
});
```

---

[**Next: Advanced Features**](08_advanced.md)
