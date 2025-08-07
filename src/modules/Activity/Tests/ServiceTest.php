<?php

namespace Box\Mod\Activity\Tests;

use Box\Mod\Activity\Service;

uses(\BBTestCase::class);

test('di', function () {
    $service = new Service();

    $di = new \Pimple\Container();
    $db = $this->getMockBuilder('Box_Database')->getMock();

    $di['db'] = $db;
    $service->setDi($di);
    $result = $service->getDi();
    expect($result)->toEqual($di);
});

dataset('searchFilters', function () {
    return [
        [[], 'FROM activity_system ', true],
        [['only_clients' => 'yes'], 'm.client_id IS NOT NULL', true],
        [['only_staff' => 'yes'], 'm.admin_id IS NOT NULL', true],
        [['priority' => '2'], 'm.priority =', true],
        [['search' => 'keyword'], 'm.message LIKE ', true],
        [['no_info' => true], 'm.priority < :priority ', true],
        [['no_debug' => true], 'm.priority < :priority ', true],
    ];
});

test('getSearchQuery', function ($filterKey, $search, $expected) {
    $di = new \Pimple\Container();
    $service = new Service();
    $service->setDi($di);
    $result = $service->getSearchQuery($filterKey);
    expect($result[0])->toBeString();
    expect($result[1])->toBeArray();
    expect(str_contains($result[0], $search))->toBe($expected);
})->with('searchFilters');

test('logEmail', function () {
    $service = new Service();
    $data = [
        'client_id' => random_int(1, 100),
        'sender' => 'sender',
        'recipients' => 'recipients',
        'subject' => 'subject',
        'content_html' => 'html',
        'content_text' => 'text',
    ];

    $model = new \Model_ActivityClientEmail();
    $model->loadBean(new \DummyBean());

    $di = new \Pimple\Container();
    $db = $this->getMockBuilder('Box_Database')->getMock();
    $db->expects($this->atLeastOnce())
        ->method('dispense')
        ->willReturn($model);
    $db->expects($this->atLeastOnce())
        ->method('store')
        ->willReturn([]);

    $di['db'] = $db;
    $service->setDi($di);

    $result = $service->logEmail($data['subject'], $data['client_id'], $data['sender'], $data['recipients'], $data['content_html'], $data['content_text']);
    expect($result)->toBeTrue();
});

test('toApiArray', function () {
    $clientHistoryModel = new \Model_ActivityClientHistory();
    $clientHistoryModel->loadBean(new \DummyBean());
    $clientHistoryModel->client_id = 1;

    $clientModel = new \Model_Client();
    $clientModel->loadBean(new \DummyBean());

    $expectionError = 'Client not found';
    $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
    $dbMock->expects($this->atLeastOnce())
        ->method('getExistingModelById')
        ->with('Client', $clientHistoryModel->client_id, $expectionError)
        ->willReturn($clientModel);

    $di = new \Pimple\Container();
    $di['db'] = $dbMock;

    $service = new Service();
    $service->setDi($di);

    $result = $service->toApiArray($clientHistoryModel);
    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['id', 'ip', 'created_at', 'client']);
    expect($result['client'])->toBeArray();
    expect($result['client'])->toHaveKeys(['id', 'first_name', 'last_name', 'email']);
});

test('rmByClient', function () {
    $clientModel = new \Model_Client();
    $clientModel->loadBean(new \DummyBean());
    $clientModel->id = 1;

    $activitySystemModel = new \Model_ActivitySystem();
    $activitySystemModel->loadBean(new \DummyBean());

    $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
    $dbMock->expects($this->atLeastOnce())
        ->method('find')
        ->with('ActivitySystem', 'client_id = ?', [$clientModel->id])
        ->willReturn([$activitySystemModel]);
    $dbMock->expects($this->atLeastOnce())
        ->method('trash')
        ->with($activitySystemModel);

    $di = new \Pimple\Container();
    $di['db'] = $dbMock;

    $service = new Service();
    $service->setDi($di);

    $service->rmByClient($clientModel);
});
