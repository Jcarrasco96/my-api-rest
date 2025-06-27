<?php

namespace SimpleApiRest\core;

class Language
{

    private array $language;

    public function __construct(string $code = 'en')
    {
        $this->language = require_once LIBRARY_LANGUAGES . "$code.php";

        if (is_file(APP_ROOT . 'languages' . DIRECTORY_SEPARATOR . "$code.php")) {
            $applicationLanguages = require_once APP_ROOT . 'languages' . DIRECTORY_SEPARATOR . "$code.php";

            if (is_array($applicationLanguages)) {
                $this->language = array_merge($this->language, $applicationLanguages);
            }
        }
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

        BaseApplication::$logger->warning("Language key: '$key' not found.");
        return $key;
    }

}