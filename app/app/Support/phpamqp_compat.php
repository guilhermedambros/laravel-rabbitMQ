<?php

namespace PhpAmqpLib\Wire;

// Provide namespaced wrappers that delegate to global bcmath functions
// This protects against environments where the library calls unqualified
// functions inside a namespace (which may try the namespaced name first).
if (!function_exists(__NAMESPACE__ . '\\bcmod')) {
    function bcmod($x, $mod)
    {
        return \bcmod($x, $mod);
    }
}

if (!function_exists(__NAMESPACE__ . '\\bcdiv')) {
    function bcdiv($x, $y, $scale = 0)
    {
        return \bcdiv($x, $y, $scale);
    }
}
