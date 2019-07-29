<?php

namespace Astrotomic\Translatable\Validation;

use InvalidArgumentException;
use Astrotomic\Translatable\Locales;

class RuleFactory
{
    const FORMAT_ARRAY = 1;
    const FORMAT_KEY = 2;

    /**
     * @var int
     */
    protected $format;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var string
     */
    protected $suffix;

    /**
     * @var null|array
     */
    protected $locales = null;

    public function __construct(int $format = self::FORMAT_ARRAY, string $prefix = '%', string $suffix = '%')
    {
        $this->format = $format;
        $this->prefix = $prefix;
        $this->suffix = $suffix;
    }

    public static function make(array $rules, int $format = self::FORMAT_ARRAY, string $prefix = '%', string $suffix = '%', ?array $locales = null): array
    {
        $factory = new static($format, $prefix, $suffix);

        $factory->setLocales($locales);

        return $factory->parse($rules);
    }

    public function setLocales(?array $locales = null): self
    {
        /** @var Locales */
        $helper = app(Locales::class);

        if (is_null($locales)) {
            $this->locales = $helper->all();

            return $this;
        }

        foreach ($locales as $locale) {
            if (! $helper->has($locale)) {
                throw new InvalidArgumentException(sprintf('The locale [%s] is not defined in available locales.', $locale));
            }
        }

        $this->locales = $locales;

        return $this;
    }

    public function parse(array $input): array
    {
        $rules = [];

        foreach ($input as $key => $value) {
            if (! $this->isTranslatable($key)) {
                $rules[$key] = $value;
                continue;
            }

            foreach ($this->locales as $locale) {
                $rules[$this->formatKey($locale, $key)] = $value;
            }
        }

        return $rules;
    }

    protected function formatKey(string $locale, string $key): string
    {
        switch ($this->format) {
            case self::FORMAT_ARRAY:
                return preg_replace($this->getPattern(), $locale.'.$1', $key);
            case self::FORMAT_KEY:
                return preg_replace($this->getPattern(), '$1:'.$locale, $key);
        }
    }

    protected function getPattern(): string
    {
        $prefix = preg_quote($this->prefix);
        $suffix = preg_quote($this->suffix);

        return '/'.$prefix.'([^\.'.$prefix.$suffix.']+)'.$suffix.'/';
    }

    protected function isTranslatable(string $key): bool
    {
        return strpos($key, $this->prefix) !== false && strpos($key, $this->suffix) !== false;
    }
}
