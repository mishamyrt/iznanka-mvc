<?php

namespace Core;

class Tools
{
    static function escapeUrl(string $url) : string {
        return substr($url, -1) === '/' ? substr($url, 0, -1) : $url;
    }
    static function escapeFilePath(string $url) : string {
        return substr($url, 0, 1) === '/' ? substr($url, 1) : $url;
    }
}