<?php

namespace Alexkart\CurlBuilder\Tests;

use Alexkart\CurlBuilder\Command;
use Nyholm\Psr7\ServerRequest;
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
    }

    public function buildSetOptionProvider(): array
    {
        return [
            [
                [],
                'curl http://example.com',
            ],
            [
                ['-L' => [null], '-v' => [null]],
                'curl -L -v http://example.com',
            ],
            [
                ['-L' => null, '-v' => null],
                'curl -L -v http://example.com',
            ],
            [
                ['-d' => 'test1', '-H' => 'test2'],
                "curl -d 'test1' -H 'test2' http://example.com",
            ],
            [
                ['-H' => ['test1', 'test2']],
                "curl -H 'test1' -H 'test2' http://example.com",
            ],
            [
                ['-L', '-v'],
                'curl -L -v http://example.com',
            ],
            [
                ['-L', '-v' => null, '--insecure' => [null], '-d' => 'test1', '-H' => ['test2']],
                "curl -L -v --insecure -d 'test1' -H 'test2' http://example.com",
            ],
        ];
    }

    /**
     * @dataProvider buildSetOptionProvider
     */
    public function testBuildSetOptions($options, $expected): void
    {
        $command = new Command();
        $command->setUrl('http://example.com');

        $command->setOptions($options);
        $this->assertEquals($expected, $command->build());
    }

    public function testBuildDuplicatedOptions(): void
    {
        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('-v');
        $command->addOption('-L');
        $command->addOption('-L');
        $this->assertEquals('curl -v -L -L http://example.com', $command->build());
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

    public function buildWithArgumentsToOptionsProvider(): array
    {
        return [
            [
                'http://example.com',
                '-d',
                'arbitrary',
                "curl -d 'arbitrary' http://example.com",
            ],
            [
                'http://example.com',
                '-d',
                '@json.txt',
                "curl -d '@json.txt' http://example.com",
            ],
            [
                'http://example.com',
                '-d',
                'I am your father',
                "curl -d 'I am your father' http://example.com",
            ],
        ];
    }

    /**
     * @dataProvider buildWithArgumentsToOptionsProvider
     */
    public function testBuildWithArgumentsToOptions($url, $option, $value, $expected): void
    {
        $command = new Command();
        $command->setUrl($url);
        $command->addOption($option, $value);
        $this->assertEquals($expected, $command->build());
    }

    public function buildSetQuoteProvider(): array
    {
        return [
            [
                Command::QUOTE_SINGLE,
                "curl -d 'arbitrary' http://example.com",
            ],
            [
                Command::QUOTE_DOUBLE,
                'curl -d "arbitrary" http://example.com',
            ],
            [
                Command::QUOTE_NONE,
                'curl -d arbitrary http://example.com',
            ],
        ];
    }

    /**
     * @dataProvider buildSetQuoteProvider
     */
    public function testBuildSetQuoteCharacter($quote, $expected): void
    {
        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('-d', 'arbitrary');

        $command->setQuoteCharacter($quote);
        $this->assertEquals($expected, $command->build());
    }

    public function testBuildSetDefaultQuoteCharacter(): void
    {
        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('-d', 'arbitrary');
        $this->assertEquals("curl -d 'arbitrary' http://example.com", $command->build());
    }

    public function buildEscapeArgumentsProvider(): array
    {
        return [
            [
<<<ARG
x=test'1
ARG
,
<<<EXP
curl -d $'x=test\'1' http://example.com
EXP
,
Command::QUOTE_SINGLE,
            ],
            [
<<<ARG
x=test"2
ARG
,
<<<EXP
curl -d 'x=test"2' http://example.com
EXP
,
Command::QUOTE_SINGLE,
            ],
            [
<<<ARG
x=test'1"2
ARG
,
<<<EXP
curl -d $'x=test\'1"2' http://example.com
EXP
,
Command::QUOTE_SINGLE,
            ],
            [
<<<ARG
x=test'1
ARG
,
<<<EXP
curl -d "x=test'1" http://example.com
EXP
,
Command::QUOTE_DOUBLE,
            ],
            [
<<<ARG
x=test"2
ARG
,
<<<EXP
curl -d "x=test\"2" http://example.com
EXP
,
Command::QUOTE_DOUBLE,
            ],
            [
<<<ARG
x=test'1"2
ARG
,
<<<EXP
curl -d "x=test'1\"2" http://example.com
EXP
,
Command::QUOTE_DOUBLE,
            ],
        ];
    }

    /**
     * @dataProvider buildEscapeArgumentsProvider
     */
    public function testBuildEscapeArguments($argument, $expected, $quote): void
    {
        $command = new Command();
        $command->setQuoteCharacter($quote);
        $command->setUrl('http://example.com');
        $command->addOption('-d', $argument);
        $this->assertEquals($expected, $command->build());
    }

    public function testBuildMultipleOptions(): void
    {
        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('-H', 'Connection: keep-alive');
        $command->addOption('-H', 'Cache-Control: max-age=0');
        $this->assertEquals("curl -H 'Connection: keep-alive' -H 'Cache-Control: max-age=0' http://example.com", $command->build());
    }

    public function testBuildAddOptions(): void
    {
        $command = new Command();
        $command->setUrl('http://example.com');
        $command->addOption('-v');
        $command->addOptions([
            '-L',
            '-d' => 'test'
        ]);
        $this->assertEquals("curl -v -L -d 'test' http://example.com", $command->build());
    }

    public function testBuildPsrHttpRequestOnGet(): void
    {
        $request = new ServerRequest('GET', 'http://example.com');
        $command = new Command();
        $command->setRequest($request);
        $this->assertEquals('curl http://example.com', $command->build());
    }

    public function testBuildPsrHttpRequestOnPost(): void
    {
        $request = new ServerRequest('POST', 'http://example.com', [
            'Connection' => ['keep-alive'],
            'Accept' => [
                'text/html',
                'application/xhtml+xml',
            ],
        ], 'data');

        $command = new Command();
        $command->setRequest($request);
        $this->assertEquals("curl -H 'Connection: keep-alive' -H 'Accept: text/html, application/xhtml+xml' -d 'data' http://example.com", $command->build());

        $command = new Command();
        $command->setRequest($request, false);
        $this->assertEquals('curl', $command->build());
        $command->parseRequest();
        $this->assertEquals("curl -H 'Connection: keep-alive' -H 'Accept: text/html, application/xhtml+xml' -d 'data' http://example.com", $command->build());
    }

    public function testBuildPsrHttpRequestHeaderExceptions(): void
    {
        $request = new ServerRequest('GET', 'http://example.com', [
            'Set-Cookie' => [
                'test1=1; Expires=Thu, 01-Jan-1970 00:00:10 GMT; Path=/; Secure; HttpOnly',
                'test2=2; Expires=Thu, 01-Jan-1970 00:00:10 GMT; Path=/; Secure; HttpOnly',
            ],
        ]);

        $command = new Command();
        $command->setRequest($request);
        $this->assertEquals("curl -H 'Set-Cookie: test1=1; Expires=Thu, 01-Jan-1970 00:00:10 GMT; Path=/; Secure; HttpOnly' -H 'Set-Cookie: test2=2; Expires=Thu, 01-Jan-1970 00:00:10 GMT; Path=/; Secure; HttpOnly' http://example.com", $command->build());
    }

    public function testBuildParseRequest(): void
    {
        $command = new Command();
        $this->assertFalse($command->parseRequest());

        $request = new ServerRequest('GET', 'http://example.com');
        $command->setRequest($request);
        $this->assertTrue($command->parseRequest());
    }
}
