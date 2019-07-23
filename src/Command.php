<?php

namespace Alexkart\CurlBuilder;

class Command
{
    private const NAME = 'curl';
    private $url;

    public function build(): string
    {
        return static::NAME . ' ' . $this->getUrl();
    }

    /**
     * @param mixed $url
     * @return Command
     */
    public function setUrl($url): Command
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }
}