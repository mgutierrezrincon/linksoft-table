<?php

namespace App\Helpers;

use App\Models\AccountingNote;
use App\Models\AccountingNoteDetail;
use App\Models\Buy;
use App\Models\Customer;
use App\Models\Devolution;
use App\Models\ExpenseCausation;
use App\Models\IncomeReceipt;
use App\Models\OutcomeReceipt;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Support\Facades\Log;

class AccountingNotes
{
    // Asiento contable en la causacion
    public static function expenseCausationNote(?ExpenseCausation $expenseCausation = null, $buyId = null)
    {
        $expenseCausation = $expenseCausation ?? ExpenseCausation::find($buyId);

        if ($accountingNote = AccountingNote::where('origin_id', $expenseCausation->id)->where('origin_type', ExpenseCausation::class)->first()) {
            $accountingNote->delete();
        }

        $accountingNote = new AccountingNote();
        $accountingNote->origin_id = $expenseCausation->id;
        $accountingNote->origin_type = ExpenseCausation::class;
        $accountingNote->third_party_id = $expenseCausation->supplier_id;
        $accountingNote->third_party_type = Supplier::class;
        $accountingNote->amount = $expenseCausation->total;
        $accountingNote->accounting_note_type_id = Helper::getProcessAccountingAccount('accounting_note_types.expense_causations');
        $accountingNote->status = AccountingNote::STATUS_ACTIVE;
        $accountingNote->note = $expenseCausation->invoice_number;
        $accountingNote->note_date = $expenseCausation->date ?? $expenseCausation->invoice_date;
        $accountingNote->consecutive = AccountingNote::whereAccountingNoteTypeId($accountingNote->accounting_note_type_id)->max('consecutive') + 1;
        $accountingNote->created_by = auth()->user()?->id;
        $accountingNote->save();

        $total = 0;

        $details = [];

        foreach ($expenseCausation->expenseCausationImputations as $expenseCausationImputation) {
            // imputacion
            self::addDetail($details, [
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $expenseCausationImputation->expenseImputation->accounting_account_id,
                'third_party_type' => Supplier::class,
                'third_party_id' => $expenseCausation->supplier_id,
                'cost_center_id' => $expenseCausationImputation->cost_center_id,
                'debit' => 'debit' == $expenseCausationImputation->expenseImputation->nature ? $expenseCausationImputation->subtotal : 0,
                'credit' => 'credit' == $expenseCausationImputation->expenseImputation->nature ? $expenseCausationImputation->subtotal : 0,
            ]);

            if ($expenseCausationImputation->iva_imputation_id > 0) {
                // iva
                self::addDetail($details, [
                    'accounting_note_id' => $accountingNote->id,
                    'accounting_account_id' => $expenseCausationImputation->ivaImputation->accounting_account_id,
                    'third_party_type' => Supplier::class,
                    'third_party_id' => $expenseCausation->supplier_id,
                    'cost_center_id' => $expenseCausationImputation->cost_center_id,
                    'debit' => 'debit' == $expenseCausationImputation->ivaImputation->nature ? $expenseCausationImputation->iva : 0,
                    'credit' => 'credit' == $expenseCausationImputation->ivaImputation->nature ? $expenseCausationImputation->iva : 0,
                ]);
            }

            if ($expenseCausationImputation->retefuente_imputation_id > 0) {
                // retefuente
                self::addDetail($details, [
                    'accounting_note_id' => $accountingNote->id,
                    'accounting_account_id' => $expenseCausationImputation->retefuenteImputation->accounting_account_id,
                    'third_party_type' => Supplier::class,
                    'third_party_id' => $expenseCausation->supplier_id,
                    'cost_center_id' => $expenseCausationImputation->cost_center_id,
                    'debit' => 'debit' == $expenseCausationImputation->retefuenteImputation->nature ? $expenseCausationImputation->retefuente : 0,
                    'credit' => 'credit' == $expenseCausationImputation->retefuenteImputation->nature ? $expenseCausationImputation->retefuente : 0,
                ]);
            }

            if ($expenseCausationImputation->reteiva_imputation_id > 0) {
                // reteiva
                self::addDetail($details, [
                    'accounting_note_id' => $accountingNote->id,
                    'accounting_account_id' => $expenseCausationImputation->reteivaImputation->accounting_account_id,
                    'third_party_type' => Supplier::class,
                    'third_party_id' => $expenseCausation->supplier_id,
                    'cost_center_id' => $expenseCausationImputation->cost_center_id,
                    'debit' => 'debit' == $expenseCausationImputation->reteivaImputation->nature ? $expenseCausationImputation->reteiva : 0,
                    'credit' => 'credit' == $expenseCausationImputation->reteivaImputation->nature ? $expenseCausationImputation->reteiva : 0,
                ]);
            }

            if ($expenseCausationImputation->reteica_imputation_id > 0) {
                // reteica
                self::addDetail($details, [
                    'accounting_note_id' => $accountingNote->id,
                    'accounting_account_id' => $expenseCausationImputation->reteicaImputation->accounting_account_id,
                    'third_party_type' => Supplier::class,
                    'third_party_id' => $expenseCausation->supplier_id,
                    'cost_center_id' => $expenseCausationImputation->cost_center_id,
                    'debit' => 'debit' == $expenseCausationImputation->reteicaImputation->nature ? $expenseCausationImputation->reteica : 0,
                    'credit' => 'credit' == $expenseCausationImputation->reteicaImputation->nature ? $expenseCausationImputation->reteica : 0,
                ]);
            }
        }

        $supplier = Supplier::find($expenseCausation->supplier_id);

        self::addDetail($details, [
            'accounting_note_id' => $accountingNote->id,
            'accounting_account_id' => $supplier->payable_accounting_account_id ?? Helper::getProcessAccountingAccount('suppliers.payable'),
            'third_party_type' => Supplier::class,
            'third_party_id' => $supplier->id,
            'cost_center_id' => null,
            'debit' => 0,
            'credit' => $expenseCausation->total,
        ]);

        foreach ($details as $detail) {
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $detail['accounting_account_id'],
                'third_party_type' => $detail['third_party_type'],
                'third_party_id' => $detail['third_party_id'],
                'cost_center_id' => $detail['cost_center_id'],
                'nature' => $detail['debit'] > 0 ? AccountingNoteDetail::NATURE_DEBIT : AccountingNoteDetail::NATURE_CREDIT,
                'amount' => $detail['debit'] > 0 ? $detail['debit'] : $detail['credit'],
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);
        }

