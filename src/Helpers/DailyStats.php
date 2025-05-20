<?php

namespace App\Helpers;

use App\Fpdf\MyPdf;
use App\Models\Business;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Sale;

class DailyStats
{
    public static function savePdf($date = null, $businessId = 0)
    {
        if (is_null($date)) {
            $date = now()->subDay();
        }

        $dateEnd = $date->copy()->setTime(23, 59, 59);

        $sales = Order::withoutGlobalScopes()->whereBusinessId($businessId)->where('created_at', '>=', $date->copy()->setTime(0, 0, 0))->where(
            'created_at',
            '<=',
            $dateEnd->setTime(23, 59, 59)
        )->whereNotIn('status', [Order::STATUS_CANCELLED])->orderBy('id', 'asc')->get();

        $dianSales = Sale::withoutGlobalScopes()->whereBusinessId($businessId)->where('created_at', '>=', $date->copy()->setTime(0, 0, 0))->where(
            'created_at',
            '<=',
            $dateEnd->setTime(23, 59, 59)
        )->whereStatus(Sale::STATUS_ACTIVE)->orderBy('id', 'asc')->get();

        $salesByUser = $sales->groupBy('created_by');
        $salesBySubsidiary = $sales->sortBy('subsidiary.name')->groupBy('subsidiary.name');

        $payments = Payment::withoutGlobalScopes()->whereBusinessId($businessId)->whereOriginType(Order::class)->whereIn('origin_id', $sales->pluck('id')->toArray())->get();
        $vaucherPayments = $payments->filter(function ($payment) {
            return Payment::METHOD_BANK_TRANSFER == $payment->payment_method or Payment::METHOD_CREDIT_CARD == $payment->payment_method or Payment::METHOD_DEBIT_CARD == $payment->payment_method;
        });
        $cashPayments = $payments->filter(function ($payment) {
            return Payment::METHOD_CASH == $payment->payment_method;
        });

        $pdf = new MyPdf(size: 'letter');
        $fullWidth = 185.9;
        $pdf->headerReport = false;
        $pdf->SetMargins(15, 15);
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 8);
        $business = Business::find($businessId);
        $pdf->Image(public_path('assets/businesses/'.$business->id.'/logo.png'), 15, 12, 0, 15);
        $pdf->MultiCell(0, 4, $business->name, 0, 'R');
        $pdf->MultiCell(0, 4, 'Dirección: '.$business->address, 0, 'R');
        $pdf->MultiCell(0, 4, 'Teléfono: '.$business->phone, 0, 'R');
        $pdf->Ln(3);

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetFillColor(8, 145, 237);
        $pdf->SetTextColor(255);
        $pdf->MultiCell(0, 7, 'Informe Diario', 'LTR', 'C', 1);
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(Helper::percentValue(50, $fullWidth), 6, 'Desde: '.$date->copy()->setTime(0, 0, 0), 'LTBR', 0, 'L');
        $pdf->Cell(Helper::percentValue(50, $fullWidth), 6, 'Hasta: '.$dateEnd->setTime(23, 59, 59), 'LTBR', 1, 'L');

        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(8, 145, 237);
        $pdf->SetTextColor(255);
        $pdf->MultiCell(0, 6, 'Venta General', 'LTR', 'C', 1);
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(Helper::percentValue(40, $fullWidth), 6, 'Total: '.Helper::formatMoney($sales->sum('total')), 'LTBR', 0, 'L');
        $pdf->Cell(Helper::percentValue(40, $fullWidth), 6, 'Enviado: '.Helper::formatMoney($dianSales->sum('total')), 'LTBR', 0, 'L');
        $dianPercent = round(($dianSales->sum('total') / $sales->sum('total')) * 100, 2);
        $pdf->SetTextColor(10, 146, 3);
        $pdf->Cell(Helper::percentValue(20, $fullWidth), 6, '% Enviado: '.$dianPercent.'%', 'LTBR', 1, 'L');
        $pdf->SetTextColor(0);
        $taxPercent = round($dianSales->sum('taxes') / $dianSales->sum('subtotal') * 100, 2);
        $taxTotalPercent = round($dianSales->sum('taxes') / $sales->sum('subtotal') * 100, 2);
        $pdf->Cell(Helper::percentValue(50, $fullWidth), 6, '% Impuesto Enviado: '.$taxPercent.'%', 'LTBR', 0, 'L');
        $pdf->Cell(Helper::percentValue(50, $fullWidth), 6, '% Impuesto General: '.$taxTotalPercent.'%', 'LTBR', 1, 'L');
        $pdf->Cell(Helper::percentValue(50, $fullWidth), 6, 'Ventas Efectivo: '.Helper::formatMoney($sales->sum('total') - $vaucherPayments->sum('price')), 'LTBR', 0, 'L');
        $pdf->Cell(Helper::percentValue(50, $fullWidth), 6, 'Ventas Bancos: '.Helper::formatMoney($vaucherPayments->sum('price')), 'LTBR', 1, 'L');

        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(8, 145, 237);
        $pdf->SetTextColor(255);
        $pdf->MultiCell(0, 6, 'Detalle Por Usuario', 'LTR', 'C', 1);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(Helper::percentValue(40, $fullWidth), 6, 'Usuario', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, 'Ventas', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, 'Porcentaje', 'LTBR', 1, 'C', 1);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0);

        foreach ($salesByUser as $k => $salesUser) {
            $pdf->SetFillColor(255);

            if (0 == ($k + 1) % 2) {
                $pdf->SetFillColor(211, 224, 236);
            }
            $pdf->Cell(Helper::percentValue(40, $fullWidth), 6, $salesUser[0]->createdBy?->name, 'LTBR', 0, 'L', 1);
            $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, Helper::formatMoney($salesUser->sum('total')), 'LTBR', 0, 'R', 1);
            $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, round(($salesUser->sum('total') / $sales->sum('total')) * 100, 2).'%', 'LTBR', 1, 'C', 1);
        }

        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(8, 145, 237);
        $pdf->SetTextColor(255);
        $pdf->MultiCell(0, 6, 'Detalle Por Sucursal', 'LTR', 'C', 1);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(Helper::percentValue(40, $fullWidth), 6, 'Sucursal', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, 'Ventas', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, 'Porcentaje', 'LTBR', 1, 'C', 1);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0);

        $k = 0;

        foreach ($salesBySubsidiary as $salesRoom) {
            $pdf->SetFillColor(255);

            if (0 == ($k + 1) % 2) {
                $pdf->SetFillColor(211, 224, 236);
            }
            $pdf->Cell(Helper::percentValue(40, $fullWidth), 6, $salesRoom[0]->subsidiary?->name, 'LTBR', 0, 'C', 1);
            $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, Helper::formatMoney($salesRoom->sum('total')), 'LTBR', 0, 'R', 1);
            $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, round(($salesRoom->sum('total') / $sales->sum('total')) * 100, 2).'%', 'LTBR', 1, 'C', 1);
            ++$k;
        }

        if (!file_exists(storage_path('app/pdfs'))) {
            mkdir(storage_path('app/pdfs'));
        }

        if (file_exists(storage_path("app/pdfs/informe_diario_{$businessId}_{$date->format('Ymd')}.pdf"))) {
            unlink(storage_path("app/pdfs/informe_diario_{$businessId}_{$date->format('Ymd')}.pdf"));
        }
        $pdf->Output(storage_path("app/pdfs/informe_diario_{$businessId}_{$date->format('Ymd')}.pdf"), 'F');

        return storage_path("app/pdfs/informe_diario_{$businessId}_{$date->format('Ymd')}.pdf");
        // $pdf->Output('I');
        // exit;
    }
}
