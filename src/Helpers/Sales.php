<?php

namespace App\Helpers;

use App\Models\BillAuthorization;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\EstimateProduct;
use App\Models\IncomeReceipt;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Rental;
use App\Models\Resolution;
use App\Models\Sale;
use App\Models\SaleProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Sales
{
    public static function saveOrUpdate($data, $type, $id = null)
    {
        // if ('estimate' == $type) {
        //     $customer = Customer::updateOrCreate([
        //         'document_number' => $data['document_number'],
        //     ], [
        //         'document_type' => $data['document_type'] ?? Customer::DOCUMENT_TYPE_NIT,
        //         'business_name' => $data['business_name'],
        //         'cellphone' => $data['cellphone'],
        //         'email' => $data['email'],
        //     ]);

        //     $data['customer_id'] = $customer->id;
        // }

        $products = [];

        foreach ($data['product'] as $key => $product) {
            if (!array_key_exists('discount', $product)) {
                $product['discount'] = 0;
            }

            $productModel = Product::find($product['id']);
            $taxes = 0;
            $subtotal = round($product['quantity'] * str_replace([',', '$'], '', $product['original_price']), 2);
            $discount = round($subtotal * $product['discount'] / 100, 2);
            $subtotal -= $discount;
            $total = 0;
            $tax_id = null;
            $tax_percent = 0;

            if (count($productModel->productTaxes) > 0 && !auth()->user()?->subsidiary?->iva_excluded && true != $data['iva_excluded']) {
                foreach ($productModel->productTaxes as $productTax) {
                    $tax_id = $productTax->tax_id;
                    $tax_percent = $productTax->percent;
                    $subtotal /= round(1 + ($productTax->percent / 100), 2);
                    $subtotal = round($subtotal, 2);
                    $total = $subtotal;
                    $taxValue = round($subtotal * $productTax->percent / 100, 2);
                    $taxes += $taxValue;
                    $total += $taxValue;
                }
            } else {
                $total = $subtotal;
            }

            $products[] = [
                'product_id' => $product['id'],
                'subsidiary_id' => $product['subsidiary_id'] ?? auth()->user()->subsidiary_id,
                'price_list_id' => array_key_exists('price_list_id', $product) ? ($product['price_list_id'] ?? 0) : 0,
                'original_price' => str_replace([',', '$'], '', $product['original_price']),
                'price' => str_replace([',', '$'], '', $product['price']),
                'quantity' => $product['quantity'],
                'product_name' => $product['name'] ?? $productModel->name,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'discount_percentage' => $product['discount'] ?? 0,
                'taxes' => $taxes,
                'total' => $total,
                'tax_id' => $tax_id,
                'tax_percent' => $tax_percent,
            ];
        }

        switch ($type) {
            case 'estimate':
                return self::saveEstimate($data, $products, $id);

                break;
            case 'sale':
                return self::saveSale($data, $products, $id);

                break;
            case 'order':
                return self::saveOrder($data, $products, $id);

                break;
        }
    }

    public static function saveEstimate($data, $products, $id)
    {
        $estimateProducts = $products;

        $subtotal = Helper::arraySumByKey($estimateProducts, 'subtotal');
        $discount = Helper::arraySumByKey($estimateProducts, 'discount');
        $taxes = Helper::arraySumByKey($estimateProducts, 'taxes');
        $total = Helper::arraySumByKey($estimateProducts, 'total');

        $retefuentePercentage = $data['retefuente'] ?? 0;
        $reteicaPercentage = $data['reteica'] ?? 0;
        $reteivaPercentage = $data['reteiva'] ?? 0;

        $retefuente = 0;
        $reteica = 0;
        $reteiva = 0;

        $customer = Customer::find($data['customer_id']);

        if ($subtotal >= 1345000 or $customer->always_fte_retention) {
            $retefuente = round($subtotal * $retefuentePercentage / 100, 2);
            $reteica = round($subtotal * $reteicaPercentage / 1000, 2);
            $reteiva = round($taxes * $reteivaPercentage / 100, 2);
        } else {
            $retefuentePercentage = 0;
            $reteicaPercentage = 0;
            $reteivaPercentage = 0;
        }

        $totalBeforeRetentions = $total;
        $total -= $retefuente + $reteica + $reteiva;

        $data = [
            'customer_id' => $data['customer_id'],
            'date' => $data['date'],
            'notes' => $data['notes'],
            'subtotal' => $subtotal,
            'discount' => $discount,
            'taxes' => $taxes,
            'total' => $total,
            'retefuente' => $retefuente,
            'retefuente_percentage' => $retefuentePercentage,
            'reteica' => $reteica,
            'reteica_percentage' => $reteicaPercentage,
            'reteiva' => $reteiva,
            'reteiva_percentage' => $reteivaPercentage,
            'total_before_retentions' => $totalBeforeRetentions,
            'status' => array_key_exists('status', $data) ? $data['status'] : Estimate::STATUS_ACTIVE,
            'employee_id' => $data['employee_id'],
            'order_number' => array_key_exists('order_number', $data) ? $data['order_number'] : null,
            'due_days' => array_key_exists('due_days', $data) ? $data['due_days'] : null,
            'delivery_type' => array_key_exists('delivery_type', $data) ? $data['delivery_type'] : null,
            'delivery_date' => array_key_exists('delivery_date', $data) ? $data['delivery_date'] : null,
            'delivery_time' => array_key_exists('delivery_time', $data) ? $data['delivery_time'] : null,
            'activity_id' => array_key_exists('activity_id', $data) ? $data['activity_id'] : null,
            'payment_type' => array_key_exists('payment_type', $data) ? $data['payment_type'] : null,
            'price_list_id' => array_key_exists('price_list_id', $data) ? $data['price_list_id'] : 0,
            'iva_excluded' => array_key_exists('iva_excluded', $data) ? ($data['iva_excluded'] ?? false) : false,
        ];

        if ($id > 0) {
            $estimate = Estimate::find($id);

            foreach ($estimate->estimateProducts as $estimateProduct) {
                $estimateProduct->delete();
            }
            unset($data['date']);
            $estimate->update($data);
        } else {
            $number = Estimate::max('consecutive') + 1;
            $data['consecutive'] = $number;
            $estimate = Estimate::create($data);
        }

        foreach ($products as $estimateProduct) {
            $estimateProduct['estimate_id'] = $estimate->id;
            EstimateProduct::create($estimateProduct);
        }

        return $estimate;
    }

    public static function saveSale($data, $products, $id)
    {
        $saleProducts = $products;

        $paymentStatus = Payment::STATUS_COMPLETED;
        $pendingPayment = 0;

        $data['payment_method'] = Payment::METHOD_CASH;

        foreach ($data['payments'] as $paymentMethod => $amount) {
            $data['payment_method'] = $paymentMethod;

            if (Payment::METHOD_CREDIT == $paymentMethod or Payment::METHOD_ADDI == $paymentMethod) {
                $paymentStatus = Payment::STATUS_PENDING;
                $pendingPayment += $amount;
            }
        }

        $rentalId = array_key_exists('rental_id', $data) ? $data['rental_id'] : 0;

        if ($rentalId > 0) {
            $rental = Rental::find($rentalId);
            $rental->update([
                'status' => Rental::STATUS_BILLED,
            ]);
        }

        $subtotal = Helper::arraySumByKey($saleProducts, 'subtotal');
        $discount = Helper::arraySumByKey($saleProducts, 'discount');
        $taxes = Helper::arraySumByKey($saleProducts, 'taxes');
        $total = Helper::arraySumByKey($saleProducts, 'total');

        $retefuentePercentage = $data['retefuente'] ?? 0;
        $reteicaPercentage = $data['reteica'] ?? 0;
        $reteivaPercentage = $data['reteiva'] ?? 0;

        $retefuente = 0;
        $reteica = 0;
        $reteiva = 0;

        $customer = Customer::find($data['customer_id']);

        if ($subtotal >= 1345000 or $customer->always_fte_retention) {
            $retefuente = round($subtotal * $retefuentePercentage / 100, 2);
            $reteica = round($subtotal * $reteicaPercentage / 1000, 2);
            $reteiva = round($taxes * $reteivaPercentage / 100, 2);
        }

        $totalBeforeRetentions = $total;
        $total -= $retefuente + $reteica + $reteiva;

        $advance = $total - $pendingPayment;

        if ($advance < 1) {
            $advance = 0;
        }

        $saveData = [
            'customer_id' => $data['customer_id'],
            'date' => $data['date'] ?? now(),
            'notes' => $data['notes'] ?? '',
            'subtotal' => $subtotal,
            'discount' => $discount,
            'taxes' => $taxes,
            'total' => $total,
            'retefuente' => $retefuente,
            'retefuente_percentage' => $retefuentePercentage,
            'reteica' => $reteica,
            'reteica_percentage' => $reteicaPercentage,
            'reteiva' => $reteiva,
            'reteiva_percentage' => $reteivaPercentage,
            'total_before_retentions' => $totalBeforeRetentions,
            'status' => Sale::STATUS_ACTIVE,
            'invoice_status' => Sale::INVOICE_STATUS_PENDING,
            'payment_status' => $paymentStatus,
            'pending_payment' => $pendingPayment,
            'advance' => $advance,
            'payment_method' => $data['payment_method'],
            'rental_id' => $rentalId,
            'employee_id' => $data['employee_id'],
            'conditions' => array_key_exists('conditions', $data) ? $data['conditions'] : null,
            'voucher' => array_key_exists('voucher', $data) ? $data['voucher'] : null,
            'bank_account_id' => array_key_exists('bank_account_id', $data) ? $data['bank_account_id'] : null,
            'order_id' => array_key_exists('order_id', $data) && $data['order_id'] > 0 ? $data['order_id'] : null,
            'delivery_type' => array_key_exists('delivery_type', $data) ? $data['delivery_type'] : null,
            'delivery_date' => array_key_exists('delivery_date', $data) ? $data['delivery_date'] : null,
            'delivery_time' => array_key_exists('delivery_time', $data) ? $data['delivery_time'] : null,
            'payment_type' => array_key_exists('payment_type', $data) ? $data['payment_type'] : null,
            'estimate_id' => array_key_exists('estimate_id', $data) ? $data['estimate_id'] : null,
            'activity_id' => array_key_exists('activity_id', $data) ? $data['activity_id'] : null,
            'order_number' => array_key_exists('order_number', $data) ? $data['order_number'] : null,
            'income_receipts' => array_key_exists('income_receipts', $data) ? $data['income_receipts'] : null,
            'price_list_id' => array_key_exists('price_list_id', $data) ? $data['price_list_id'] : 0,
            'iva_excluded' => array_key_exists('iva_excluded', $data) ? ($data['iva_excluded'] ?? false) : false,
        ];

        if (array_key_exists('income_receipts', $data)) {
            foreach ($data['income_receipts'] as $incomeReceiptId => $amount) {
                if ($incomeReceipt = IncomeReceipt::withoutGlobalScopes()->whereId($incomeReceiptId)->first()) {
                    $incomeReceipt->update([
                        'pending_balance' => $incomeReceipt->pending_balance - $amount,
                    ]);
                }
            }
        }

        if ($id > 0) {
            $sale = Sale::find($id);

            foreach ($sale->saleProducts as $saleProduct) {
                $saleProduct->delete();
            }

            foreach ($sale->payments as $payment) {
                $payment->delete();
            }

            $sale->update($saveData);
        } else {
            $resolution = Resolution::find(Helper::getSubsidiaryConfig('billing.pos_resolution'));

            $number = Sale::whereResolutionId($resolution->id)->max('consecutive') + 1;
            $saveData['consecutive'] = $number > 1 ? $number : $resolution->from_number;
            $saveData['resolution_id'] = $resolution->id;
            $saveData['uuid'] = Str::uuid();
            $sale = Sale::create($saveData);
        }

        if (array_key_exists('estimate_id', $data)) {
            if ($estimate = Estimate::find($data['estimate_id'])) {
                $estimate->update([
                    'sale_id' => $sale->id,
                    'status' => Estimate::STATUS_COMPLETED,
                ]);
            }
            DB::update('update delivery_orders set sale_id = ? where estimate_id = ?', [$sale->id, $data['estimate_id']]);
        }

        foreach ($data['payments'] as $paymentMethod => $amount) {
            if (Payment::METHOD_BALANCE_IN_FAVOR == $paymentMethod) {
                Customer::find($data['customer_id'])->decrement('balance_in_favor', $amount);
            }

            if (Payment::METHOD_CREDIT == $paymentMethod or Payment::METHOD_ADDI == $paymentMethod) {
                BillAuthorization::whereCustomerId($data['customer_id'])->update([
                    'status' => BillAuthorization::STATUS_APPROVED,
                    'updated_by' => auth()->user()->id,
                ]);
            }
            Payment::create([
                'customer_id' => $data['customer_id'],
                'status' => Payment::STATUS_COMPLETED,
                'price' => $amount,
                'payment_method' => $paymentMethod,
                'notes' => '',
                'origin_type' => Sale::class,
                'origin_id' => $sale->id,
                'voucher' => array_key_exists('voucher', $data) ? $data['voucher'] : null,
                'bank_account_id' => array_key_exists('bank_account_id', $data) ? $data['bank_account_id'] : null,
                'created_at' => $sale->created_at,
            ]);
        }

        foreach ($products as $saleProduct) {
            $saleProduct['sale_id'] = $sale->id;
            SaleProduct::create($saleProduct);
        }

        return $sale;
    }

    public static function saveOrder($data, $products, $id)
    {
        $orderProducts = $products;

        $paymentStatus = Payment::STATUS_COMPLETED;
        $pendingPayment = 0;

        $data['payment_method'] = Payment::METHOD_CASH;

        if (array_key_exists('payments', $data)) {
            foreach ($data['payments'] as $paymentMethod => $amount) {
                $data['payment_method'] = $paymentMethod;

                if (Payment::METHOD_CREDIT == $paymentMethod or Payment::METHOD_ADDI == $paymentMethod) {
                    $paymentStatus = Payment::STATUS_PENDING;
                    $pendingPayment += $amount;
                }
            }
        }

        $rentalId = array_key_exists('rental_id', $data) ? $data['rental_id'] : 0;

        if ($rentalId > 0) {
            $rental = Rental::find($rentalId);
            $rental->update([
                'status' => Rental::STATUS_BILLED,
            ]);
        }

        $subtotal = Helper::arraySumByKey($orderProducts, 'subtotal');
        $discount = Helper::arraySumByKey($orderProducts, 'discount');
        $taxes = Helper::arraySumByKey($orderProducts, 'taxes');
        $total = Helper::arraySumByKey($orderProducts, 'total');

        $retefuentePercentage = $data['retefuente'] ?? 0;
        $reteicaPercentage = $data['reteica'] ?? 0;
        $reteivaPercentage = $data['reteiva'] ?? 0;

        $retefuente = 0;
        $reteica = 0;
        $reteiva = 0;

        $customer = Customer::find($data['customer_id']);

        if ($subtotal >= 1345000 or $customer->always_fte_retention) {
            $retefuente = round($subtotal * $retefuentePercentage / 100, 2);
            $reteica = round($subtotal * $reteicaPercentage / 1000, 2);
            $reteiva = round($taxes * $reteivaPercentage / 100, 2);
        }

        $totalBeforeRetentions = $total;
        $total -= $retefuente + $reteica + $reteiva;

        $advance = $total - $pendingPayment;

        if ($advance < 1) {
            $advance = 0;
        }

        $saveData = [
            'customer_id' => $data['customer_id'],
            'date' => $data['date'] ?? now(),
            'notes' => $data['notes'] ?? '',
            'subtotal' => $subtotal,
            'discount' => $discount,
            'taxes' => $taxes,
            'total' => $total,
            'retefuente' => $retefuente,
            'retefuente_percentage' => $retefuentePercentage,
            'reteica' => $reteica,
            'reteica_percentage' => $reteicaPercentage,
            'reteiva' => $reteiva,
            'reteiva_percentage' => $reteivaPercentage,
            'total_before_retentions' => $totalBeforeRetentions,
            'status' => Order::STATUS_ACTIVE,
            'payment_status' => $paymentStatus,
            'pending_payment' => $pendingPayment,
            'advance' => $advance,
            'payment_method' => $data['payment_method'],
            'rental_id' => $rentalId,
            'employee_id' => $data['employee_id'],
            'voucher' => array_key_exists('voucher', $data) ? $data['voucher'] : null,
            'bank_account_id' => array_key_exists('bank_account_id', $data) ? $data['bank_account_id'] : null,
            'delivery_type' => array_key_exists('delivery_type', $data) ? $data['delivery_type'] : null,
            'delivery_date' => array_key_exists('delivery_date', $data) ? $data['delivery_date'] : null,
            'delivery_time' => array_key_exists('delivery_time', $data) ? $data['delivery_time'] : null,
            'payment_type' => array_key_exists('payment_type', $data) ? $data['payment_type'] : null,
            'estimate_id' => array_key_exists('estimate_id', $data) ? $data['estimate_id'] : null,
            'activity_id' => array_key_exists('activity_id', $data) ? $data['activity_id'] : null,
            'order_number' => array_key_exists('order_number', $data) ? $data['order_number'] : null,
            'price_list_id' => array_key_exists('price_list_id', $data) ? $data['price_list_id'] : 0,
            'iva_excluded' => array_key_exists('iva_excluded', $data) ? ($data['iva_excluded'] ?? false) : false,
            'payment_confirmed' => array_key_exists('payment_confirmed', $data) ? $data['payment_confirmed'] : false,
            'payment_confirmed_by' => array_key_exists('payment_confirmed_by', $data) ? $data['payment_confirmed_by'] : null,
            'payment_confirmed_at' => array_key_exists('payment_confirmed_at', $data) ? $data['payment_confirmed_at'] : null,
        ];

        if (array_key_exists('income_receipts', $data)) {
            foreach ($data['income_receipts'] as $incomeReceiptId => $amount) {
                if ($incomeReceipt = IncomeReceipt::withoutGlobalScopes()->whereId($incomeReceiptId)->first()) {
                    $incomeReceipt->update([
                        'pending_balance' => $incomeReceipt->pending_balance - $amount,
                    ]);
                }
            }
        }

        if ($id > 0) {
            $order = Order::find($id);

            foreach ($order->orderProducts as $orderProduct) {
                $orderProduct->delete();
            }

            foreach ($order->payments as $payment) {
                $payment->delete();
            }
            unset($data['date']);
            $order->update($saveData);
        } else {
            $number = Order::max('consecutive') + 1;
            $saveData['consecutive'] = $number;
            $order = Order::create($saveData);
        }

        if (array_key_exists('estimate_id', $data)) {
            $estimate = Estimate::find($data['estimate_id']);
            $estimate->update([
                'order_id' => $order->id,
                'status' => Estimate::STATUS_COMPLETED,
            ]);
        }

        if (array_key_exists('payments', $data)) {
            foreach ($data['payments'] as $paymentMethod => $amount) {
                if (Payment::METHOD_BALANCE_IN_FAVOR == $paymentMethod) {
                    Customer::find($data['customer_id'])->decrement('balance_in_favor', $amount);
                }

                if (Payment::METHOD_CREDIT == $paymentMethod or Payment::METHOD_ADDI == $paymentMethod) {
                    BillAuthorization::whereCustomerId($data['customer_id'])->update([
                        'status' => BillAuthorization::STATUS_APPROVED,
                        'updated_by' => auth()->user()->id,
                    ]);
                }
                Payment::create([
                    'customer_id' => $data['customer_id'],
                    'status' => Payment::STATUS_COMPLETED,
                    'price' => $amount > 0 ? $amount : round($order->total),
                    'payment_method' => $paymentMethod,
                    'notes' => '',
                    'origin_type' => Order::class,
                    'origin_id' => $order->id,
                    'voucher' => array_key_exists('voucher', $data) ? $data['voucher'] : null,
                    'bank_account_id' => array_key_exists('bank_account_id', $data) ? $data['bank_account_id'] : null,
                    // 'created_by' => $order->created_by,
                    // 'created_at' => $order->created_at,
                ]);
            }
        }

        foreach ($products as $orderProduct) {
            $orderProduct['order_id'] = $order->id;
            OrderProduct::create($orderProduct);
        }

        return $order;
    }
}
