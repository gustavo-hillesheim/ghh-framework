<?php
function startsWith(string $comparedString, string $startingString): bool
{
    $len = strlen($startingString);
    return (substr($comparedString, 0, $len) === $startingString);
}

function endsWith(string $comparedString, string $endingString): bool
{
    $len = strlen($endingString);
    if ($len == 0) {
        return true;
    }
    return (substr($comparedString, -$len) === $endingString);
}


function addSurroundingTag(string $text, string $tagName): string
{
    if (!startsWith($text, "<$tagName>")) {
        $text = "<$tagName>\n" . $text;
    }
    if (!endsWith($text, "</$tagName>")) {
        $text = $text . "\n</$tagName>";
    }
    return $text;
}

function removeSurroundingTag(string $text, string $tagName): string
{
    if (startsWith($text, "<$tagName>")) {
        $text = substr($text, strlen("<$tagName>"));
    }
    if (endsWith($text, "</$tagName>")) {
        $text = substr($text, 0, -strlen("</$tagName>"));
    }
    return $text;
}

function kebabCase(string $text): string
{
    return strtolower(preg_replace(
        ["/([A-Z]+)/", "/_([A-Z]+)([A-Z][a-z])/"],
        ["-$1", "-$1-$2"],
        lcfirst($text)
    ));
}
