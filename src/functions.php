<?php
function array_pluck($array, $key) {
    return array_map(function($v) use ($key) {
        return is_object($v) ? $v->$key : $v[$key];
    }, $array);
}

function dd ($variable) {
    echo "<pre>";
    echo(json_encode($variable));
    echo "</pre>";
    die();
}