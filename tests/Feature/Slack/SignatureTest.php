<?php

beforeEach(function () {
    fakeSlackConfig();
});

it('accepte une requête correctement signée', function () {
    postFromSlack('/slack/command', ['command' => '/inconnue'])
        ->assertOk()
        ->assertJson(['response_type' => 'ephemeral']);
});

it('rejette une signature invalide', function () {
    postFromSlack('/slack/command', ['command' => '/lieu'], signature: 'v0=deadbeef')
        ->assertUnauthorized();
});

it('rejette une requête signée avec un autre secret', function () {
    $body = http_build_query(['command' => '/lieu']);
    $timestamp = now()->getTimestamp();

    postFromSlack(
        '/slack/command',
        ['command' => '/lieu'],
        signature: 'v0='.hash_hmac('sha256', "v0:{$timestamp}:{$body}", 'mauvais-secret'),
    )->assertUnauthorized();
});

it('rejette un timestamp de plus de 5 minutes', function () {
    postFromSlack('/slack/command', ['command' => '/inconnue'], timestamp: now()->subMinutes(6)->getTimestamp())
        ->assertUnauthorized();
});

it('rejette une requête sans en-têtes Slack', function () {
    $this->post('/slack/interact', ['payload' => '{}'])
        ->assertUnauthorized();
});

it('rejette tout si le signing secret n\'est pas configuré', function () {
    config(['services.slack.signing_secret' => null]);

    postFromSlack('/slack/command', ['command' => '/inconnue'], signature: 'v0=')
        ->assertUnauthorized();
});
