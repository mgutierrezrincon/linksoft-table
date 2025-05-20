<?php

namespace App\Helpers;

use App\Fpdf\MyPdf;
use App\Models\CashSession;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Room;
use Carbon\CarbonInterface;

class AdminDailyStats
{
    public static function savePdf($date = null)
    {
        if (is_null($date)) {
            $date = now();
        }

        $realDay = $date->copy()->subDay();
        $cashSessions = CashSession::where('created_at', '>=', $realDay->copy()->setTime(0, 0, 0))->where('created_at', '<=', $realDay->copy()->setTime(23, 59, 59))->orderBy('id', 'asc')->get();

        $dateEnd = $date->copy()->setTime(5, 59, 59);
        $sales = Payment::where('created_at', '>=', $cashSessions[0]->open_at)->where('created_at', '<=', $cashSessions[1]?->close_at ?? now())->orderBy('id', 'asc')->get();
        $salesByUser = $sales->groupBy('created_by');
        $salesByRoom = $sales->sortBy('rental.room_id')->groupBy('rental.room_id');
        $salesByRoomType = $sales->sortBy('rental.room.type')->groupBy('rental.room.type');
        $rentals = $sales->pluck('rental_id')->unique();
        $rentalsFull = Rental::whereIn('id', $rentals)->get();
        $vaucherPayments = $sales->filter(function ($payment) {
            return Payment::METHOD_VOUCHER == $payment->payment_method;
        });
        $cashPayments = $sales->filter(function ($payment) {
            return Payment::METHOD_CASH == $payment->payment_method;
        });

        $pdf = new MyPdf(size: 'letter');
        $fullWidth = 205.9;
        $pdf->headerReport = false;
        $pdf->SetMargins(5, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Image(public_path('assets/img/logo-dark.png'), 15, 7, 0, 15);
        $pdf->Cell(0, 4, config('billing.invoice.business_name'), 0, 1, 'R');
        // $pdf->Cell(0, 4, 'Nit: '.config('billing.invoice.document_number'), 0, 1, 'R');
        $pdf->Cell(0, 4, 'Dirección: '.config('billing.invoice.address'), 0, 1, 'R');
        $pdf->Cell(0, 4, 'Teléfono: '.config('billing.invoice.cellphone'), 0, 1, 'R');
        // $pdf->Cell(0, 4, 'Email: '.config('billing.invoice.email'), 0, 1, 'R');
        // $pdf->Cell(0, 4, config('billing.invoice.regime'), 0, 1, 'R');
        $pdf->Ln(3);

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetFillColor(8, 145, 237);
        $pdf->SetTextColor(255);
        $pdf->MultiCell(0, 7, 'Informe Diario #'.(now()->dayOfYear() - 163), 'LTR', 'C', 1);
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(Helper::percentValue(50, $fullWidth), 6, 'Desde: '.$date->copy()->subDay()->setTime(6, 0, 0), 'LTBR', 0, 'L');
        $pdf->Cell(Helper::percentValue(50, $fullWidth), 6, 'Hasta: '.$dateEnd->setTime(5, 59, 59), 'LTBR', 1, 'L');

        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(8, 145, 237);
        $pdf->SetTextColor(255);
        $pdf->MultiCell(0, 6, 'Venta General', 'LTR', 'C', 1);
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(Helper::percentValue(40, $fullWidth), 6, 'Total General: '.Helper::formatMoney($sales->sum('price')), 'LTBR', 0, 'L');
        $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, 'Total Efectivo: '.Helper::formatMoney($cashPayments->sum('price')), 'LTBR', 0, 'L');
        $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, 'Total Datafono: '.Helper::formatMoney($vaucherPayments->sum('price')), 'LTBR', 1, 'L');
        $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, 'Taxis: '.Helper::formatMoney($cashSessions->sum('taxis')), 'LTBR', 0, 'L');
        $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, 'Restaurantes: '.Helper::formatMoney($cashSessions->sum('restaurants')), 'LTBR', 0, 'L');
        $pdf->Cell(Helper::percentValue(40, $fullWidth), 6, 'Efectivo Neto: '.Helper::formatMoney($cashPayments->sum('price') - $cashSessions->sum('taxis') - $cashSessions->sum('restaurants')), 'LTBR', 1, 'L');

        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(8, 145, 237);
        $pdf->SetTextColor(255);
        $pdf->MultiCell(0, 6, 'Detalle General', 'LTR', 'C', 1);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(Helper::percentValue(5, $fullWidth), 6, '# Hab', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, 'Placa', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, 'Atendio', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(15, $fullWidth), 6, 'Entrada', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(15, $fullWidth), 6, 'Salida', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(9, $fullWidth), 6, 'Tiempo', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, 'Habit.', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, 'Hora Add', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, 'Pers Add', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, 'Otros', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, 'Consumo', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, 'Total', 'LTBR', 1, 'C', 1);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0);

        foreach ($rentalsFull as $k => $sale) {
            $pdf->SetFillColor(255);

            if (0 == ($k + 1) % 2) {
                $pdf->SetFillColor(211, 224, 236);
            }

            $totalRoom = $sale->rentalProducts()->whereProductId(1)->sum('total');
            $totalAdditionalHours = $sale->rentalProducts()->whereProductId(228)->sum('total');
            $totalAdditionalPersons = $sale->rentalProducts()->whereProductId(229)->sum('total');
            $totalOther = $sale->rentalProducts()->whereProductId(214)->sum('total');
            $totalOthers = $sale->rentalProducts()->whereNotIn('product_id', [1, 228, 229, 214])->sum('total');

            $pdf->Cell(Helper::percentValue(5, $fullWidth), 6, $sale->room?->name, 'LTBR', 0, 'C', 1);
            $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, strtoupper($sale->car_plate), 'LTBR', 0, 'C', 1);
            $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, $sale->createdBy?->name, 'LTBR', 0, 'L', 1);
            $pdf->Cell(Helper::percentValue(15, $fullWidth), 6, $sale->start_time, 'LTBR', 0, 'L', 1);
            $pdf->Cell(Helper::percentValue(15, $fullWidth), 6, $sale->sale?->created_at, 'LTBR', 0, 'L', 1);
            $pdf->Cell(Helper::percentValue(9, $fullWidth), 6, str_replace(['después'], '', $sale->sale?->created_at->diffForHumans($sale->start_time, CarbonInterface::DIFF_RELATIVE_AUTO, true, 6)), 'LTBR', 0, 'L', 1);
            $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, Helper::formatMoney($totalRoom), 'LTBR', 0, 'R', 1);
            $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, Helper::formatMoney($totalAdditionalHours), 'LTBR', 0, 'R', 1);
            $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, Helper::formatMoney($totalAdditionalPersons), 'LTBR', 0, 'R', 1);
            $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, Helper::formatMoney($totalOther), 'LTBR', 0, 'R', 1);
            $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, Helper::formatMoney($totalOthers), 'LTBR', 0, 'R', 1);
            $pdf->Cell(Helper::percentValue(7, $fullWidth), 6, Helper::formatMoney($sale->total), 'LTBR', 1, 'R', 1);
        }

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
            $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, Helper::formatMoney($salesUser->sum('price')), 'LTBR', 0, 'R', 1);
            $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, round(($salesUser->sum('price') / $sales->sum('price')) * 100, 2).'%', 'LTBR', 1, 'C', 1);
        }

        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(8, 145, 237);
        $pdf->SetTextColor(255);
        $pdf->MultiCell(0, 6, 'Detalle Por Habitacion', 'LTR', 'C', 1);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(Helper::percentValue(40, $fullWidth), 6, 'Habitacion', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, 'Ventas', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, 'Porcentaje', 'LTBR', 1, 'C', 1);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0);

        foreach ($salesByRoom as $k => $salesRoom) {
            $pdf->SetFillColor(255);

            if (0 == ($k + 1) % 2) {
                $pdf->SetFillColor(211, 224, 236);
            }
            $pdf->Cell(Helper::percentValue(40, $fullWidth), 6, $salesRoom[0]->rental?->room?->name, 'LTBR', 0, 'C', 1);
            $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, Helper::formatMoney($salesRoom->sum('price')), 'LTBR', 0, 'R', 1);
            $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, round(($salesRoom->sum('price') / $sales->sum('price')) * 100, 2).'%', 'LTBR', 1, 'C', 1);
        }

        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(8, 145, 237);
        $pdf->SetTextColor(255);
        $pdf->MultiCell(0, 6, 'Detalle Por Tipo de Habitacion', 'LTR', 'C', 1);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(Helper::percentValue(40, $fullWidth), 6, 'Tipo Habitacion', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, 'Ventas', 'LTBR', 0, 'C', 1);
        $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, 'Porcentaje', 'LTBR', 1, 'C', 1);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0);
        $k = 0;

        foreach ($salesByRoomType as $salesRoomType) {
            $pdf->SetFillColor(255);

            if (0 == ($k + 1) % 2) {
                $pdf->SetFillColor(211, 224, 236);
            }
            $pdf->Cell(Helper::percentValue(40, $fullWidth), 6, Room::types()[$salesRoomType[0]->rental?->room?->type], 'LTBR', 0, 'C', 1);
            $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, Helper::formatMoney($salesRoomType->sum('price')), 'LTBR', 0, 'R', 1);
            $pdf->Cell(Helper::percentValue(30, $fullWidth), 6, round(($salesRoomType->sum('price') / $sales->sum('price')) * 100, 2).'%', 'LTBR', 1, 'C', 1);
            ++$k;
        }

        if (!file_exists(storage_path('app/pdfs'))) {
            mkdir(storage_path('app/pdfs'));
        }

        if (file_exists(storage_path("app/pdfs/informe_diario_admin_{$date->format('Ymd')}.pdf"))) {
            unlink(storage_path("app/pdfs/informe_diario_admin_{$date->format('Ymd')}.pdf"));
        }
        $pdf->Output(storage_path("app/pdfs/informe_diario_admin_{$date->format('Ymd')}.pdf"), 'F');

        return storage_path("app/pdfs/informe_diario_admin_{$date->format('Ymd')}.pdf");
        // $pdf->Output('I');
        // exit;
    }
}
