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

        // default is singe
        $this->assertEquals("curl -d 'arbitrary' http://example.com", $command->build());

        $command->setQuoteCharacter(Command::QUOTE_CHARACTER_DOUBLE);
        $this->assertEquals('curl -d "arbitrary" http://example.com', $command->build());

        $command->setQuoteCharacter(Command::QUOTE_CHARACTER_SINGLE);
        $this->assertEquals("curl -d 'arbitrary' http://example.com", $command->build());

        $command->setQuoteCharacter('');
        $this->assertEquals('curl -d arbitrary http://example.com', $command->build());
    }

    public function testBuildEscapeArguments(): void
    {
        $argument = <<<ARG
x=test'1
ARG;
        $expected = <<<EXP
curl -d $'x=test\'1' http://example.com
EXP;
        $command = new Command();
        $command->setQuoteCharacter(Command::QUOTE_CHARACTER_SINGLE);
        $command->setUrl('http://example.com');
        $command->addOption('-d', $argument);
        $this->assertEquals($expected, $command->build());

        $argument = <<<ARG
x=test"2
ARG;
        $expected = <<<EXP
curl -d 'x=test"2' http://example.com
EXP;
        $command = new Command();
        $command->setQuoteCharacter(Command::QUOTE_CHARACTER_SINGLE);
        $command->setUrl('http://example.com');
        $command->addOption('-d', $argument);
        $this->assertEquals($expected, $command->build());

        $argument = <<<ARG
x=test'1"2
ARG;
        $expected = <<<EXP
curl -d $'x=test\'1"2' http://example.com
EXP;
        $command = new Command();
        $command->setQuoteCharacter(Command::QUOTE_CHARACTER_SINGLE);
        $command->setUrl('http://example.com');
        $command->addOption('-d', $argument);
        $this->assertEquals($expected, $command->build());

        $argument = <<<ARG
x=test'1
ARG;
        $expected = <<<EXP
curl -d "x=test'1" http://example.com
EXP;
        $command = new Command();
        $command->setQuoteCharacter(Command::QUOTE_CHARACTER_DOUBLE);
        $command->setUrl('http://example.com');
        $command->addOption('-d', $argument);
        $this->assertEquals($expected, $command->build());

        $argument = <<<ARG
x=test"2
ARG;
        $expected = <<<EXP
curl -d "x=test\"2" http://example.com
EXP;
        $command = new Command();
        $command->setQuoteCharacter(Command::QUOTE_CHARACTER_DOUBLE);
        $command->setUrl('http://example.com');
        $command->addOption('-d', $argument);
        $this->assertEquals($expected, $command->build());

        $argument = <<<ARG
x=test'1"2
ARG;
        $expected = <<<EXP
curl -d "x=test'1\"2" http://example.com
EXP;
        $command = new Command();
        $command->setQuoteCharacter(Command::QUOTE_CHARACTER_DOUBLE);
        $command->setUrl('http://example.com');
        $command->addOption('-d', $argument);
        $this->assertEquals($expected, $command->build());
    }
}
