<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class StreamedContentTest extends TestCase
{

    /**
     * @dataProvider provider
     */
    public function testStreamedContent(bool $gc): void
    {
        $tmpfile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'testdata' . DIRECTORY_SEPARATOR . 'tmpfile.txt';
        $this->assertFalse(file_exists($tmpfile));

        file_put_contents($tmpfile, 'Some example content');
        $handle = fopen($tmpfile, "r+");
        $this->assertTrue(file_exists($tmpfile));

        $contentStream = Utils::streamFor($handle);

        $mock = new MockHandler([
            new Response(200, [], 'Success'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $client->request('POST', '/', [
            'multipart' => [
                [
                    'name' => 'filecontents',
                    'contents' => $contentStream,
                ],
            ],
        ]);

        if ($gc) {
            $contentStream->close();
        }

        unlink($tmpfile);
        $this->assertFalse(file_exists($tmpfile));
    }

    public static function provider(): array
    {
        return [
            [true],
            [true],
            [false],
            [false],
            [true],
        ];
    }
}
