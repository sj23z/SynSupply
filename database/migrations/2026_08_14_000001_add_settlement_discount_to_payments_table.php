<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Widens payments.method by two values: 'settlement' and 'discount'.
     * Additive only — existing 'cash'/'bank_transfer'/'other' rows and the
     * column default ('cash') are untouched. Same raw-SQL enum-widening
     * pattern already used successfully in this project (see
     * 2026_08_08_000001_add_sales_rep_role_to_users_table.php and
     * 2026_08_13_000001_add_owner_role_to_users_table.php).
     *
     * Settlement/Discount are non-monetary balance adjustments (a
     * negotiated write-off, not cash received) — they reduce a
     * customer's outstanding balance the same way any payment row
     * already does (Customer::outstandingBalance() and
     * CollectionController::recalculateInvoicePaymentStatus() both sum
     * `amount` across every method with no change required), but they
     * must never be counted as cash on hand. See DashboardController's
     * cashOnHand() for where that distinction is enforced.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('cash', 'bank_transfer', 'other', 'settlement', 'discount') NOT NULL DEFAULT 'cash'");
    }

    public function down(): void
    {
        // Any existing settlement/discount rows are reverted to 'other' so
        // the enum can safely narrow back — this does NOT delete the rows
        // or change their amounts, only their method label, and only runs
        // if this migration is explicitly rolled back.
        DB::statement("UPDATE payments SET method = 'other' WHERE method IN ('settlement', 'discount')");
        DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('cash', 'bank_transfer', 'other') NOT NULL DEFAULT 'cash'");
    }
};
