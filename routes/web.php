<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware(['auth'])
    ->get('/admin/import-reports/{filename}', function (string $filename) {
        abort_unless(preg_match('/^[A-Za-z0-9._-]+$/', $filename) === 1, 404);

        $path = storage_path('app/import-reports/' . $filename);
        abort_unless(is_file($path), 404);

        return response()->download($path, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    })
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('import-reports.download');
