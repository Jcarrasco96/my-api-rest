<?php

namespace MyApiRest\services;

use MyApiRest\core\Application;

class Language
{

    private mixed $language;

    public function __construct(string $code = 'en')
    {
        $this->language = require_once LIBRARY_LANGUAGES . "$code.php";
    }

    public function t(string $key, array $params = []): string
    {
        if (isset($this->language[$key])) {
            $translation = $this->language[$key];

            preg_match_all('/\{(.*?)}/', $translation, $matches);

            foreach ($matches[1] as $index => $match) {
                if (isset($params[$index])) {
                    $translation = str_replace("{" . $match . "}", $params[$index], $translation);
                } else {
                    $translation = str_replace("{" . $match . "}", "{" . $match . "}", $translation);
                }
            }

            return $translation;
        }

        Application::$logger->warning("Language key: '$key' not found.");
        return $key;
    }

}