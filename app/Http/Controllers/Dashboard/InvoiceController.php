<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Alignment, Border};
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    protected string $title;
    protected string $page_title;

    public function __construct()
    {
        // app()->setLocale('en');

        $this->middleware('can:invoices.view')->only(['index', 'show', 'datatable']);

        $this->title = t('invoices.list');
        $this->page_title = t('invoices.title');

        view()->share([
            'title' => $this->title,
            'page_title' => $this->page_title,
        ]);
    }

    public function index()
    {
        return view('dashboard.invoices.index');
    }

    public function datatable(DataTables $datatable, Request $request)
    {
        $query = Invoice::query()
            ->with(['user'])
            ->select('invoices.*')
            ->latest('id');

        // search (invoice number + user name/mobile/email)
        if ($search = trim((string) $request->get('search_custom'))) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            })->orWhereHas('user', function ($uq) use ($search) {
                $uq->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%"); // عدّل لو اسم العمود مختلف
            });
        }

        // filters
        if ($status = $request->get('status')) {
            if (in_array($status, ['unpaid', 'paid', 'cancelled', 'refunded'], true)) {
                $query->where('status', $status);
            }
        }

        if ($type = $request->get('type')) {
            if (in_array($type, ['invoice', 'adjustment', 'credit_note'], true)) {
                $query->where('type', $type);
            }
        }

        if ($request->filled('from')) {
            $query->whereDate('issued_at', '>=', $request->get('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('issued_at', '<=', $request->get('to'));
        }

        if ($locked = $request->get('locked')) {
            if ($locked === 'yes')
                $query->where('is_locked', true);
            if ($locked === 'no')
                $query->where('is_locked', false);
        }

        return $datatable->eloquent($query)
            ->addColumn('user_label', function (Invoice $row) {
                $name = $row->user?->name ?? '—';
                $mobile = $row->user?->mobile ?? null;
                return e($mobile ? ($name . ' - ' . $mobile) : $name);
            })
            ->addColumn('type_badge', function (Invoice $row) {
                $map = [
                    'invoice' => 'primary',
                    'adjustment' => 'warning',
                    'credit_note' => 'info',
                ];
                $cls = $map[$row->type] ?? 'secondary';
                return '<span class="badge badge-light-' . $cls . '">' . e(__('invoices.type.' . $row->type)) . '</span>';
            })
            ->addColumn('status_badge', function (Invoice $row) {
                $map = [
                    'unpaid' => 'danger',
                    'paid' => 'success',
                    'cancelled' => 'secondary',
                    'refunded' => 'warning',
                ];
                $cls = $map[$row->status] ?? 'secondary';
                return '<span class="badge badge-light-' . $cls . '">' . e(__('invoices.status.' . $row->status)) . '</span>';
            })
            ->addColumn('locked_badge', function (Invoice $row) {
                if (!$row->is_locked)
                    return '—';
                return '<span class="badge badge-light-dark">' . e(__('invoices.locked')) . '</span>';
            })
            ->editColumn('subtotal', fn(Invoice $row) => format_currency((float) $row->subtotal))
            ->editColumn('discount', fn(Invoice $row) => format_currency((float) $row->discount))
            ->editColumn('tax', fn(Invoice $row) => format_currency((float) $row->tax))
            ->editColumn('total', fn(Invoice $row) => format_currency((float) $row->total))
            ->addColumn('issued_at_label', fn(Invoice $row) => $row->issued_at ? $row->issued_at->format('Y-m-d H:i') : '—')
            ->addColumn('paid_at_label', fn(Invoice $row) => $row->paid_at ? $row->paid_at->format('Y-m-d H:i') : '—')
            ->addColumn('invoiceable_label', function (Invoice $row) {
                $short = class_basename($row->invoiceable_type);
                $data = invoiceable_label($short, (int) $row->invoiceable_id, $row->invoiceable);
                if (!$data)
                    return '—';
                return $data['label'];
            })
            ->addColumn('actions', function (Invoice $row) {
                return view('dashboard.invoices._actions', ['invoice' => $row])->render();
            })
            ->rawColumns(['type_badge', 'status_badge', 'locked_badge', 'actions', 'total', 'subtotal', 'discount', 'tax'])
            ->make(true);
    }

    public function show(Invoice $invoice)
    {
        $this->title = __('invoices.view');
        $this->page_title = $this->title;

        view()->share([
            'title' => $this->page_title,
            'page_title' => $this->page_title,
        ]);

        $short = class_basename($invoice->invoiceable_type);
        $data = invoiceable_label($short, (int) $invoice->invoiceable_id, $invoice->invoiceable);
        $invoiceableLabels = $data['label'] ?? '—';
        $invoiceableRoute = $data['route'] ?? null;


        $invoice->loadMissing([
            'user',
            'items',
            'items.itemable',
            'parent',
            'children',
            'payments',
            'invoiceable'
        ]);

        return view('dashboard.invoices.show', compact('invoice', 'invoiceableLabels'));
    }

    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'from'   => ['required', 'date_format:Y-m-d'],
            'to'     => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', 'in:unpaid,paid,cancelled,refunded'],
        ]);

        $from   = $request->input('from');
        $to     = $request->input('to');
        $status = $request->input('status');

        $invoices = Invoice::query()
            ->with(['user:id,name,mobile'])
            ->whereDate('issued_at', '>=', $from)
            ->whereDate('issued_at', '<=', $to)
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderBy('issued_at')
            ->orderBy('id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('الفواتير');
        $sheet->setRightToLeft(true);

        // ── Headers ──────────────────────────────────────────────
        $headers = [
            'A' => ['label' => '#',               'width' => 8],
            'B' => ['label' => 'رقم الفاتورة',    'width' => 20],
            'C' => ['label' => 'الزبون',          'width' => 25],
            'D' => ['label' => 'الجوال',          'width' => 16],
            'E' => ['label' => 'المبلغ الفرعي',   'width' => 14],
            'F' => ['label' => 'الخصم',           'width' => 12],
            'G' => ['label' => 'الضريبة',         'width' => 12],
            'H' => ['label' => 'الإجمالي',        'width' => 14],
            'I' => ['label' => 'العملة',          'width' => 10],
            'J' => ['label' => 'الحالة',          'width' => 14],
            'K' => ['label' => 'تاريخ الإصدار',  'width' => 20],
            'L' => ['label' => 'تاريخ الدفع',    'width' => 20],
        ];

        $headerFill = [
            'fillType'   => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '1F4E79'],
        ];
        $headerFont = [
            'bold'  => true,
            'color' => ['rgb' => 'FFFFFF'],
            'name'  => 'Arial',
            'size'  => 11,
        ];
        $centerAlign = [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical'   => Alignment::VERTICAL_CENTER,
        ];
        $thinBorder = [
            'borderStyle' => Border::BORDER_THIN,
            'color'       => ['rgb' => 'CCCCCC'],
        ];

        foreach ($headers as $col => $def) {
            $cell = $sheet->getCell("{$col}1");
            $cell->setValue($def['label']);
            $cell->getStyle()->applyFromArray([
                'fill'      => $headerFill,
                'font'      => $headerFont,
                'alignment' => $centerAlign,
                'borders'   => ['allBorders' => $thinBorder],
            ]);
            $sheet->getColumnDimension($col)->setWidth($def['width']);
        }

        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->freezePane('A2');

        // ── Rows ─────────────────────────────────────────────────
        $rowIndex = 2;
        $oddFill  = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F8FF']];
        $evenFill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']];

        $statusColors = [
            'paid'      => '1F7A1F',
            'unpaid'    => 'B8860B',
            'cancelled' => 'CC0000',
            'refunded'  => '0055CC',
        ];
        $statusLabels = [
            'paid'      => 'مدفوعة',
            'unpaid'    => 'غير مدفوعة',
            'cancelled' => 'ملغاة',
            'refunded'  => 'مستردة',
        ];

        foreach ($invoices as $invoice) {
            $fill = ($rowIndex % 2 === 0) ? $evenFill : $oddFill;

            $row = [
                'A' => $invoice->id,
                'B' => $invoice->number,
                'C' => $invoice->user?->name ?? '—',
                'D' => $invoice->user?->mobile ?? '—',
                'E' => number_format((float) $invoice->subtotal, 2),
                'F' => number_format((float) $invoice->discount, 2),
                'G' => number_format((float) $invoice->tax, 2),
                'H' => number_format((float) $invoice->total, 2),
                'I' => $invoice->currency,
                'J' => $statusLabels[$invoice->status] ?? $invoice->status,
                'K' => $invoice->issued_at?->format('Y-m-d H:i'),
                'L' => $invoice->paid_at?->format('Y-m-d H:i') ?? '—',
            ];

            foreach ($row as $col => $value) {
                $cell = $sheet->getCell("{$col}{$rowIndex}");
                $cell->setValue($value);
                $cell->getStyle()->applyFromArray([
                    'fill'      => $fill,
                    'font'      => ['name' => 'Arial', 'size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'borders'   => ['allBorders' => $thinBorder],
                ]);
            }

            // أعمدة الوسط
            foreach (['A', 'I', 'J', 'K', 'L'] as $col) {
                $sheet->getCell("{$col}{$rowIndex}")->getStyle()
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // الأرقام يمين
            foreach (['E', 'F', 'G', 'H'] as $col) {
                $sheet->getCell("{$col}{$rowIndex}")->getStyle()
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // لون الحالة
            $statusColor = $statusColors[$invoice->status] ?? '333333';
            $sheet->getCell("J{$rowIndex}")->getStyle()->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $statusColor]],
            ]);

            $sheet->getRowDimension($rowIndex)->setRowHeight(22);
            $rowIndex++;
        }

        // ── Summary row ──────────────────────────────────────────
        $totalRow = $rowIndex;
        $lastData = $totalRow - 1;

        $sheet->getCell("A{$totalRow}")->setValue('الإجمالي');
        $sheet->getCell("E{$totalRow}")->setValue("=SUM(E2:E{$lastData})");
        $sheet->getCell("F{$totalRow}")->setValue("=SUM(F2:F{$lastData})");
        $sheet->getCell("G{$totalRow}")->setValue("=SUM(G2:G{$lastData})");
        $sheet->getCell("H{$totalRow}")->setValue("=SUM(H2:H{$lastData})");
        $sheet->getCell("B{$totalRow}")->setValue("=COUNTA(B2:B{$lastData}) & \" فاتورة\"");

        $sheet->getStyle("A{$totalRow}:L{$totalRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']],
            'font' => ['bold' => true, 'name' => 'Arial', 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── Metadata ─────────────────────────────────────────────
        $spreadsheet->getProperties()
            ->setTitle("الفواتير {$from} – {$to}")
            ->setCreator('Dashboard');

        // ── Stream ───────────────────────────────────────────────
        $suffix   = $status ? "_{$status}" : '';
        $filename = "invoices_{$from}_{$to}{$suffix}.xlsx";
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn() => $writer->save('php://output'),
            $filename,
            [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control'       => 'max-age=0',
            ]
        );
    }
}