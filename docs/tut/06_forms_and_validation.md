# 06. Forms & Validation

SPP provides a powerful XML/YAML-based form system that automatically handles server-side processing and client-side validation.

---

## Defining a Form

A typical form is defined in an XML file, specifying the controls, their types, and any linked validation rules.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<forms>
    <form name="login_form" action="/login.php">
        <controls>
            <control name="username" type="ViewInputText">
                <placeholder>Enter Username</placeholder>
                <id>username_id</id>
            </control>
            <control name="password" type="ViewInputPassword">
                <placeholder>Enter Password</placeholder>
                <id>password_id</id>
            </control>
            <control name="login_btn" type="ViewInputSubmit">
                <value>Login</value>
                <id>login_btn_id</id>
            </control>
        </controls>
        <validations>
            <validation type="ViewValidatorRequired" message="Username is required">
                <attach element="username_id" event="onblur" errorholder="error_username"/>
            </validation>
            <validation type="ViewValidatorRequired" message="Password is required">
                <attach element="password_id" event="onblur" errorholder="error_password"/>
            </validation>
        </validations>
    </form>
</forms>
```

---

## Automatic Form Augmentation

When the `FormAugmentor` is active, it detects your XML-defined forms and:
1.  **Injects** necessary JavaScript for client-side validation.
2.  **Wraps** the form in an AJAX-compatible container.
3.  **Applies** CSS classes for error states and success messages.

This allows you to add complex validation to any page with minimal extra code.

---

## Server-side Processing

To handle a form submission in your PHP controller:

```php
if (\SPPMod\SPPView\ViewPage::processForms()) {
    // This is called automatically when a form is submitted
}

function login_form_submitted() {
    $username = $_POST['username'];
    $password = $_POST['password'];
    // ... authentication logic
}
```

---

## Manual Form Generation

If you prefer building forms manually, you can still use SPP's `ViewTag` classes for consistent rendering:

```php
$form = new \SPPMod\SPPView\ViewForm('my_form', '/submit.php');
$uname = new \SPPMod\SPPView\ViewInputText('username');
$uname->setAttribute('placeholder', 'Enter Username');
$form->addElement($uname);
echo $form->render();
```

---

[**Next: The Event System**](07_events.md)
