<?php

namespace Alexkart\CurlBuilder\Tests;

use Alexkart\CurlBuilder\Command;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    public function testBuildMinimal(): void
    {
        $command = new Command();
        $this->assertEquals('curl', $command->build());
    }

    public function testBuildWithUrl(): void
    {
        $command = new Command();
        $command->setUrl('http://example.com');
        $this->assertEquals('curl http://example.com', $command->build());
    }

    public function testBuildWithOption(): void
    {
        $command = new Command();
        $command->addOption('-v');
        $this->assertEquals('curl -v', $command->build());
    }

    public function testBuildWithOptions(): void
    {
        $command = new Command();
        $command->addOption('-v');
        $command->addOption('-L');
        $this->assertEquals('curl -v -L', $command->build());
    }

    public function testBuildWithUrlAndOption(): void
    {
        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('-v');
        $this->assertEquals('curl -v http://example.com', $command->build());
    }

    public function testBuildWithUrlAndOptions(): void
    {
        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('-v');
        $command->addOption('-L');
        $this->assertEquals('curl -v -L http://example.com', $command->build());

        $command->setOptions([]);
        $this->assertEquals('curl http://example.com', $command->build());

        $command->setOptions(['-L' => null, '-v' => null]);
        $this->assertEquals('curl -L -v http://example.com', $command->build());
    }

    public function testBuildDuplicatedOptions(): void
    {
        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('-v');
        $command->addOption('-L');
        $command->addOption('-L');
        $this->assertEquals('curl -v -L http://example.com', $command->build());
    }

    public function testBuildSetTemplate(): void
    {
        $command = new Command();
        $command->setTemplate(Command::TEMPLATE_NAME . Command::TEMPLATE_URL . Command::TEMPLATE_OPTIONS);
        $command->setUrl('http://example.com');
        $command->addOption('-v');
        $command->addOption('-L');
        $this->assertEquals('curl http://example.com -v -L', $command->build());

    }
}