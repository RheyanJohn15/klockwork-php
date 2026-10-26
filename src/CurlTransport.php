<?php

declare(strict_types=1);

namespace Klockwork;

final class CurlTransport
{
    /**
     * @param array<string, string> $headers
     * @param string|array<string, array{filename: string, contents: string, contentType: string}>|null $body
     */
    public function __invoke(string $method, string $url, array $headers, string|array|null $body): TransportResponse
    {
        if (is_array($body)) {
            [$payload, $contentType] = $this->multipart($body);
            $headers['Content-Type'] = $contentType;
            $body = $payload;
        }

        $handle = curl_init($url);
        if ($handle === false) {
            throw new \RuntimeException('Klockwork request failed: unable to initialize curl.');
        }

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $rawHeaders = '';
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $header) use (&$rawHeaders): int {
                $rawHeaders .= $header;

                return strlen($header);
            },
        ]);

        if (is_string($body)) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($handle);
        if ($raw === false) {
            $error = curl_error($handle);
            curl_close($handle);
            throw new \RuntimeException('Klockwork request failed: ' . $error);
        }

        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        return new TransportResponse($status, $this->statusText($rawHeaders), $raw);
    }

    /**
     * @param array<string, array{filename: string, contents: string, contentType: string}> $files
     * @return array{0: string, 1: string}
     */
    private function multipart(array $files): array
    {
        $boundary = 'klockwork-' . bin2hex(random_bytes(8));
        $body = '';
        foreach ($files as $name => $file) {
            $filename = str_replace(["\r", "\n", '"'], '', $file['filename']);
            $contentType = str_replace(["\r", "\n"], '', $file['contentType']);
            $body .= "--{$boundary}\r\n";
            $body .= 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $filename . "\"\r\n";
            $body .= 'Content-Type: ' . $contentType . "\r\n\r\n";
            $body .= $file['contents'] . "\r\n";
        }
        $body .= "--{$boundary}--\r\n";

        return [$body, 'multipart/form-data; boundary=' . $boundary];
    }

    private function statusText(string $headers): string
    {
        $text = '';
        foreach (preg_split("/\r\n|\n|\r/", $headers) ?: [] as $line) {
            if (preg_match('#^HTTP/\S+\s+\d+\s*(.*)$#', $line, $matches) === 1) {
                $text = trim($matches[1]);
            }
        }

        return $text;
    }
}