        return $accountingNote;
    }

    // Asiento contable en la compra
    public static function buyNote(?Buy $buy = null, $buyId = null)
    {
        $buy = $buy ?? Buy::find($buyId);

        if ($accountingNote = AccountingNote::where('origin_id', $buy->id)->where('origin_type', Buy::class)->first()) {
            // return $accountingNote;
            $accountingNote->delete();
        }

        $accountingNote = new AccountingNote();
        $accountingNote->origin_id = $buy->id;
        $accountingNote->origin_type = Buy::class;
        $accountingNote->third_party_id = $buy->supplier_id;
        $accountingNote->third_party_type = Supplier::class;
        $accountingNote->amount = $buy->total;
        $accountingNote->accounting_note_type_id = Helper::getProcessAccountingAccount('accounting_note_types.buys');
        $accountingNote->status = AccountingNote::STATUS_ACTIVE;
        $accountingNote->note = $buy->notes;
        $accountingNote->note_date = $buy->date;
        $accountingNote->consecutive = AccountingNote::whereAccountingNoteTypeId($accountingNote->accounting_note_type_id)->max('consecutive') + 1;
        $accountingNote->created_by = auth()->user()?->id;
        $accountingNote->save();

        $total = 0;

        $details = [];

        foreach ($buy->buyProducts as $buyProduct) {
            // inventario
            self::addDetail($details, [
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $buyProduct->product->stock_accounting_account_id ?? Helper::getProcessAccountingAccount('products.stock'),
                'third_party_type' => Supplier::class,
                'third_party_id' => $buy->supplier_id,
                'cost_center_id' => null,
                'debit' => $buyProduct->subtotal,
                'credit' => 0,
            ]);

            if ($buyProduct->tax_id > 0) {
                // impuesto
                self::addDetail($details, [
                    'accounting_note_id' => $accountingNote->id,
                    'accounting_account_id' => $buyProduct->tax->businessAccountingAccount('buy_'.$buyProduct->tax_percent)?->accounting_account_id ?? Helper::getProcessAccountingAccount('taxes.buy_tax'),
                    'third_party_type' => Supplier::class,
                    'third_party_id' => $buy->supplier_id,
                    'cost_center_id' => null,
                    'debit' => $buyProduct->taxes,
                    'credit' => 0,
                ]);
            }
        }

        // retefuente
        if ($buy->retefuente > 0) {
            self::addDetail($details, [
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => Helper::getProcessAccountingAccount('retentions.buy_retefuente'),
                'third_party_type' => Supplier::class,
                'third_party_id' => $buy->supplier_id,
                'cost_center_id' => null,
                'debit' => 0,
                'credit' => $buy->retefuente,
            ]);

            $total += $buy->retefuente;
        }

        // reteiva
        if ($buy->reteiva > 0) {
            self::addDetail($details, [
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => Helper::getProcessAccountingAccount('retentions.buy_reteiva'),
                'third_party_type' => Supplier::class,
                'third_party_id' => $buy->supplier_id,
                'cost_center_id' => null,
                'debit' => 0,
                'credit' => $buy->reteiva,
            ]);

            $total += $buy->reteiva;
        }

        // reteica
        if ($buy->reteica > 0) {
            self::addDetail($details, [
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => Helper::getProcessAccountingAccount('retentions.buy_reteica'),
                'third_party_type' => Supplier::class,
                'third_party_id' => $buy->supplier_id,
                'cost_center_id' => null,
                'debit' => 0,
                'credit' => $buy->reteica,
            ]);

            $total += $buy->reteica;
        }

        // $buyAccount = null;

        // switch ($buy->payment_method) {
        //     case Payment::METHOD_CASH:
        //         $buyAccount = $buy->paymentBox?->accounting_account_id;

        //         break;
        //     case Payment::METHOD_CREDIT:
        //         $buy->supplier?->payableAccountingAccount?->id;

        //         break;
        //     case Payment::METHOD_BANK_TRANSFER:
        //         $buyAccount = $buy->bankAccount?->accounting_account_id;

        //         break;
        //     default:
        //         break;
        // }
        self::addDetail($details, [
            'accounting_note_id' => $accountingNote->id,
            'accounting_account_id' => $buy->supplier->payable_accounting_account_id ?? Helper::getProcessAccountingAccount('suppliers.payable'),
            'third_party_type' => Supplier::class,
            'third_party_id' => $buy->supplier_id,
            'cost_center_id' => null,
            'debit' => 0,
            'credit' => $buy->total,
        ]);

        $total += $buy->price;

        foreach ($details as $detail) {
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $detail['accounting_account_id'],
                'third_party_type' => $detail['third_party_type'],
                'third_party_id' => $detail['third_party_id'],
                'cost_center_id' => $detail['cost_center_id'],
                'nature' => $detail['debit'] > 0 ? AccountingNoteDetail::NATURE_DEBIT : AccountingNoteDetail::NATURE_CREDIT,
                'amount' => $detail['debit'] > 0 ? $detail['debit'] : $detail['credit'],
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);
        }

        $accountingNote->amount = $total;
        $accountingNote->save();

        return $accountingNote;
    }

    // Asiento contable en la devolucion
    public static function DevolutionNote(?Devolution $devolution = null, $devolutionId = null)
    {
        $devolution = $devolution ?? Devolution::find($devolutionId);

        if ($accountingNote = AccountingNote::where('origin_id', $devolution->id)->where('origin_type', Devolution::class)->first()) {
            $accountingNote->delete();
        }

        $accountingNote = new AccountingNote();
        $accountingNote->origin_id = $devolution->id;
        $accountingNote->origin_type = Devolution::class;
        $accountingNote->third_party_id = $devolution->customer_id;
        $accountingNote->third_party_type = Customer::class;
        $accountingNote->amount = $devolution->total;
        $accountingNote->accounting_note_type_id = Helper::getProcessAccountingAccount('accounting_note_types.devolutions');
        $accountingNote->status = AccountingNote::STATUS_ACTIVE;
        $accountingNote->note = $devolution->notes;
        $accountingNote->note_date = $devolution->created_at;
        $accountingNote->consecutive = AccountingNote::whereAccountingNoteTypeId($accountingNote->accounting_note_type_id)->max('consecutive') + 1;
        $accountingNote->created_by = auth()->user()?->id;
        $accountingNote->save();

        $total = 0;

        foreach ($devolution->devolutionProducts as $devolutionProduct) {
            // costo
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $devolutionProduct->product->sale_cost_accounting_account_id ?? Helper::getProcessAccountingAccount('products.sale_cost'),
                'third_party_type' => Customer::class,
                'third_party_id' => $devolution->customer_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_CREDIT,
                'amount' => round($devolutionProduct->product->cost * $devolutionProduct->quantity, 2),
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);

            $total += round($devolutionProduct->product->cost * $devolutionProduct->quantity, 2);

            // inventario
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $devolutionProduct->product->stock_accounting_account_id ?? Helper::getProcessAccountingAccount('products.stock'),
                'third_party_type' => Customer::class,
                'third_party_id' => $devolution->customer_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_DEBIT,
                'amount' => round($devolutionProduct->product->cost * $devolutionProduct->quantity, 2),
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);

            // ingreso
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $devolutionProduct->product->income_accounting_account_id ?? Helper::getProcessAccountingAccount('products.income'),
                'third_party_type' => Customer::class,
                'third_party_id' => $devolution->customer_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_DEBIT,
                'amount' => $devolutionProduct->subtotal,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);

            if ($devolutionProduct->tax_percent > 0) {
                // impuesto
                AccountingNoteDetail::create([
                    'accounting_note_id' => $accountingNote->id,
                    'accounting_account_id' => Helper::getProcessAccountingAccount('taxes.tax_19'),
                    'third_party_type' => Customer::class,
                    'third_party_id' => $devolution->customer_id,
                    // 'cost_center_id',
                    'nature' => AccountingNoteDetail::NATURE_DEBIT,
                    'amount' => $devolutionProduct->taxes,
                    'status' => AccountingNoteDetail::STATUS_ACTIVE,
                ]);
            }
        }

        $account = null;

        switch ($devolution->payment_type) {
            case Devolution::PAYMENT_TYPE_ADVANCE:
                $account = $devolution->customer?->payable_accounting_account_id ?? Helper::getProcessAccountingAccount('customers.payable');

                break;
            case Devolution::PAYMENT_TYPE_CREDIT_FINISH:
                $account = $devolution->customer?->receivable_accounting_account_id ?? Helper::getProcessAccountingAccount('customers.receivable');

                break;
            case Devolution::PAYMENT_TYPE_CASH_DEVOLUTION:
                $account = $devolution->customer?->receivable_accounting_account_id ?? Helper::getProcessAccountingAccount('customers.receivable');

                break;
            default:
                break;
        }
        AccountingNoteDetail::create([
            'accounting_note_id' => $accountingNote->id,
            'accounting_account_id' => $account ?? Helper::getProcessAccountingAccount('payment_forms.cash'),
            'third_party_type' => Customer::class,
            'third_party_id' => $devolution->customer_id,
            // 'cost_center_id',
            'nature' => AccountingNoteDetail::NATURE_CREDIT,
            'amount' => $devolution->total,
            'status' => AccountingNoteDetail::STATUS_ACTIVE,
        ]);

        $total = $devolution->total;

        // retefuente
        if ($devolution->retefuente > 0) {
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => Helper::getProcessAccountingAccount('retentions.retefuente'),
                'third_party_type' => Customer::class,
                'third_party_id' => $devolution->customer_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_CREDIT,
                'amount' => $devolution->retefuente,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);

            $total += $devolution->retefuente;
        }

        // reteiva
        if ($devolution->reteiva > 0) {
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => Helper::getProcessAccountingAccount('retentions.reteiva'),
                'third_party_type' => Customer::class,
                'third_party_id' => $devolution->customer_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_CREDIT,
                'amount' => $devolution->reteiva,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);

            $total += $devolution->reteiva;
        }

        // reteica
        if ($devolution->reteica > 0) {
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => Helper::getProcessAccountingAccount('retentions.reteica'),
                'third_party_type' => Customer::class,
                'third_party_id' => $devolution->customer_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_CREDIT,
                'amount' => $devolution->reteica,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);

            $total += $devolution->reteica;
        }

        $accountingNote->amount = $total;
        $accountingNote->save();

        return $accountingNote;

        try {
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    // Asiento contable en comprobante ingreso
    public static function incomeReceiptNote(?IncomeReceipt $incomeReceipt = null, $incomeReceiptId = null)
    {
        $incomeReceipt = $incomeReceipt ?? IncomeReceipt::find($incomeReceiptId);

        if ($accountingNote = AccountingNote::where('origin_id', $incomeReceipt->id)->where('origin_type', IncomeReceipt::class)->first()) {
            $accountingNote->delete();
        }

        $accountingNote = new AccountingNote();
        $accountingNote->origin_id = $incomeReceipt->id;
        $accountingNote->origin_type = IncomeReceipt::class;
        $accountingNote->third_party_id = $incomeReceipt->customer_id;
        $accountingNote->third_party_type = Customer::class;
        $accountingNote->amount = $incomeReceipt->total;
        $accountingNote->accounting_note_type_id = Helper::getProcessAccountingAccount('accounting_note_types.income_receipts');
        $accountingNote->status = AccountingNote::STATUS_ACTIVE;
        $accountingNote->note = $incomeReceipt->notes;
        $accountingNote->note_date = $incomeReceipt->date;
        $accountingNote->consecutive = AccountingNote::whereAccountingNoteTypeId($accountingNote->accounting_note_type_id)->max('consecutive') + 1;
        $accountingNote->created_by = auth()->user()?->id;
        $accountingNote->save();

        $customer = $accountingNote->thirdParty;

        if ('portfolio' == $incomeReceipt->type) {
            // cuenta por cobrar
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $customer->receivable_accounting_account_id ?? Helper::getProcessAccountingAccount('customers.receivable'),
                'third_party_type' => Customer::class,
                'third_party_id' => $incomeReceipt->customer_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_CREDIT,
                'amount' => $incomeReceipt->total - $incomeReceipt->advance,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);
        }

        if ($incomeReceipt->advance > 0) {
            // anticipo
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $customer->payable_accounting_account_id ?? Helper::getProcessAccountingAccount('customers.payable'),
                'third_party_type' => Customer::class,
                'third_party_id' => $incomeReceipt->customer_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_CREDIT,
                'amount' => $incomeReceipt->advance,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);
        }
        // cuenta de pago
        $account = null;

        switch ($incomeReceipt->payment_method) {
            case Payment::METHOD_CREDIT:
                $account = $customer?->receivable_accounting_account_id ?? Helper::getProcessAccountingAccount('customers.receivable');

                break;
            case Payment::METHOD_BANK_TRANSFER:
                $account = $incomeReceipt->bankAccount?->accounting_account_id;

                break;
            case Payment::METHOD_CASH:
                $account = $incomeReceipt->paymentBox?->accounting_account_id;

                break;
            case Payment::METHOD_BALANCE_IN_FAVOR:
                $account = $customer?->payable_accounting_account_id ?? Helper::getProcessAccountingAccount('customers.payable');

                break;
            default:
                break;
        }
        AccountingNoteDetail::create([
            'accounting_note_id' => $accountingNote->id,
            'accounting_account_id' => $account ?? Helper::getProcessAccountingAccount('payment_forms.'.$incomeReceipt->payment_method),
            'third_party_type' => Customer::class,
            'third_party_id' => $incomeReceipt->customer_id,
            // 'cost_center_id',
            'nature' => AccountingNoteDetail::NATURE_DEBIT,
            'amount' => $incomeReceipt->total,
            'status' => AccountingNoteDetail::STATUS_ACTIVE,
        ]);

        return $accountingNote;
    }

    // Asiento contable en comprobante egreso
    public static function outcomeReceiptNote(?OutcomeReceipt $outcomeReceipt = null, $outcomeReceiptId = null)
    {
        $outcomeReceipt = $outcomeReceipt ?? OutcomeReceipt::find($outcomeReceiptId);

        if ($accountingNote = AccountingNote::where('origin_id', $outcomeReceipt->id)->where('origin_type', OutcomeReceipt::class)->first()) {
            $accountingNote->delete();
        }

        $accountingNote = new AccountingNote();
        $accountingNote->origin_id = $outcomeReceipt->id;
        $accountingNote->origin_type = OutcomeReceipt::class;
        $accountingNote->third_party_id = $outcomeReceipt->third_party_id;
        $accountingNote->third_party_type = $outcomeReceipt->third_party_type;
        $accountingNote->amount = $outcomeReceipt->total;
        $accountingNote->accounting_note_type_id = Helper::getProcessAccountingAccount('accounting_note_types.outcome_receipts');
        $accountingNote->status = AccountingNote::STATUS_ACTIVE;
        $accountingNote->note = $outcomeReceipt->notes;
        $accountingNote->note_date = $outcomeReceipt->date;
        $accountingNote->consecutive = AccountingNote::whereAccountingNoteTypeId($accountingNote->accounting_note_type_id)->max('consecutive') + 1;
        $accountingNote->created_by = auth()->user()?->id;
        $accountingNote->save();

        $supplier = $accountingNote->thirdParty;

        $details = [];

        if ('portfolio' == $outcomeReceipt->type) {
            foreach ($outcomeReceipt->outcomeReceiptBuys as $outcomeReceiptBuy) {
                $account = $outcomeReceipt->payable_accounting_account_id ?? $supplier->payable_accounting_account_id ?? Helper::getProcessAccountingAccount('suppliers.payable');

                // if ($outcomeReceiptBuy->expense_causation_id > 0) {
                //     foreach ($outcomeReceiptBuy->expenseCausation->expenseCausationImputations ?? [] as $expenseImputation) {
                //         if ($expenseImputation->expenseImputation->accountingAccount->code > 23000000 && $expenseImputation->expenseImputation->accountingAccount->code < 24000000) {
                //             $account = $expenseImputation->expenseImputation->accountingAccount->id;
                //         }
                //     }
                // }

                // cuenta por cobrar
                self::addDetail($details, [
                    'accounting_note_id' => $accountingNote->id,
                    'accounting_account_id' => $account,
                    'third_party_type' => $outcomeReceipt->third_party_type,
                    'third_party_id' => $outcomeReceipt->third_party_id,
                    'cost_center_id' => null,
                    'nature' => AccountingNoteDetail::NATURE_DEBIT,
                    'amount' => $outcomeReceiptBuy->total_receipt > 0 ? $outcomeReceiptBuy->total_receipt : $outcomeReceiptBuy->total,
                    'status' => AccountingNoteDetail::STATUS_ACTIVE,
                ]);

                if ($outcomeReceiptBuy->discount > 0) {
                    // descuento
                    self::addDetail($details, [
                        'accounting_note_id' => $accountingNote->id,
                        'accounting_account_id' => Helper::getProcessAccountingAccount('outcome_receipts.discount'),
                        'third_party_type' => $outcomeReceipt->third_party_type,
                        'third_party_id' => $outcomeReceipt->third_party_id,
                        'cost_center_id' => null,
                        'debit' => 0,
                        'credit' => $outcomeReceiptBuy->discount,
                    ]);
                }

                if ($outcomeReceiptBuy->iva_retention_imputation_id > 0) {
                    // iva
                    self::addDetail($details, [
                        'accounting_note_id' => $accountingNote->id,
                        'accounting_account_id' => $outcomeReceiptBuy->ivaRetentionImputation->accounting_account_id,
                        'third_party_type' => $outcomeReceipt->third_party_type,
                        'third_party_id' => $outcomeReceipt->third_party_id,
                        'cost_center_id' => null,
                        'debit' => 'debit' == $outcomeReceiptBuy->ivaRetentionImputation->nature ? $outcomeReceiptBuy->iva_retention : 0,
                        'credit' => 'credit' == $outcomeReceiptBuy->ivaRetentionImputation->nature ? $outcomeReceiptBuy->iva_retention : 0,
                    ]);
                }

                if ($outcomeReceiptBuy->ica_retention_imputation_id > 0) {
                    // reteica
                    self::addDetail($details, [
                        'accounting_note_id' => $accountingNote->id,
                        'accounting_account_id' => $outcomeReceiptBuy->icaRetentionImputation->accounting_account_id,
                        'third_party_type' => $outcomeReceipt->third_party_type,
                        'third_party_id' => $outcomeReceipt->third_party_id,
                        'cost_center_id' => null,
                        'debit' => 'debit' == $outcomeReceiptBuy->icaRetentionImputation->nature ? $outcomeReceiptBuy->ica_retention : 0,
                        'credit' => 'credit' == $outcomeReceiptBuy->icaRetentionImputation->nature ? $outcomeReceiptBuy->ica_retention : 0,
                    ]);
                }

                if ($outcomeReceiptBuy->fte_retention_imputation_id > 0) {
                    // retefuente
                    self::addDetail($details, [
                        'accounting_note_id' => $accountingNote->id,
                        'accounting_account_id' => $outcomeReceiptBuy->fteRetentionImputation->accounting_account_id,
                        'third_party_type' => $outcomeReceipt->third_party_type,
                        'third_party_id' => $outcomeReceipt->third_party_id,
                        'cost_center_id' => null,
                        'debit' => 'debit' == $outcomeReceiptBuy->fteRetentionImputation->nature ? $outcomeReceiptBuy->fte_retention : 0,
                        'credit' => 'credit' == $outcomeReceiptBuy->fteRetentionImputation->nature ? $outcomeReceiptBuy->fte_retention : 0,
                    ]);
                }
            }
        } elseif ('balance_payment' == $outcomeReceipt->type) {
            // cuenta por cobrar
            self::addDetail($details, [
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $outcomeReceipt->payable_accounting_account_id ?? $supplier?->payable_accounting_account_id ?? Helper::getProcessAccountingAccount('suppliers.payable'),
                'third_party_type' => $outcomeReceipt->third_party_type,
                'third_party_id' => $outcomeReceipt->third_party_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_DEBIT,
                'amount' => $outcomeReceipt->total,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);
        } else {
            // cuenta por cobrar
            self::addDetail($details, [
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $outcomeReceipt->payable_accounting_account_id ?? Helper::getProcessAccountingAccount('suppliers.receivable'),
                'third_party_type' => $outcomeReceipt->third_party_type,
                'third_party_id' => $outcomeReceipt->third_party_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_DEBIT,
                'amount' => $outcomeReceipt->total,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);
        }
        // cuenta de pago

        foreach ($outcomeReceipt->outcomeReceiptPayments as $outcomeReceiptPayment) {
            $account = null;

            switch ($outcomeReceiptPayment->payment_method) {
                case Payment::METHOD_CREDIT:
                    $account = $supplier?->receivable_accounting_account_id ?? Helper::getProcessAccountingAccount('suppliers.receivable');

                    break;
                case Payment::METHOD_BANK_TRANSFER:
                    $account = $outcomeReceiptPayment->bankAccount?->accounting_account_id;

                    break;
                case Payment::METHOD_CASH:
                    $account = $outcomeReceiptPayment->paymentBox?->accounting_account_id;

                    break;
                case Payment::METHOD_BALANCE_IN_FAVOR:
                    $account = $supplier?->payable_accounting_account_id ?? Helper::getProcessAccountingAccount('suppliers.payable');

                    break;
                default:
                    break;
            }
            self::addDetail($details, [
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $account ?? Helper::getProcessAccountingAccount('payment_forms.'.$outcomeReceiptPayment->payment_method),
                'third_party_type' => $outcomeReceipt->third_party_type,
                'third_party_id' => $outcomeReceipt->third_party_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_CREDIT,
                'amount' => $outcomeReceiptPayment->amount,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);
        }

        foreach ($details as $detail) {
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $detail['accounting_account_id'],
                'third_party_type' => $detail['third_party_type'],
                'third_party_id' => $detail['third_party_id'],
                'cost_center_id' => $detail['cost_center_id'],
                'nature' => $detail['debit'] > 0 ? AccountingNoteDetail::NATURE_DEBIT : AccountingNoteDetail::NATURE_CREDIT,
                'amount' => $detail['debit'] > 0 ? $detail['debit'] : $detail['credit'],
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);
        }

        return $accountingNote;
    }

    // Asiento contable en la venta
    public static function SaleNote(?Sale $sale = null, $saleId = null)
    {
        $sale = $sale ?? Sale::find($saleId);

        if ($accountingNote = AccountingNote::where('origin_id', $sale->id)->where('origin_type', Sale::class)->first()) {
            $accountingNote->delete();
        }

        $accountingNote = new AccountingNote();
        $accountingNote->origin_id = $sale->id;
        $accountingNote->origin_type = Sale::class;
        $accountingNote->third_party_id = $sale->customer_id;
        $accountingNote->third_party_type = Customer::class;
        $accountingNote->amount = $sale->total;
        $accountingNote->accounting_note_type_id = Helper::getProcessAccountingAccount('accounting_note_types.sales');
        $accountingNote->status = AccountingNote::STATUS_ACTIVE;
        $accountingNote->note = $sale->notes;
        $accountingNote->note_date = $sale->date;
        $accountingNote->consecutive = AccountingNote::whereAccountingNoteTypeId($accountingNote->accounting_note_type_id)->max('consecutive') + 1;
        $accountingNote->created_by = auth()->user()?->id;
        $accountingNote->save();

        $total = 0;

        foreach ($sale->saleProducts as $saleProduct) {
            // costo
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $saleProduct->product->sale_cost_accounting_account_id ?? Helper::getProcessAccountingAccount('products.sale_cost'),
                'third_party_type' => Customer::class,
                'third_party_id' => $sale->customer_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_DEBIT,
                'amount' => $saleProduct->product->cost,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);

            $total += $saleProduct->product->cost;

            // inventario
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $saleProduct->product->stock_accounting_account_id ?? Helper::getProcessAccountingAccount('products.stock'),
                'third_party_type' => Customer::class,
                'third_party_id' => $sale->customer_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_CREDIT,
                'amount' => $saleProduct->product->cost,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);

            // ingreso
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $saleProduct->product->income_accounting_account_id ?? Helper::getProcessAccountingAccount('products.income'),
                'third_party_type' => Customer::class,
                'third_party_id' => $sale->customer_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_CREDIT,
                'amount' => $saleProduct->subtotal,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);

            if ($saleProduct->tax_id > 0) {
                // impuesto
                AccountingNoteDetail::create([
                    'accounting_note_id' => $accountingNote->id,
                    'accounting_account_id' => $saleProduct->tax->businessAccountingAccount($saleProduct->tax_percent)?->accounting_account_id ?? Helper::getProcessAccountingAccount('taxes.tax'),
                    'third_party_type' => Customer::class,
                    'third_party_id' => $sale->customer_id,
                    // 'cost_center_id',
                    'nature' => AccountingNoteDetail::NATURE_CREDIT,
                    'amount' => $saleProduct->taxes,
                    'status' => AccountingNoteDetail::STATUS_ACTIVE,
                ]);
            }
        }

        // pagos
        foreach ($sale->order_id > 0 ? $sale->order?->payments : $sale->payments as $payment) {
            $account = null;

            switch ($payment->payment_method) {
                case Payment::METHOD_CREDIT:
                    $account = $sale->customer?->receivable_accounting_account_id ?? Helper::getProcessAccountingAccount('customers.receivable');

                    break;
                case Payment::METHOD_BANK_TRANSFER:
                    $account = $payment->bankAccount?->accounting_account_id;

                    break;
                default:
                    break;
            }
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => $account ?? Helper::getProcessAccountingAccount('payment_forms.'.$payment->payment_method),
                'third_party_type' => Customer::class,
                'third_party_id' => $sale->customer_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_DEBIT,
                'amount' => $payment->price,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);

            $total += $payment->price;
        }

        // retefuente
        if ($sale->retefuente > 0) {
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => Helper::getProcessAccountingAccount('retentions.retefuente'),
                'third_party_type' => Customer::class,
                'third_party_id' => $sale->customer_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_DEBIT,
                'amount' => $payment->retefuente,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);

            $total += $payment->retefuente;
        }

        // reteiva
        if ($sale->reteiva > 0) {
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => Helper::getProcessAccountingAccount('retentions.reteiva'),
                'third_party_type' => Customer::class,
                'third_party_id' => $sale->customer_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_DEBIT,
                'amount' => $payment->reteiva,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);

            $total += $payment->reteiva;
        }

        // reteica
        if ($sale->reteica > 0) {
            AccountingNoteDetail::create([
                'accounting_note_id' => $accountingNote->id,
                'accounting_account_id' => Helper::getProcessAccountingAccount('retentions.reteica'),
                'third_party_type' => Customer::class,
                'third_party_id' => $sale->customer_id,
                // 'cost_center_id',
                'nature' => AccountingNoteDetail::NATURE_DEBIT,
                'amount' => $payment->reteica,
                'status' => AccountingNoteDetail::STATUS_ACTIVE,
            ]);

            $total += $payment->reteica;
        }

        $accountingNote->amount = $total;
        $accountingNote->save();

        return $accountingNote;
    }

    public static function addDetail(&$details, $data)
    {
        $key = $data['accounting_account_id'].'_'.$data['third_party_type'].'_'.$data['third_party_id'].'_'.($data['cost_center_id'] ?? null);

        if (!array_key_exists('debit', $data)) {
            $data['debit'] = 'debit' == $data['nature'] ? $data['amount'] : 0;
            $data['credit'] = 'credit' == $data['nature'] ? $data['amount'] : 0;
        }

        if (!array_key_exists($key, $details)) {
            $details[$key] = [
                'accounting_account_id' => $data['accounting_account_id'],
                'third_party_type' => $data['third_party_type'],
                'third_party_id' => $data['third_party_id'],
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'debit' => 0,
                'credit' => 0,
            ];
        }

        $details[$key]['debit'] += $data['debit'];
        $details[$key]['credit'] += $data['credit'];

        // $details[] = [
        //     'accounting_account_id' => $data['accounting_account_id'],
        //     'third_party_type' => $data['third_party_type'],
        //     'third_party_id' => $data['third_party_id'],
        //     'cost_center_id' => $data['cost_center_id'] ?? null,
        //     'debit' => $data['debit'] ?? 0,
        //     'credit' => $data['credit'] ?? 0,
        // ];
    }

    public static function cancelAccountingAccount($originId, $originType)
    {
        if ($accountingNote = AccountingNote::where('origin_id', $originId)->where('origin_type', $originType)->first()) {
            $accountingNote->status = AccountingNote::STATUS_CANCELLED;
            $accountingNote->save();

            foreach ($accountingNote->AccountingNoteDetails as $accountingNoteDetail) {
                $accountingNoteDetail->status = AccountingNoteDetail::STATUS_CANCELLED;
                $accountingNoteDetail->save();
            }
        }
    }
}
