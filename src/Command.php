<?php

namespace Alexkart\CurlBuilder;

use Psr\Http\Message\ServerRequestInterface;

class Command
{
    /**
     * Template part which represents command name
     */
    public const TEMPLATE_NAME = '{name}';

    /**
     * Template part which represents command line options
     */
    public const TEMPLATE_OPTIONS = '{options}';

    /**
     * Template part which represents url
     */
    public const TEMPLATE_URL = '{url}';

    /**
     * Single quote character
     */
    public const QUOTE_CHARACTER_SINGLE = "'";

    /**
     * Double quote character
     */
    public const QUOTE_CHARACTER_DOUBLE = '"';

    /**
     * No quote character
     */
    public const QUOTE_CHARACTER_NONE = '';

    /**
     * Command name
     */
    private const NAME = 'curl';

    /**
     * Header names that can use %x2C (",") character, so multiple header fields can't be folded into a single header field
     */
    private const HEADER_EXCEPTIONS = [
        'set-cookie',
        'www-authenticate',
        'proxy-authenticate',
    ];

    /**
     * @var string built command
     */
    private $command;

    /**
     * @var string url
     */
    private $url;

    /**
     * @var string command template
     */
    private $template;

    /**
     * @var array command line options
     */
    private $options = [];

    /**
     * Character used to quote arguments
     * @var string
     */
    private $quoteCharacter;

    /**
     * @var ServerRequestInterface|null
     */
    private $request;

    public function __construct()
    {
        $this->initTemplate();
        $this->initQuoteCharacter();
    }

    /**
     * Generates command
     * @return string
     */
    public function build(): string
    {
        $this->setCommand($this->getTemplate());
        $this->buildName();
        $this->buildOptions();
        $this->buildUrl();
        return $this->getCommand();
    }

