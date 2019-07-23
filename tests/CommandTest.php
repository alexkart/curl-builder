<?php

namespace Alexkart\CurlBuilder\Tests;

use Alexkart\CurlBuilder\Command;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    public function testBuild(): void
    {
        $command = new Command();
        $command->setUrl('http://example.com');
        $this->assertEquals('curl http://example.com', $command->build());
    }
}