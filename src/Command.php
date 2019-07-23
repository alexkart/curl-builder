<?php

namespace Alexkart\CurlBuilder;

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
     * @var string command name
     */
    private const NAME = 'curl';

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

    public function __construct()
    {
        $this->initTemplate();
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
     * @return string
     */
    public function getUrl(): string
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
        $this->options = $options;
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
        $this->options[$option] = $argument;
        return $this;
    }

    public function addOptions(array $options)
    {
        // TODO
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
     * Init default command template
     */
    private function initTemplate(): void
    {
        $this->setTemplate(static::TEMPLATE_NAME . static::TEMPLATE_OPTIONS . static::TEMPLATE_URL);
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
            foreach ($options as $option => $argument) {
                $optionsString .= ' ' . $option;
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
}