    /**
     * @param string $url
     * @return Command
     */
    public function setUrl($url): Command
    {
        $this->url = $url;
        return $this;
    }


    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string $template
     * @return Command
     */
    public function setTemplate(string $template): Command
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @param array $options
     * @return Command
     */
    public function setOptions(array $options): Command
    {
        $this->options = $this->toInternalFormat($options);
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param string $option
     * @param string|null $argument
     * @return Command
     */
    public function addOption($option, $argument = null): Command
    {
        $this->options[$option][] = $argument;
        return $this;
    }

    /**
     * @param array $options
     */
    public function addOptions(array $options): void
    {
        $options = $this->toInternalFormat($options);
        foreach ($options as $option => $arguments) {
            foreach ($arguments as $argument) {
                $this->addOption($option, $argument);
            }
        }
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand($command): void
    {
        $this->command = $command;
    }

    /**
     * Inits default command template
     */
    private function initTemplate(): void
    {
        $this->setTemplate(static::TEMPLATE_NAME . static::TEMPLATE_OPTIONS . static::TEMPLATE_URL);
    }

    /**
     * Inits default quote character
     */
    private function initQuoteCharacter(): void
    {
        $this->setQuoteCharacter(static::QUOTE_CHARACTER_SINGLE);
    }

    /**
     * Builds command name
     */
    private function buildName(): void
    {
        $this->buildTemplatePart(static::TEMPLATE_NAME, static::NAME);
    }

    /**
     * Builds command line options
     */
    private function buildOptions(): void
    {
        $optionsString = '';
        $options = $this->getOptions();
        if (is_array($options) && !empty($options)) {
            foreach ($options as $option => $arguments) {
                foreach ($arguments as $argument) {
                    $optionsString .= ' ' . $option;
                    if ($argument !== null) {
                        $optionsString .= ' ' . $this->quote($argument);
                    }
                }
            }
        }
        $optionsString = trim($optionsString);

        $this->buildTemplatePart(static::TEMPLATE_OPTIONS, $optionsString);
    }

    /**
     * Builds url
     */
    private function buildUrl(): void
    {
        $this->buildTemplatePart(static::TEMPLATE_URL, $this->getUrl());
    }

    /**
     * Builds command part
     * @param string $search
     * @param string $replace
     */
    private function buildTemplatePart($search, $replace): void
    {
        if ($replace === '') {
            // remove extra space
            $this->setCommand(preg_replace($this->getTemplatePartPattern($search), $search, $this->getCommand()));
        } else {
            // add space
            $replace = ' ' . $replace;
        }

        $this->setCommand(trim(str_replace($search, $replace, $this->getCommand())));
    }

    /**
     * Generates regular expression in order to remove extra spaces from the command template if the part is empty
     * @param string $search
     * @return string
     */
    private function getTemplatePartPattern($search): string
    {
        return '/ ?' . str_replace(['{', '}'], ['\{', '\}'], $search) . ' ?/';
    }

    /**
     * @param string $quoteCharacter
     * @return Command
     */
    public function setQuoteCharacter(string $quoteCharacter): Command
    {
        $this->quoteCharacter = $quoteCharacter;
        return $this;
    }

    /**
     * @return string
     */
    public function getQuoteCharacter(): string
    {
        return $this->quoteCharacter;
    }

    /**
     * Quotes argument
     * @param string $argument
     * @return string
     */
    private function quote($argument): string
    {
        $quoteCharacter = $this->getQuoteCharacter();

        if ($quoteCharacter === '') {
            return $argument;
        }

        if (strpos($argument, $quoteCharacter) !== false) {
            if ($quoteCharacter === static::QUOTE_CHARACTER_SINGLE) {
                return '$' . $quoteCharacter . $this->escape($argument) . $quoteCharacter;
            }

            return $quoteCharacter . $this->escape($argument) . $quoteCharacter;
        }

        return $quoteCharacter . $argument . $quoteCharacter;
    }

    /**
     * Escapes double quotes in the argument
     * @param string $argument
     * @return string
     */
    private function escape(string $argument): string
    {
        return str_replace($this->getQuoteCharacter(), '\\' . $this->getQuoteCharacter(), $argument);
    }

    /**
     * Converts option from user friendly format ot internal format
     * @param array $options
     * @return array
     */
    private function toInternalFormat(array $options): array
    {
        $formattedOptions = [];
        foreach ($options as $option => $arguments) {
            $option = trim($option);
            if (strpos($option, '-') !== 0) {
                // ['-L', '-v']
                $formattedOptions[$arguments] = [null];
            } elseif (!is_array($arguments)) {
                // ['-L' => null, '-v' => null]
                $formattedOptions[$option] = [$arguments];
            } else {
                // internal format
                $formattedOptions[$option] = $arguments;
            }
        }

        return $formattedOptions;
    }


    /**
     * Sets request. If $parse = true gets data from request
     * @param ServerRequestInterface|null $request
     * @param bool $parse
     * @return Command
     */
    public function setRequest(?ServerRequestInterface $request, $parse = true): Command
    {
        $this->request = $request;
        if ($parse) {
            $this->parseRequest();
        }

        return $this;
    }

    /**
     * @return ServerRequestInterface|null
     */
    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }


    /**
     * Gets data from request
     * @return bool
     */
    public function parseRequest(): bool
    {
        $request = $this->getRequest();
        if ($request === null) {
            return false;
        }

        // url
        $this->setUrl((string)$request->getUri());

        // headers
        foreach ($request->getHeaders() as $name => $values) {
            if (strtolower($name) === 'host') {
                continue;
            }
            if (in_array(strtolower($name), static::HEADER_EXCEPTIONS, true)) {
                $header = $request->getHeader($name);
                foreach ($header as $value) {
                    $this->addOption('-H', $name . ': ' . $value);
                }
            } else {
                $this->addOption('-H', $name . ': ' . $request->getHeaderLine($name));
            }
        }

        // data
        $data = (string)$request->getBody();
        if (!empty($data)) {
            $this->addOption('-d', (string)$request->getBody());
        }

        return true;
    }
}
