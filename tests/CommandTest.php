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

        $command->setTemplate(Command::TEMPLATE_NAME . Command::TEMPLATE_URL);
        $this->assertEquals('curl http://example.com', $command->build());

        $command->setTemplate('');
        $this->assertEquals('', $command->build());
    }

    public function testBuildWithLongOptions(): void
    {
        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('--verbose');
        $this->assertEquals('curl --verbose http://example.com', $command->build());

        $command->addOption('--location');
        $this->assertEquals('curl --verbose --location http://example.com', $command->build());
    }

    public function testBuildWithArgumentsToOptions(): void
    {
        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('-d', 'arbitrary');
        $this->assertEquals("curl -d 'arbitrary' http://example.com", $command->build());

        // data from file
        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('-d', '@json.txt');
        $this->assertEquals("curl -d '@json.txt' http://example.com", $command->build());

        // argument with spaces
        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('-d', 'I am your father');
        $this->assertEquals("curl -d 'I am your father' http://example.com", $command->build());
    }

    public function testBuildSetQuoteCharacter(): void
    {
        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('-d', 'arbitrary');
        $command->setQuoteCharacter(Command::QUOTE_CHARACTER_DOUBLE);
        $this->assertEquals('curl -d "arbitrary" http://example.com', $command->build());

        $command->addOption('-d', '{ "name": "Darth" }');
        $command->setQuoteCharacter(Command::QUOTE_CHARACTER_SINGLE);
        $this->assertEquals('curl -d \'{ "name": "Darth" }\' http://example.com', $command->build());
    }

    public function testBuildAutoSwitchQuoteCharacter(): void
    {
        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('-d', "I'm your father");
        $this->assertEquals("curl -d $'I\'m your father' http://example.com", $command->build());

        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('-d', '{ "name": "Darth" }');
        $command->setQuoteCharacter(Command::QUOTE_CHARACTER_DOUBLE);
        $this->assertEquals('curl -d "{ \"name\": \"Darth\" }" http://example.com', $command->build());

        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('-d', '{ "name": "I\'m your father" }');
        $command->setQuoteCharacter(Command::QUOTE_CHARACTER_DOUBLE);
        $this->assertEquals('curl -d "{ \"name\": \"I\'m your father\" }" http://example.com', $command->build());

        $command->setQuoteCharacter(Command::QUOTE_CHARACTER_SINGLE);
        $this->assertEquals("curl -d '{ \"name\": \"I\'m your father\" }' http://example.com", $command->build());
    }
}
