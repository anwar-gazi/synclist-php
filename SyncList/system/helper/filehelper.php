<?php

class FileHelper {
    static function extension($path) {
        return pathinfo($path, PATHINFO_EXTENSION);
    }
}