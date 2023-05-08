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
        $tmpfile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'testdata' . DIRECTORY_SEPARATOR . 'tmpfile2.txt';
        $this->assertFalse(file_exists($tmpfile));

        // Create a new file.
        // Note: On subsequent runs, the file handle has _not_ been properly closed.
        // While the file itself is removed, php is unable to create a new file in the same location until the file handle is closed.
        file_put_contents($tmpfile, 'Some example content');
        $this->assertTrue(file_exists($tmpfile));

        $mock = new MockHandler([
            new Response(200, [], 'Success'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $client->request('POST', '/', [
            'multipart' => [
                [
                    'name' => 'filecontents',
                    'contents' => Utils::streamFor(fopen($tmpfile, "r+")),
                ],
            ],
        ]);

        // Unset the client, and unlink the tempfile.
        unset($client);
        unlink($tmpfile);
        $this->assertFalse(file_exists($tmpfile));
    }
}
