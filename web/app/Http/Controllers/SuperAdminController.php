<?php
declare(strict_types=1);

namespace App\Http\Controllers;

/**
 * Panel Super Admin di prefix /superadmin.
 * Seluruh logika diwarisi dari AdminController; guard role + prefix
 * diatur otomatis via $panel = 'superadmin'.
 */
final class SuperAdminController extends AdminController {
    protected string $panel = 'superadmin';
}
