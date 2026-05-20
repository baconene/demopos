<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    private const TRANSACTIONAL_TABLES = [
        'audit_logs',
        'order_item_modifiers',
        'refunds',
        'payments',
        'order_items',
        'queue_numbers',
        'orders',
        'inventory_transactions',
        'financial_transactions',
        'payroll_records',
        'purchase_order_items',
        'purchase_orders',
    ];

    public function index(): Response
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        return Inertia::render('settings/System');
    }

    public function reset(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $request->validate([
            'confirmation' => ['required', 'in:RESET'],
        ], [
            'confirmation.in' => 'Type RESET exactly to confirm.',
        ]);

        DB::transaction(function () {
            // Disable FK constraints for the duration of the wipe
            try { DB::statement('PRAGMA foreign_keys=OFF'); } catch (\Throwable) {}

            foreach (self::TRANSACTIONAL_TABLES as $table) {
                DB::table($table)->delete();
                // Reset SQLite auto-increment (no-op on MySQL/Postgres)
                try {
                    DB::statement("DELETE FROM sqlite_sequence WHERE name='{$table}'");
                } catch (\Throwable) {}
            }

            // Re-enable FK constraints
            try { DB::statement('PRAGMA foreign_keys=ON'); } catch (\Throwable) {}

            // Zero out all inventory quantities since the transaction log is gone
            Ingredient::query()->update(['current_quantity' => 0]);
        });

        return back()->with('success', 'System has been reset. All transaction history cleared and inventory zeroed.');
    }
}
