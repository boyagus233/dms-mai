<?php

namespace App\Http\Controllers;

use App\Models\AlatUkur;
use App\Models\Kalibrasi;
use App\Models\User;
use App\Models\Vendor;
use App\Services\KalibrasiPdfService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;

class KalibrasiController extends Controller
{
    public function __construct(
        protected KalibrasiPdfService $pdfService
    ) {}

    /* ------------------------------------------------------------------ */
    /*  1. TAB 1: DASHBOARD UTAMA - PERLU KALIBRASI                        */
    /* ------------------------------------------------------------------ */

    public function index(Request $request)
    {
        $query = AlatUkur::query();

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('asset_code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhere('brand_model', 'LIKE', "%{$search}%")
                  ->orWhere('serial_number', 'LIKE', "%{$search}%")
                  ->orWhere('location', 'LIKE', "%{$search}%");
            });
        }

        // Category filter (KI / KE / ALL)
        if ($cat = $request->input('category')) {
            if (in_array($cat, ['KI', 'KE'])) {
                $query->where('category_type', $cat);
            }
        }

        // Urgency / Due filter
        $today = Carbon::today()->format('Y-m-d');
        $in30Days = Carbon::today()->addDays(30)->format('Y-m-d');

        if ($filterDue = $request->input('due_filter')) {
            if ($filterDue === 'overdue') {
                $query->where('next_calibration_date', '<', $today);
            } elseif ($filterDue === 'due_30') {
                $query->whereBetween('next_calibration_date', [$today, $in30Days]);
            } elseif ($filterDue === 'safe') {
                $query->where('next_calibration_date', '>', $in30Days);
            }
        }

        // Sort by next calibration date nearest (ASC), keeping unscheduled nulls last
        $perPage = (int) $request->input('per_page', 25);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }
        $tools = $query->orderByRaw('next_calibration_date IS NULL ASC, next_calibration_date ASC')->paginate($perPage)->withQueryString();

        // Summary counters
        $counts = [
            'total'            => AlatUkur::count(),
            'overdue'          => AlatUkur::where('next_calibration_date', '<', $today)->count(),
            'due_soon'         => AlatUkur::whereBetween('next_calibration_date', [$today, $in30Days])->count(),
            'pending_return'   => AlatUkur::where('current_status', 'OUT_OF_PREMISE')->count(),
            'pending_approval' => Kalibrasi::where('status', 'PENDING_APPROVAL')->count(),
        ];

        $vendors = Vendor::active()->orderBy('name')->get(['id', 'code', 'name']);

        return view('content.kalibrasi.index', compact('tools', 'counts', 'vendors'));
    }

    /* ------------------------------------------------------------------ */
    /*  2. KALIBRASI INTERNAL - FORM & SUBMIT                              */
    /* ------------------------------------------------------------------ */

    public function createInternal(AlatUkur $alatUkur)
    {
        $defaultCalDate = Carbon::today()->format('Y-m-d');
        $defaultNextDue = Carbon::today()->addMonths($alatUkur->calibration_interval_months ?: 12)->format('Y-m-d');

        $technician = Auth::user();
        $approver = $this->pdfService->resolveApprover($technician);

        return view('content.kalibrasi.internal_form', compact('alatUkur', 'defaultCalDate', 'defaultNextDue', 'technician', 'approver'));
    }

    public function storeInternal(Request $request, AlatUkur $alatUkur)
    {
        $request->validate([
            'calibration_date' => 'required|date',
            'next_due_date'    => 'required|date|after:calibration_date',
            'conclusion'       => 'required|in:PASS,PASS_WITH_NOTE,FAIL',
            'temperature'      => 'nullable|numeric|between:10,40',
            'humidity'         => 'nullable|numeric|between:10,95',
            'calibrator_used'  => 'nullable|string|max:150',
            'attachment_pdf'   => 'required|file|mimes:pdf|max:15360', // max 15MB
        ]);

        try {
            $technician = Auth::user();
            $approver = $this->pdfService->resolveApprover($technician);

            // Upload raw attachment PDF
            $file = $request->file('attachment_pdf');
            $fileName = 'worksheets/' . time() . '_' . Str::slug($alatUkur->asset_code) . '.pdf';
            $attachmentPath = $file->storeAs('kalibrasi', $fileName, 'public');

            // Generate certificate number
            $certNumber = $this->pdfService->generateCertificateNumber('INTERNAL');

            // Create record
            $kalibrasi = Kalibrasi::create([
                'certificate_number' => $certNumber,
                'alat_ukur_id'       => $alatUkur->id,
                'calibration_type'   => 'INTERNAL',
                'calibration_date'   => $request->calibration_date,
                'next_due_date'      => $request->next_due_date,
                'conclusion'         => $request->conclusion,
                'technician_id'      => $technician->id,
                'approver_id'        => $approver?->id,
                'temperature'        => $request->temperature ?: 20.0,
                'humidity'           => $request->humidity ?: 55.0,
                'calibrator_used'    => $request->calibrator_used ?: $alatUkur->calibrator_ref,
                'raw_attachment_pdf' => $attachmentPath,
                'status'             => 'PENDING_APPROVAL',
                'created_by'         => $technician->id,
            ]);

            // Update alat ukur operational status
            $alatUkur->update([
                'current_status' => 'PENDING_APPROVAL',
            ]);

            // Generate draft preview PDF (Page 1 cert + Page 2+ manual PDF)
            $previewRelativePath = $this->pdfService->generateMergedPdf($kalibrasi, false);
            $kalibrasi->update(['final_merged_pdf' => $previewRelativePath]);

            Alert::success('Berhasil Diajukan', "Sertifikat {$certNumber} telah dikirim ke antrean approval atasan.");
            return redirect()->route('kalibrasi.preview', $kalibrasi->id);
        } catch (Exception $e) {
            Alert::error('Gagal', 'Terjadi kesalahan: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /* ------------------------------------------------------------------ */
    /*  3. KALIBRASI EKSTERNAL - OUT OF PREMISE                            */
    /* ------------------------------------------------------------------ */

    public function markEksternal(Request $request, AlatUkur $alatUkur)
    {
        $alatUkur->update([
            'current_status' => 'OUT_OF_PREMISE',
        ]);

        Alert::success('Alat Ditandai Keluar', "Alat {$alatUkur->asset_code} telah berstatus Sedang Kalibrasi di luar pabrik.");
        return redirect()->route('kalibrasi.pending-return');
    }

    /* ------------------------------------------------------------------ */
    /*  4. TAB 2: PENDING RETURN (KALIBRASI EKSTERNAL)                     */
    /* ------------------------------------------------------------------ */

    public function pendingReturn(Request $request)
    {
        $perPage = (int) $request->input('per_page', 25);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }
        $tools = AlatUkur::outOfPremise()->orderBy('updated_at', 'desc')->paginate($perPage)->withQueryString();
        $vendors = Vendor::active()->orderBy('name')->get(['id', 'code', 'name']);

        return view('content.kalibrasi.pending_return', compact('tools', 'vendors'));
    }

    public function returnForm(AlatUkur $alatUkur)
    {
        $vendors = Vendor::active()->orderBy('name')->get();
        $defaultCalDate = Carbon::today()->format('Y-m-d');
        $defaultNextDue = Carbon::today()->addMonths($alatUkur->calibration_interval_months ?: 12)->format('Y-m-d');

        return view('content.kalibrasi.return_form', compact('alatUkur', 'vendors', 'defaultCalDate', 'defaultNextDue'));
    }

    public function storeReturn(Request $request, AlatUkur $alatUkur)
    {
        $request->validate([
            'certificate_number' => 'required|string|max:100',
            'vendor_id'          => 'nullable|exists:m_vendor,id',
            'vendor_name'        => 'nullable|string|max:200',
            'calibration_date'   => 'required|date',
            'next_due_date'      => 'required|date|after:calibration_date',
            'conclusion'         => 'required|in:PASS,PASS_WITH_NOTE,FAIL',
            'vendor_cert_pdf'    => 'required|file|mimes:pdf|max:15360',
        ]);

        try {
            $user = Auth::user();

            // Store uploaded vendor PDF
            $file = $request->file('vendor_cert_pdf');
            $fileName = 'vendor_certs/' . time() . '_' . Str::slug($alatUkur->asset_code) . '.pdf';
            $certPath = $file->storeAs('kalibrasi', $fileName, 'public');

            // Resolve vendor name
            $vendorName = $request->vendor_name;
            if ($request->vendor_id) {
                $vendor = Vendor::find($request->vendor_id);
                if ($vendor) {
                    $vendorName = $vendor->name;
                }
            }

            // Create completed record (Bypass Approval / Auto-Close)
            $kalibrasi = Kalibrasi::create([
                'certificate_number' => trim($request->certificate_number),
                'alat_ukur_id'       => $alatUkur->id,
                'calibration_type'   => 'EKSTERNAL',
                'calibration_date'   => $request->calibration_date,
                'next_due_date'      => $request->next_due_date,
                'conclusion'         => $request->conclusion,
                'technician_id'      => $user->id,
                'approver_id'        => null, // Auto-close / no approval
                'vendor_id'          => $request->vendor_id,
                'vendor_name'        => $vendorName ?: 'Vendor Kalibrasi Eksternal',
                'raw_attachment_pdf' => $certPath,
                'final_merged_pdf'   => $certPath,
                'status'             => 'COMPLETED',
                'approved_at'        => now(),
                'created_by'         => $user->id,
            ]);

            // Reactivate tool and update calibration schedule
            $alatUkur->update([
                'current_status'        => 'ACTIVE',
                'last_calibration_date' => $request->calibration_date,
                'next_calibration_date' => $request->next_due_date,
            ]);

            Alert::success('Kalibrasi Selesai', "Sertifikat kalibrasi eksternal {$request->certificate_number} berhasil disimpan dan alat aktif kembali.");
            return redirect()->route('kalibrasi.archive');
        } catch (Exception $e) {
            Alert::error('Gagal', 'Terjadi kesalahan: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /* ------------------------------------------------------------------ */
    /*  5. TAB 3: ANTREAN APPROVAL (KHUSUS ATASAN / MANAGER)               */
    /* ------------------------------------------------------------------ */

    public function approvalQueue(Request $request)
    {
        $user = Auth::user();

        $query = Kalibrasi::pendingApproval()->with(['alatUkur', 'technician']);

        // Filter for specific approver unless admin
        if (!$user->roles->contains('code', 'ADM')) {
            $query->where('approver_id', $user->id);
        }

        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }
        $items = $query->orderBy('created_at', 'asc')->paginate($perPage)->withQueryString();

        return view('content.kalibrasi.approval_queue', compact('items'));
    }

    /* ------------------------------------------------------------------ */
    /*  6. LIVE PREVIEW & 1-CLICK APPROVAL                                 */
    /* ------------------------------------------------------------------ */

    public function preview(Kalibrasi $kalibrasi)
    {
        $kalibrasi->load(['alatUkur', 'technician', 'approver']);

        // Ensure preview PDF exists
        if (!$kalibrasi->final_merged_pdf || !Storage::disk('public')->exists($kalibrasi->final_merged_pdf)) {
            $previewPath = $this->pdfService->generateMergedPdf($kalibrasi, $kalibrasi->status === 'COMPLETED');
            $kalibrasi->update(['final_merged_pdf' => $previewPath]);
        }

        $user = Auth::user();
        $canApprove = ($kalibrasi->status === 'PENDING_APPROVAL') &&
            ($kalibrasi->approver_id === $user->id || $user->roles->contains('code', 'ADM'));

        return view('content.kalibrasi.preview', compact('kalibrasi', 'canApprove'));
    }

    public function approve(Request $request, Kalibrasi $kalibrasi)
    {
        if ($kalibrasi->status !== 'PENDING_APPROVAL') {
            Alert::error('Peringatan', 'Dokumen ini tidak dalam status menunggu persetujuan.');
            return redirect()->back();
        }

        try {
            $user = Auth::user();

            // Set current user as approver if admin or matching
            $kalibrasi->approver_id = $user->id;
            $kalibrasi->status = 'COMPLETED';
            $kalibrasi->approved_at = now();

            // Generate final stamped & flattened PDF
            $finalPath = $this->pdfService->generateMergedPdf($kalibrasi, true);
            $kalibrasi->final_merged_pdf = $finalPath;
            $kalibrasi->save();

            // Reactivate tool and update schedule (+1 year)
            $kalibrasi->alatUkur->update([
                'current_status'        => 'ACTIVE',
                'last_calibration_date' => $kalibrasi->calibration_date,
                'next_calibration_date' => $kalibrasi->next_due_date,
            ]);

            Alert::success('Sertifikat Disetujui', "Sertifikat {$kalibrasi->certificate_number} berhasil disetujui, ditandatangani digital, dan alat aktif kembali.");
            return redirect()->route('kalibrasi.preview', $kalibrasi->id);
        } catch (Exception $e) {
            Alert::error('Gagal', 'Terjadi kesalahan saat menyetujui: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function reject(Request $request, Kalibrasi $kalibrasi)
    {
        $request->validate([
            'rejection_note' => 'required|string|max:500',
        ]);

        try {
            $kalibrasi->update([
                'status'         => 'REJECTED',
                'rejection_note' => $request->rejection_note,
            ]);

            // Revert tool status to active so it can be re-calibrated
            $kalibrasi->alatUkur->update([
                'current_status' => 'ACTIVE',
            ]);

            Alert::warning('Dokumen Ditolak', 'Pengajuan kalibrasi telah ditolak.');
            return redirect()->route('kalibrasi.approval-queue');
        } catch (Exception $e) {
            Alert::error('Gagal', 'Terjadi kesalahan: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /* ------------------------------------------------------------------ */
    /*  7. TAB 4: ARSIP SAH & RIWAYAT DOKUMEN                              */
    /* ------------------------------------------------------------------ */

    public function archive(Request $request)
    {
        $query = Kalibrasi::completed()->with(['alatUkur', 'technician', 'approver', 'vendor']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'LIKE', "%{$search}%")
                  ->orWhere('vendor_name', 'LIKE', "%{$search}%")
                  ->orWhereHas('alatUkur', function ($aq) use ($search) {
                      $aq->where('asset_code', 'LIKE', "%{$search}%")
                        ->orWhere('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($type = $request->input('type')) {
            if (in_array($type, ['INTERNAL', 'EKSTERNAL'])) {
                $query->where('calibration_type', $type);
            }
        }

        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }
        $records = $query->orderBy('calibration_date', 'desc')->paginate($perPage)->withQueryString();

        return view('content.kalibrasi.archive', compact('records'));
    }

    public function downloadPdf(Kalibrasi $kalibrasi)
    {
        $relPath = $kalibrasi->final_merged_pdf ?: $kalibrasi->raw_attachment_pdf;
        if (!$relPath || !Storage::disk('public')->exists($relPath)) {
            abort(404, 'File PDF sertifikat tidak ditemukan.');
        }

        $fullPath = Storage::disk('public')->path($relPath);
        $fileName = Str::slug($kalibrasi->certificate_number) . '.pdf';

        return response()->file($fullPath, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }
}
