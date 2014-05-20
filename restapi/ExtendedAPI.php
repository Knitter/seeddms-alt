<?php

abstract class ExtendedAPI {

    protected static function error($code, $reason) {
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

        header($protocol . ' ' . $code . ' ' . $reason);
        exit;
    }

}
