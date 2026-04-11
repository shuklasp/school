<?php
$file = 'c:/projects/apache/school1/spp/modules/spp/sppview/class.viewpage.php';
$content = file_get_contents($file);

$method = <<<EOD

    /**
     * Function redirect(\$page, \$params = [])
     * Redirects to an internal SPP route or an external URL.
     *
     * @param string \$page   The page name (from pages.yml) or a full destination URL.
     * @param array  \$params Optional associative array of query parameters.
     */
    public static function redirect(\$page, \$params = [])
    {
        // 1. Determine if it's an internal route or external URL
        \$url = \$page;
        if (!preg_match('/^(http|https|ftp):\/\//i', (string)\$page)) {
            // Internal SPP routing uses the 'q' parameter via index.php
            \$url = '?q=' . urlencode((string)\$page);
        }

        // 2. Append additional parameters if provided
        if (!empty(\$params)) {
            \$query = http_build_query(\$params);
            \$url .= (strpos(\$url, '?') === false ? '?' : '&') . \$query;
        }

        // 3. Perform the redirect
        if (!headers_sent()) {
            // Standard HTTP redirect
            header('Location: ' . \$url);
            exit;
        } else {
            // Fallback for when output has already started (JavaScript/Meta Refresh)
            echo '<script type="text/javascript">window.location.href="' . addslashes((string)\$url) . '";</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars((string)\$url) . '" /></noscript>';
            exit;
        }
    }

EOD;

// Find the last closing brace
$pos = strrpos($content, '}');
if ($pos !== false) {
    $newContent = substr($content, 0, $pos) . $method . "}\n";
    file_put_contents($file, $newContent);
    echo "Successfully updated class.viewpage.php\n";
} else {
    echo "Error: Could not find closing brace in class.viewpage.php\n";
}
?>
