<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

require_once 'vendor/autoload.php';

function fail(string $message, \Exception $exception = null)
{
    fwrite(STDERR, "ERROR: $message\n");
    if ($exception) {
        fwrite(STDERR, $exception->getMessage()."\n");
    }

    exit(1);
}

function warn(string $message, \Exception $exception = null)
{
    fwrite(STDERR, "WARNING: $message\n");
    if ($exception) {
        fwrite(STDERR, $exception->getMessage()."\n");
    }
}

function fetch_config(): array
{
    try {
        return Yaml::parseFile('spreaded-secrets.yml');
    } catch (ParseException $parseException) {
        fail("Error processing the 'spreaded-secrets.yml' config file", $parseException);
    }
}

function public_key(array $config): string
{
    if (!isset($config['public-key'])) {
        fail('The config file is invalid. It does not contain the public key.');
    }

    if (!$pk = base64_decode($config['public-key'])) {
        fail('Unable to base64-decode the public key. The config file might be corrupt.');
    }

    return $pk;
}

function private_key(): string
{
    if (!$sk = base64_decode(getenv('SPREAD_PRIVATE_KEY'))) {
        fail('The private key must be provided through the SPREAD_PRIVATE_KEY environment variable');
    }

    return $sk;
}

function keypair(array $config): string
{
    try {
        return sodium_crypto_box_keypair_from_secretkey_and_publickey(private_key(), public_key($config));
    } catch (SodiumException $sodiumException) {
        fail('Error processing the keypair', $sodiumException);
    }
}

function fetch_secret(string $keypair, string $file): string
{
    if (!file_exists($file)) {
        fail("The file '$file' does not exist");
    }

    return sodium_crypto_box_seal_open(base64_decode(file_get_contents($file)), $keypair);
}

function get_repositories(array $config): array
{
    if (!isset($config['repositories']) || !is_array($config['repositories'])) {
        fail("The 'repositories' config section is invalid.");
    }

    return $config['repositories'];
}

function list_secrets(Client $client, string $repo): array
{
    try {
        $body = $client->request('GET', "/repos/$repo/actions/secrets")->getBody();
        $data = json_decode($body, true);

        $names = array_map(function ($entry) {return $entry['name']; }, $data['secrets']);

        return array_combine($names, $names);
    } catch (ClientException $exception) {
        fail("Failed to fetch existing secrets for the '$repo' repository from the API", $exception);
    }
}

function fetch_repo_key(Client $client, string $repo): array
{
    try {
        $body = $client->request('GET', "/repos/$repo/actions/secrets/public-key")->getBody();
        $data = json_decode($body, true);

        return [$data['key_id'], base64_decode($data['key'])];
    } catch (ClientException $exception) {
        fail("Failed to fetch the public key for the '$repo' repository from the API", $exception);
    }
}

function put_secret(Client $client, string $repo, string $secretName, string $encryptedSecret, string $keyId)
{
    try {
        $client->request('PUT', "/repos/$repo/actions/secrets/$secretName", ['json' => [
            'encrypted_value' => $encryptedSecret,
            'key_id' => $keyId,
        ]]);
    } catch (ClientException $exception) {
        warn("Failed to update the '$secretName' secret for the '$repo' repository", $exception);
    }
}

function update_secret(): void
{
    $secretValue = fetch_secret($keypair, $file);
    $encryptedValue = encrypt_64($secretValue, $key);
    put_secret($client, $repo, $secretName, $encryptedValue, $keyId);
}

function delete_secret(Client $client, string $repo, string $secretName)
{
    try {
        $client->request('DELETE', "/repos/$repo/actions/secrets/$secretName");
    } catch (ClientException $exception) {
        warn("Failed to remove the '$secretName' secret from the '$repo' repository", $exception);
    }
}

function encrypt_64(string $message, string $publicKey): string
{
    try {
        return base64_encode(sodium_crypto_box_seal($message, $publicKey));
    } catch (SodiumException $sodiumException) {
        fail('Failed to encrypt a secret value', $sodiumException);
    }
}
