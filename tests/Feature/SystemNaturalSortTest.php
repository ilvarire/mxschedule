<?php

use App\Http\Controllers\Admin\HallController;
use App\Http\Controllers\Admin\SystemController;
use App\Models\Hall;
use App\Models\System;

test('system codes are sorted naturally', function () {
    $hall = Hall::create([
        'name' => 'Hall C',
        'code' => 'HC',
        'capacity' => 120,
        'is_active' => true,
    ]);

    foreach (['HC1', 'HC10', 'HC100', 'HC11', 'HC2'] as $code) {
        System::create([
            'hall_id' => $hall->id,
            'system_code' => $code,
            'status' => 'active',
        ]);
    }

    expect(System::naturalSort(System::all())->pluck('system_code')->all())
        ->toBe(['HC1', 'HC2', 'HC10', 'HC11', 'HC100']);
});

test('hall show systems are sorted naturally', function () {
    $hall = Hall::create([
        'name' => 'Hall C',
        'code' => 'HC',
        'capacity' => 120,
        'is_active' => true,
    ]);

    foreach (['HC1', 'HC10', 'HC100', 'HC11', 'HC2'] as $code) {
        System::create([
            'hall_id' => $hall->id,
            'system_code' => $code,
            'status' => 'active',
        ]);
    }

    $view = app(HallController::class)->show($hall);

    expect($view->getData()['hall']->systems->pluck('system_code')->all())
        ->toBe(['HC1', 'HC2', 'HC10', 'HC11', 'HC100']);
});

test('systems index paginator is sorted naturally', function () {
    $hall = Hall::create([
        'name' => 'Hall C',
        'code' => 'HC',
        'capacity' => 120,
        'is_active' => true,
    ]);

    foreach (['HC1', 'HC10', 'HC100', 'HC11', 'HC2'] as $code) {
        System::create([
            'hall_id' => $hall->id,
            'system_code' => $code,
            'status' => 'active',
        ]);
    }

    $view = app(SystemController::class)->index();

    expect($view->getData()['systems']->getCollection()->pluck('system_code')->all())
        ->toBe(['HC1', 'HC2', 'HC10', 'HC11', 'HC100']);
});
