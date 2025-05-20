<?php

namespace App\Helpers;

use App\Models\Business;
use App\Models\Buy;
use App\Models\CreditNote;
use App\Models\DianEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Resolution;
use App\Models\Sale;
use App\Models\SupportDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class ElectronicBillClient
{
    public static $url = 'https://ebilling.linksoft.cloud/';
    public static $pathInvoices = 'api/invoices';
    public static $pathSupportDocuments = 'api/support-documents';
    public static $pathCreditNotes = 'api/credit-notes';
    public static $pathDebitNotes = 'api/debit-notes';
    public static $pathLogin = 'api/login';

    public static function percentBilled($date)
    {
        $sales = Order::where('date', '=', $date)->whereStatus(Sale::STATUS_ACTIVE)->get();

        if (0 === $sales->count()) {
            return 0;
        }

        return 100 - (($sales->filter(function ($sale) {
            return $sale->sale_id > 0;
        })->count() / $sales->count()) * 100);
    }

    public static function login(Business $business)
    {
        try {
            $response = Http::acceptJson()->asJson()->post(self::$url.self::$pathLogin, [
                'email' => $business->company_email,
                'password' => $business->company_password,
                'device_name' => 'web',
            ]);

            return $response->json()['token'];
        } catch (\Exception $e) {
            $response = Http::acceptJson()->asJson()->post(self::$url.self::$pathLogin, [
                'email' => $business->company_email,
                'password' => $business->company_password,
                'device_name' => 'web',
            ]);

            return $response->json()['token'];
        }
    }

    public static function sendInvoice(Sale $sale)
    {
        $products = [];

        foreach ($sale->saleProducts as $product) {
            if ($product->product->no_bill) {
                continue;
            }

            if ($product->total <= 0) {
                continue;
            }

            $discounts = [];
            $taxes = [];

            if ($tax = $product->tax) {
                $amount = round($product->subtotal * ($product->tax_percent / 100), 2);

                if ($amount > 0) {
                    $taxes[] = [
                        'code' => $tax->code,
                        'amount' => $amount,
                        'percent' => $product->tax_percent,
                    ];
                }
            }

            $amount = $product->subtotal;

            // if ($product->discount > 0) {
            //     $discounts[] = [
            //         'code' => '09',
            //         'reason' => 'Descuento General',
            //         'amount' => $product->discount,
            //     ];
            //     $amount -= $product->discount;
            // }

            $amount = round($amount / $product->quantity, 2);

            $products[] = [
                'code' => $product->product->barcode ?? $product->product_id,
                'description' => $product->product_name ?? $product->product->name,
                'type_identification' => '999',
                'reference_price' => '01',
                'unit' => $product->product?->unitMeasure?->code ?? 'ZZ',
                'quantity' => $product->quantity,
                'amount' => $amount,
                'discounts' => $discounts,
                'taxes' => $taxes,
            ];
        }

        $paymentMethod = 10;

        switch ($sale->payment_method) {
            case Payment::METHOD_CREDIT:
                $paymentMethod = 1;

                break;
            case Payment::METHOD_BANK_TRANSFER:
                $paymentMethod = 31;

                break;
            case Payment::METHOD_CREDIT_CARD:
                $paymentMethod = 48;

                break;
            case Payment::METHOD_DEBIT_CARD:
                $paymentMethod = 49;

                break;
        }

        $data = [
            'company_id' => $sale->business->company_id,
            'prefix' => $sale->resolution->prefix,
            'consecutive' => $sale->consecutive,
            'date' => $sale->date->format('Y-m-d'),
            'payment_form' => $sale->pending_payment > 0 ? 2 : 1,
            'payment_method' => $paymentMethod,
            'due_days' => $sale->due_days ?? 0,
            'pdf' => url('sales/'.$sale->uuid.'/pdf'),
            'customer' => [
                'business_name' => $sale->customer->business_name,
                'identification_number' => $sale->customer->document_number,
                'dv' => self::calcularDV($sale->customer->document_number),
                'language_id' => 80,
                'tax_id' => 1,
                'type_environment_id' => 2,
                'type_operation_id' => 5,
                'type_document_identification_id' => 6,
                'country_id' => 46,
                'type_currency_id' => 35,
                'type_organization_id' => 1,
                'type_regime_id' => 1,
                'type_liability_id' => 201,
                'municipality_id' => 687,
                'merchant_registration' => '',
                'address' => $sale->customer->address,
                'phone' => $sale->customer->cellphone,
                'email' => filter_var($sale->customer->email, FILTER_VALIDATE_EMAIL) ? $sale->customer->email : 'lucia@linksoft.cloud',
            ],
            'products' => $products,
        ];

        // if (0 == $sale->id % 5 && (22222222 == $sale->customer->document_number || 2222222 == $sale->customer->document_number) && in_array($sale->business_id, [3, 4, 6])) {
        //     $data['customer'] = [
        //         'business_name' => 'Miguel Gutierrez Rincon',
        //         'identification_number' => 1121898356,
        //         'dv' => self::calcularDV(1121898356),
        //         'language_id' => 80,
        //         'tax_id' => 1,
        //         'type_environment_id' => 2,
        //         'type_operation_id' => 5,
        //         'type_document_identification_id' => 6,
        //         'country_id' => 46,
        //         'type_currency_id' => 35,
        //         'type_organization_id' => 1,
        //         'type_regime_id' => 1,
        //         'type_liability_id' => 201,
        //         'municipality_id' => 687,
        //         'merchant_registration' => '',
        //         'address' => 'Vda zuria',
        //         'phone' => '3053982747',
        //         'email' => 'miguel.gutierrez.rincon@gmail.com',
        //     ];
        // }

        $retentions = [];

        if ($sale->retefuente > 0) {
            $retentions[] = [
                'code' => '06',
                'amount' => $sale->retefuente,
                'percent' => $sale->retefuente_percentage,
            ];
        }

        if ($sale->reteica > 0) {
            $retentions[] = [
                'code' => '07',
                'amount' => $sale->reteica,
                'percent' => $sale->reteica_percentage,
            ];
        }

        if ($sale->reteiva > 0) {
            $retentions[] = [
                'code' => '05',
                'amount' => $sale->reteiva,
                'percent' => $sale->reteiva_percentage,
            ];
        }

        $data['retentions'] = $retentions;

        if ($sale->invoice_id > 0) {
            $data['invoice_id'] = $sale->invoice_id;
        }

        $response = Http::acceptJson()->asJson()->withToken(self::login($sale->business))->post(self::$url.self::$pathInvoices, $data);

        if (Request::has('debug')) {
            dd($response->json());
        }

        if (200 === $response->status() || 201 === $response->status()) {
            $sale->invoice_status = 'success' == $response->json()['data']['status'] ? Sale::INVOICE_STATUS_APPROVED : ('pending' == $response->json()['data']['status'] ? Sale::INVOICE_STATUS_PENDING : Sale::INVOICE_STATUS_REJECTED);
            $sale->invoice_id = $response->json()['data']['id'];

            if (array_key_exists('cufe', $response->json()['data'])) {
                $sale->cufe = $response->json()['data']['cufe'];
            }
            $sale->invoice_consecutive = $response->json()['data']['consecutive'];
            $sale->save();
        } else {
            $sale->invoice_status = Sale::INVOICE_STATUS_REJECTED;

            if (array_key_exists('id', $response->json()['data'])) {
                $sale->invoice_id = $response->json()['data']['id'];
                $sale->invoice_consecutive = $response->json()['data']['consecutive'];
            }
            $sale->save();
        }
    }

    public static function sendCreditNote(CreditNote $creditNote)
    {
        $products = [];

        foreach ($creditNote->creditNoteProducts as $product) {
            $discounts = [];
            $taxes = [];

            if ($product->discount > 0) {
                $discounts[] = [
                    'code' => '09',
                    'reason' => 'Descuento General',
                    'amount' => $product->discount,
                ];
            }

            if ($tax = $product->tax) {
                $amount = round($product->subtotal * ($product->tax_percent / 100), 2);

                if ($amount > 0) {
                    $taxes[] = [
                        'code' => $tax->code,
                        'amount' => $amount,
                        'percent' => $product->tax_percent,
                    ];
                }
            }

            if (is_null($product->product)) {
                continue;
            }

            $products[] = [
                'code' => $product->product->barcode ?? $product->product_id,
                'description' => $product->product->name,
                'type_identification' => '999',
                'reference_price' => '01',
                'unit' => $product->product?->unitMeasure?->code ?? 'ZZ',
                'quantity' => $product->quantity,
                'amount' => round($product->subtotal / $product->quantity, 2),
                'discounts' => $discounts,
                'taxes' => $taxes,
            ];
        }

        $paymentMethod = 10;

        switch ($creditNote->payment_method) {
            case Payment::METHOD_CREDIT:
                $paymentMethod = 1;

                break;
            case Payment::METHOD_BANK_TRANSFER:
                $paymentMethod = 31;

                break;
            case Payment::METHOD_CREDIT_CARD:
                $paymentMethod = 48;

                break;
            case Payment::METHOD_DEBIT_CARD:
                $paymentMethod = 49;

                break;
        }

        $data = [
            'company_id' => $creditNote->business->company_id,
            'prefix' => 'NC',
            'consecutive' => $creditNote->consecutive,
            'date' => $creditNote->date->format('Y-m-d'),
            'payment_form' => 'credit' == $creditNote->payment_method ? 2 : 1,
            'payment_method' => $paymentMethod,
            'due_days' => 0,
            'invoice_id' => $creditNote->sale()->withoutGlobalScopes()->first()?->invoice_id,
            'products' => $products,
            'notes' => $creditNote->notes,
            'customer' => [
                'business_name' => $creditNote->customer->business_name,
                'identification_number' => $creditNote->customer->document_number,
                'dv' => self::calcularDV($creditNote->customer->document_number),
                'language_id' => 80,
                'tax_id' => 1,
                'type_environment_id' => 2,
                'type_operation_id' => 5,
                'type_document_identification_id' => 6,
                'country_id' => 46,
                'type_currency_id' => 35,
                'type_organization_id' => 1,
                'type_regime_id' => 1,
                'type_liability_id' => 202,
                'municipality_id' => 687,
                'merchant_registration' => '',
                'address' => $creditNote->customer->address,
                'phone' => $creditNote->customer->cellphone,
                'email' => filter_var($creditNote->customer->email, FILTER_VALIDATE_EMAIL) ? $creditNote->customer->email : 'lucia@linksoft.cloud',
            ],
        ];

        if ($creditNote->dian_id > 0) {
            $data['credit_note_id'] = $creditNote->dian_id;
        }

        $response = Http::acceptJson()->asJson()->withToken(self::login($creditNote->business))->post(self::$url.self::$pathCreditNotes, $data);

        if (Request::has('debug')) {
            dd($response->json());
        }

        if (200 === $response->status() || 201 === $response->status()) {
            $creditNote->dian_status = 'success' == $response->json()['data']['status'] ? Sale::INVOICE_STATUS_APPROVED : ('pending' == $response->json()['data']['status'] ? Sale::INVOICE_STATUS_PENDING : Sale::INVOICE_STATUS_REJECTED);
            $creditNote->dian_id = $response->json()['data']['id'];

            if (array_key_exists('cufe', $response->json()['data'])) {
                $creditNote->cude = $response->json()['data']['cufe'];
            }

            if (array_key_exists('cude', $response->json()['data'])) {
                $creditNote->cude = $response->json()['data']['cufe'];
            }
            $creditNote->save();
        } else {
            $creditNote->dian_status = Sale::INVOICE_STATUS_REJECTED;

            if (array_key_exists('id', $response->json()['data'])) {
                $creditNote->dian_id = $response->json()['data']['id'];
            }
            $creditNote->save();
        }
    }

    public static function validateInvoice(Sale $sale)
    {
        $response = Http::acceptJson()->asJson()->withToken(self::login($sale->business))->post(self::$url.self::$pathInvoices.'/'.$sale->invoice_id.'/send', []);

        // if (session()->has('is_cron')) {
        //     dump($sale->id, $response->json());
        // }
        // dd($response->body());

        if (200 === $response->status() || 201 === $response->status()) {
            $sale->invoice_status = 'success' == $response->json()['data']['status'] ? Sale::INVOICE_STATUS_APPROVED : ('pending' == $response->json()['data']['status'] ? Sale::INVOICE_STATUS_PENDING : Sale::INVOICE_STATUS_REJECTED);

            if (array_key_exists('cufe', $response->json()['data'])) {
                $sale->cufe = $response->json()['data']['cufe'];
            }
            $sale->invoice_consecutive = $response->json()['data']['consecutive'];
            $sale->save();
        } else {
            if (is_null($sale->cufe)) {
                $sale->invoice_status = Sale::INVOICE_STATUS_REJECTED;
            }
            $sale->save();
        }
    }

    public static function validateCreditNote(CreditNote $creditNote)
    {
        $response = Http::acceptJson()->asJson()->withToken(self::login($creditNote->business))->post(self::$url.self::$pathCreditNotes.'/'.$creditNote->dian_id.'/send', []);

        // if (session()->has('is_cron')) {
        //     dump($sale->id, $response->json());
        // }
        // dd($response->body());

        if (Request::has('debug')) {
            dd($response->json());
        }

        if (200 === $response->status() || 201 === $response->status()) {
            $creditNote->dian_status = 'success' == $response->json()['data']['status'] ? Sale::INVOICE_STATUS_APPROVED : ('pending' == $response->json()['data']['status'] ? Sale::INVOICE_STATUS_PENDING : Sale::INVOICE_STATUS_REJECTED);

            if (array_key_exists('cufe', $response->json()['data'])) {
                $creditNote->cude = $response->json()['data']['cufe'];
            }
            // $creditNote->invoice_consecutive = $response->json()['data']['consecutive'];
            $creditNote->save();
        } else {
            if (is_null($creditNote->cude)) {
                $creditNote->dian_status = Sale::INVOICE_STATUS_REJECTED;
            }
            $creditNote->save();
        }

        if (Sale::INVOICE_STATUS_APPROVED != $creditNote->dian_status) {
            Log::info('CreditNote '.$creditNote->id.' rejected. Response: '.json_encode($response->json()));
        }
    }

    public static function sendEvent(Buy $buy, $type, $personData)
    {
        $business = $buy->business;

        $data = [
            'company_id' => $business->company_id,
            'invoice_number' => $buy->invoice_number,
            'invoice_date' => $buy->date->format('Y-m-d'),
            'cufe' => $buy->cufe,
            'type' => $type,
            'supplier' => [
                'business_name' => $buy->supplier->business_name,
                'identification_number' => $buy->supplier->document_number,
                'dv' => self::calcularDV($buy->supplier->document_number),
                'language_id' => 80,
                'tax_id' => 1,
                'type_environment_id' => 2,
                'type_operation_id' => 5,
                'type_document_identification_id' => 6,
                'country_id' => 46,
                'type_currency_id' => 35,
                'type_organization_id' => 1,
                'type_regime_id' => 1,
                'type_liability_id' => 202,
                'municipality_id' => 687,
                'merchant_registration' => '',
                'address' => $buy->supplier->address,
                'phone' => $buy->supplier->cellphone,
                'email' => filter_var($buy->supplier->email, FILTER_VALIDATE_EMAIL) ? $buy->supplier->email : 'lucia@linksoft.cloud',
            ],
            'person' => [
                'identification_number' => $personData['identification_number'],
                'dv' => self::calcularDV($personData['identification_number']),
                'first_name' => $personData['first_name'],
                'last_name' => $personData['last_name'],
                'position' => $personData['position'],
                'department' => $personData['department'],
            ],
        ];

        $response = Http::acceptJson()
            ->asJson()
            ->withToken(self::login($business))
            ->post(self::$url.'api/send_event', $data)
        ;

        $event = DianEvent::updateOrCreate([
            'business_id' => $business->id,
            'eventable_id' => $buy->id,
            'eventable_type' => Buy::class,
            'event_type' => $type,
            'status' => 'success' == $response['data']['status'] ? DianEvent::STATUS_COMPLETED : DianEvent::STATUS_CANCELLED,
        ]);

        return $response->json();
    }

    public static function getRanges(?Business $business = null)
    {
        if (is_null($business)) {
            $business = auth()->user()?->business;
        }
        $response = Http::acceptJson()->asJson()->withToken(self::login($business))->get(self::$url.'api/ranges/'.$business->company_id);

        return $response->json();
    }

    public static function importRanges(?Business $business = null)
    {
        if (is_null($business)) {
            $business = auth()->user()?->business;
        }

        foreach (self::getRanges($business) as $range) {
            Resolution::updateOrCreate([
                'business_id' => $business->id,
                'number' => $range['ResolutionNumber'],
                'prefix' => $range['Prefix'],
            ], [
                'type' => Resolution::TYPE_ELECTRONIC_INVOCIE,
                'from_date' => $range['ValidDateFrom'],
                'to_date' => $range['ValidDateTo'],
                'from_number' => $range['FromNumber'],
                'to_number' => $range['ToNumber'],
                'technical_key' => $range['TechnicalKey'],
            ]);
        }

        return true;
    }

    public static function urlInvoice(Sale $sale)
    {
        return self::$url.self::$pathInvoices.'/'.$sale->invoice_id.'/print';
    }

    public static function calcularDV($nit)
    {
        if (!is_numeric($nit)) {
            $nit = str_replace(['.', '-', ' '], '', $nit);
        }

        $nit = trim($nit);

        try {
            $arr = [
                1 => 3,
                4 => 17,
                7 => 29,
                10 => 43,
                13 => 59,
                2 => 7,
                5 => 19,
                8 => 37,
                11 => 47,
                14 => 67,
                3 => 13,
                6 => 23,
                9 => 41,
                12 => 53,
                15 => 71,
            ];
            $x = 0;
            $y = 0;
            $z = strlen($nit);
            $dv = '';

            for ($i = 0; $i < $z; ++$i) {
                $y = substr($nit, $i, 1);
                $x += ($y * $arr[$z - $i]);
            }
            $y = $x % 11;

            if ($y > 1) {
                return 11 - $y;
            }

            return $y;
        } catch (\ErrorException $e) {
            return false;
        }
    }

    public static function sendSupportDocument(SupportDocument $supportDocument)
    {
        if ($supportDocument->resolution_id ?? 0 <= 0) {
            $supportDocument->resolution_id = Helper::getSubsidiaryConfig('support_documents.resolution');
            $supportDocument->save();
        }

        $products = [];
        foreach ($supportDocument->supportDocumentProducts as $product) {
            if ($product->total <= 0) {
                continue;
            }

            $discounts = [];
            $taxes = [];

            if ($tax = $product->tax) {
                $amount = round($product->subtotal * ($product->tax_percent / 100), 2);

                if ($amount > 0) {
                    $taxes[] = [
                        'code' => $tax->code,
                        'amount' => $amount,
                        'percent' => $product->tax_percent,
                    ];
                }
            }

            $amount = $product->subtotal;

            // if ($product->discount > 0) {
            //     $discounts[] = [
            //         'code' => '09',
            //         'reason' => 'Descuento General',
            //         'amount' => $product->discount,
            //     ];
            //     $amount -= $product->discount;
            // }

            $amount = round($amount / $product->quantity, 2);

            $products[] = [
                'code' => $product->product?->barcode ?? $product->product_id,
                'description' => $product->product_name ?? $product->product?->name,
                'type_identification' => '999',
                'reference_price' => '01',
                'unit' => $product->product?->unitMeasure?->code ?? 'ZZ',
                'quantity' => $product->quantity,
                'amount' => $amount,
                'discounts' => $discounts,
                'taxes' => $taxes,
            ];
        }

        $paymentMethod = 10;

        switch ($supportDocument->payment_method) {
            case Payment::METHOD_CREDIT:
                $paymentMethod = 1;

                break;
            case Payment::METHOD_BANK_TRANSFER:
                $paymentMethod = 31;

                break;
            case Payment::METHOD_CREDIT_CARD:
                $paymentMethod = 48;

                break;
            case Payment::METHOD_DEBIT_CARD:
                $paymentMethod = 49;

                break;
        }

        $data = [
            'company_id' => $supportDocument->business->company_id,
            'prefix' => $supportDocument->resolution->prefix,
            'consecutive' => $supportDocument->consecutive,
            'date' => $supportDocument->date->format('Y-m-d'),
            'payment_form' => $supportDocument->pending_payment > 0 ? 2 : 1,
            'payment_method' => $paymentMethod,
            'due_days' => $supportDocument->due_days ?? 0,
            'customer' => [
                'business_name' => $supportDocument->supplier->business_name,
                'identification_number' => $supportDocument->supplier->document_number,
                'dv' => self::calcularDV($supportDocument->supplier->document_number),
                'language_id' => 80,
                'tax_id' => 1,
                'type_environment_id' => 2,
                'type_operation_id' => 5,
                'type_document_identification_id' => 6,
                'country_id' => 46,
                'type_currency_id' => 35,
                'type_organization_id' => 1,
                'type_regime_id' => 1,
                'type_liability_id' => 201,
                'municipality_id' => 687,
                'merchant_registration' => '',
                'postal_code' => 500001,
                'address' => $supportDocument->supplier->address,
                'phone' => $supportDocument->supplier->cellphone,
                'email' => filter_var($supportDocument->supplier->email, FILTER_VALIDATE_EMAIL) ? $supportDocument->supplier->email : 'lucia@linksoft.cloud',
            ],
            'products' => $products,
        ];

        // if (0 == $sale->id % 5 && (22222222 == $sale->customer->document_number || 2222222 == $sale->customer->document_number) && in_array($sale->business_id, [3, 4, 6])) {
        //     $data['customer'] = [
        //         'business_name' => 'Miguel Gutierrez Rincon',
        //         'identification_number' => 1121898356,
        //         'dv' => self::calcularDV(1121898356),
        //         'language_id' => 80,
        //         'tax_id' => 1,
        //         'type_environment_id' => 2,
        //         'type_operation_id' => 5,
        //         'type_document_identification_id' => 6,
        //         'country_id' => 46,
        //         'type_currency_id' => 35,
        //         'type_organization_id' => 1,
        //         'type_regime_id' => 1,
        //         'type_liability_id' => 201,
        //         'municipality_id' => 687,
        //         'merchant_registration' => '',
        //         'address' => 'Vda zuria',
        //         'phone' => '3053982747',
        //         'email' => 'miguel.gutierrez.rincon@gmail.com',
        //     ];
        // }

        $retentions = [];

        if ($supportDocument->retefuente > 0) {
            $retentions[] = [
                'code' => '06',
                'amount' => $supportDocument->retefuente,
                'percent' => $supportDocument->retefuente_percentage,
            ];
        }

        if ($supportDocument->reteica > 0) {
            $retentions[] = [
                'code' => '07',
                'amount' => $supportDocument->reteica,
                'percent' => $supportDocument->reteica_percentage,
            ];
        }

        if ($supportDocument->reteiva > 0) {
            $retentions[] = [
                'code' => '05',
                'amount' => $supportDocument->reteiva,
                'percent' => $supportDocument->reteiva_percentage,
            ];
        }

        $data['retentions'] = $retentions;

        if ($supportDocument->billing_id > 0) {
            $data['support_document_id'] = $supportDocument->billing_id;
        }

        $response = Http::acceptJson()->asJson()->withToken(self::login($supportDocument->business))->post(self::$url.self::$pathSupportDocuments, $data);

        if (200 === $response->status() || 201 === $response->status()) {
            $supportDocument->dian_status = 'success' == $response->json()['data']['status'] ? Sale::INVOICE_STATUS_APPROVED : ('pending' == $response->json()['data']['status'] ? Sale::INVOICE_STATUS_PENDING : Sale::INVOICE_STATUS_REJECTED);
            $supportDocument->billing_id = $response->json()['data']['id'];

            if (array_key_exists('cufe', $response->json()['data'])) {
                $supportDocument->cufe = $response->json()['data']['cufe'];
            }
            $supportDocument->dian_consecutive = $response->json()['data']['consecutive'];
            $supportDocument->save();
        } else {
            dd($response->json());
            $supportDocument->dian_status = Sale::INVOICE_STATUS_REJECTED;

            if (array_key_exists('id', $response->json()['data'])) {
                $supportDocument->billing_id = $response->json()['data']['id'];
                $supportDocument->dian_consecutive = $response->json()['data']['consecutive'];
            }
            $supportDocument->save();
        }
    }

    public static function validateSupportDocument(SupportDocument $supportDocument)
    {
        $response = Http::acceptJson()->asJson()->withToken(self::login($supportDocument->business))->post(self::$url.self::$pathSupportDocuments.'/'.$supportDocument->billing_id.'/send', []);

        // if (session()->has('is_cron')) {
        //     dump($sale->id, $response->json());
        // }
        // dd($response->body());

        if (200 === $response->status() || 201 === $response->status()) {
            $supportDocument->dian_status = 'success' == $response->json()['data']['status'] ? Sale::INVOICE_STATUS_APPROVED : ('pending' == $response->json()['data']['status'] ? Sale::INVOICE_STATUS_PENDING : Sale::INVOICE_STATUS_REJECTED);

            if (array_key_exists('cufe', $response->json()['data'])) {
                $supportDocument->cufe = $response->json()['data']['cufe'];
            }
            $supportDocument->dian_consecutive = $response->json()['data']['consecutive'];
            $supportDocument->save();
        } else {
            if (is_null($supportDocument->cufe)) {
                $supportDocument->dian_status = Sale::INVOICE_STATUS_REJECTED;
            }
            $supportDocument->save();
        }
    }
}
