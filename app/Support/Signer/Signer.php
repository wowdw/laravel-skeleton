<?php

namespace App\Support\Signer;

abstract class Signer
{
    abstract public function sign(array $payload): string;

    abstract public function validate(string $signature, array $payload): bool;

    protected function sort(array $payload)
    {
        ksort($payload);

        return $payload;
    }

    protected function getPreEncryptedData(array $payload): string
    {
        $sortedPayload = $this->sort($payload);

        return urldecode(http_build_query($sortedPayload));
    }
}
