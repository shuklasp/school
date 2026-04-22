<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHP-SPA Demo</title>
</head>
<body>
    <div id="app">
        <h1>Welcome to PHP-SPA</h1>
        <p>This page uses a PHP component without writing any JavaScript.</p>
        
        <php-comp name="Counter" title="Custom Counter Title" count="10" />

        <hr>
        <h2>Test Form Augmentation</h2>
        <form id="DemoForm">
            <div>
                <label>Name:</label>
                <input type="text" name="username" id="username">
                <span id="username_error" style="color:red"></span>
            </div>
            <button type="submit">Submit</button>
        </form>
    </div>
</body>
</html>
