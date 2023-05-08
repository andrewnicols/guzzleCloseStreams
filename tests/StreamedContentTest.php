<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Test for content streams not being closed properly on windows.
 */
final class StreamedContentTest extends TestCase
{
    /**
     * Data provider for testStreamedContentNoVar test.
     * The same test will run multiple times.
     *
     * @return array
     */
    public static function provider(): array {
        return [
            [],
            [],
            [],
        ];
    }

    /**
     * @dataProvider provider
     */
    public function testStreamedContentNoVar(): void
    {
        $this->runStreamTest();
    }

    protected function runStreamTest(): void
    {
        $tmpfile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'testdata' . DIRECTORY_SEPARATOR . 'tmpfile2.txt';
        $this->assertFalse(file_exists($tmpfile));

        file_put_contents($tmpfile, 'Some example content');
        $handle = fopen($tmpfile, "r+");
        $this->assertTrue(file_exists($tmpfile));

        $mock = new MockHandler([
            new Response(200, [], 'Success'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $client->request('POST', '/', [
            'multipart' => [
                [
                    'name' => 'filecontents',
                    'contents' => Utils::streamFor($handle),
                ],
            ],
        ]);

        unlink($tmpfile);
        $this->assertFalse(file_exists($tmpfile));
    }
}
