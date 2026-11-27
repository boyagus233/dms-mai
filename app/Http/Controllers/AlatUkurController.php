<?php

namespace App\Http\Controllers;

use App\Models\AlatUkur;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RealRashid\SweetAlert\Facades\Alert;

class AlatUkurController extends Controller
{
    /**
     * Build filtered query shared between index() and export().
     */
    protected function buildFilteredQuery(Request $request)
    {
        $query = AlatUkur::query()->withCount('kalibrasiRecords');

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('asset_code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhere('standard_spec', 'LIKE', "%{$search}%")
                  ->orWhere('brand_model', 'LIKE', "%{$search}%")
                  ->orWhere('serial_number', 'LIKE', "%{$search}%")
                  ->orWhere('calibrator_ref', 'LIKE', "%{$search}%")
                  ->orWhere('location', 'LIKE', "%{$search}%")
                  ->orWhere('notes', 'LIKE', "%{$search}%");
            });
        }

        if ($category = $request->input('category_type')) {
            if (in_array($category, ['KI', 'KE'], true)) {
                $query->where('category_type', $category);
            }
        }

        if ($location = trim((string) $request->input('location', ''))) {
            $query->where('location', $location);
        }

        if ($status = $request->input('current_status')) {
            if (array_key_exists($status, AlatUkur::STATUS_OPTIONS)) {
                $query->where('current_status', $status);
            }
        }

        return $query;
    }

    /**
     * Shared lookup lists for form dropdowns/datalists.
     */
    protected function formOptions(): array
    {
        $areas = AlatUkur::query()
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        $calibrators = AlatUkur::query()
            ->whereNotNull('calibrator_ref')
            ->where('calibrator_ref', '!=', '')
            ->distinct()
            ->orderBy('calibrator_ref')
            ->pluck('calibrator_ref');

        return [
            'areas'       => $areas,
            'calibrators' => $calibrators,
            'statuses'    => AlatUkur::STATUS_OPTIONS,
        ];
    }

    /**
     * Validate request payload and handle optional picture upload cleanly for store/update.
     */
    protected function validateAndPrepare(Request $request, ?AlatUkur $existing = null): array
    {
        $validated = $request->validate([
            'asset_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('m_alat_ukur', 'asset_code')->ignore($existing?->id),
            ],
            'category_type'               => ['required', Rule::in(['KI', 'KE'])],
            'name'                        => ['required', 'string', 'max:150'],
            'standard_spec'               => ['nullable', 'string', 'max:100'],
            'brand_model'                 => ['nullable', 'string', 'max:120'],
            'serial_number'               => ['nullable', 'string', 'max:100'],
            'calibrator_ref'              => ['nullable', 'string', 'max:150'],
            'location'                    => ['nullable', 'string', 'max:100'],
            'calibration_interval_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'last_calibration_date'       => ['nullable', 'date'],
            'next_calibration_date'       => ['nullable', 'date', 'after_or_equal:last_calibration_date'],
            'current_status'              => ['required', Rule::in(array_keys(AlatUkur::STATUS_OPTIONS))],
            'picture'                     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_picture'              => ['nullable', 'boolean'],
            'notes'                       => ['nullable', 'string', 'max:2000'],
        ], [
            'asset_code.required' => 'No. Alat Ukur wajib diisi.',
            'asset_code.unique'   => 'No. Alat Ukur sudah terdaftar pada master data.',
            'name.required'       => 'Nama Alat Ukur wajib diisi.',
            'next_calibration_date.after_or_equal' => 'Next Kalibrasi harus sama atau lebih besar dari Last Kalibrasi.',
        ]);

        $photoPath = $existing?->photo_path;

        if ($existing && $request->boolean('remove_picture') && $photoPath) {
            if (Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = null;
        }

        if ($request->hasFile('picture')) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            $file = $request->file('picture');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $filename = Str::slug(trim($validated['asset_code'])) . '_' . time() . '_' . Str::random(6) . '.' . $ext;
            $photoPath = $file->storeAs('alat-ukur', $filename, 'public');
        }

        return [
            'asset_code'                  => trim($validated['asset_code']),
            'category_type'               => $validated['category_type'],
            'name'                        => trim($validated['name']),
            'standard_spec'               => isset($validated['standard_spec']) ? (trim($validated['standard_spec']) ?: null) : null,
            'brand_model'                 => isset($validated['brand_model']) ? (trim($validated['brand_model']) ?: null) : null,
            'serial_number'               => isset($validated['serial_number']) ? (trim($validated['serial_number']) ?: null) : null,
            'calibrator_ref'              => isset($validated['calibrator_ref']) ? (trim($validated['calibrator_ref']) ?: null) : null,
            'location'                    => isset($validated['location']) ? (trim($validated['location']) ?: null) : null,
            'calibration_interval_months' => (int) ($validated['calibration_interval_months'] ?? 12),
            'last_calibration_date'       => $validated['last_calibration_date'] ?? null,
            'next_calibration_date'       => $validated['next_calibration_date'] ?? null,
            'current_status'              => $validated['current_status'],
            'photo_path'                  => $photoPath,
            'notes'                       => isset($validated['notes']) ? (trim($validated['notes']) ?: null) : null,
        ];
    }

    /**
     * Master Data Alat Ukur list.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 25);
        if (!in_array($perPage, [10, 25, 50, 100, 250], true)) {
            $perPage = 25;
        }

        $tools = $this->buildFilteredQuery($request)
            ->orderBy('asset_code', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        $counts = [
            'total'    => AlatUkur::count(),
            'ki'       => AlatUkur::where('category_type', 'KI')->count(),
            'ke'       => AlatUkur::where('category_type', 'KE')->count(),
            'active'   => AlatUkur::where('current_status', 'ACTIVE')->count(),
            'inactive' => AlatUkur::where('current_status', '!=', 'ACTIVE')->count(),
        ];

        $areas = AlatUkur::query()
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        $statuses = AlatUkur::STATUS_OPTIONS;

        return view('content.alat-ukur.index', compact('tools', 'counts', 'areas', 'statuses'));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        return view('content.alat-ukur.create', $this->formOptions());
    }

    /**
     * Store newly created Alat Ukur.
     */
    public function store(Request $request)
    {
        $data = $this->validateAndPrepare($request);
        $data['created_by'] = Auth::id();

        try {
            $tool = AlatUkur::create($data);
            Alert::success('Berhasil', "Alat ukur {$tool->asset_code} ({$tool->name}) berhasil ditambahkan.");
            return redirect()->route('alat-ukur.index');
        } catch (Exception $e) {
            Alert::error('Gagal', 'Gagal menyimpan alat ukur: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Show single Alat Ukur detail & history.
     */
    public function show(AlatUkur $alatUkur)
    {
        $alatUkur->load([
            'creator',
            'updater',
            'kalibrasiRecords' => fn($q) => $q->with(['technician', 'approver', 'vendor'])->limit(20),
        ]);

        return view('content.alat-ukur.show', compact('alatUkur'));
    }

    /**
     * Show edit form.
     */
    public function edit(AlatUkur $alatUkur)
    {
        return view('content.alat-ukur.edit', array_merge(['alatUkur' => $alatUkur], $this->formOptions()));
    }

    /**
     * Update Alat Ukur in DB.
     */
    public function update(Request $request, AlatUkur $alatUkur)
    {
        $data = $this->validateAndPrepare($request, $alatUkur);
        $data['updated_by'] = Auth::id();

        try {
            $alatUkur->update($data);
            Alert::success('Berhasil', "Data alat ukur {$alatUkur->asset_code} berhasil diperbarui.");
            return redirect()->route('alat-ukur.index');
        } catch (Exception $e) {
            Alert::error('Gagal', 'Gagal memperbarui data: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Delete Alat Ukur (and clean up uploaded photo).
     */
    public function destroy(AlatUkur $alatUkur)
    {
        try {
            $code = $alatUkur->asset_code;
            $photoPath = $alatUkur->photo_path;

            $alatUkur->delete();

            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }

            Alert::success('Berhasil', "Alat ukur {$code} berhasil dihapus dari master data.");
        } catch (Exception $e) {
            Alert::error('Gagal', 'Gagal menghapus alat ukur: ' . $e->getMessage());
        }

        return redirect()->route('alat-ukur.index');
    }

    /**
     * Stream Alat Ukur picture safely regardless of public/storage symlink state.
     */
    public function photo(AlatUkur $alatUkur)
    {
        if (!$alatUkur->photo_path) {
            abort(404);
        }
        $normalized = ltrim($alatUkur->photo_path, '/');
        if (!Storage::disk('public')->exists($normalized)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($normalized), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Export filtered Master Data Alat Ukur to CSV matching the 13 Excel columns.
     */
    public function export(Request $request)
    {
        $tools = $this->buildFilteredQuery($request)->orderBy('asset_code', 'asc')->get();
        $fileName = 'Master_Alat_Ukur_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($tools) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, [
                'No. Alat Ukur',
                'KI/KE',
                'Alat Ukur',
                'Standard',
                'Model',
                'No.Serial',
                'Kalibrator',
                'Area',
                'Last Kalibrasi',
                'Next Kalibrasi',
                'Status',
                'Picture',
                'Note',
            ]);

            foreach ($tools as $tool) {
                fputcsv($file, [
                    $tool->asset_code,
                    $tool->category_type,
                    $tool->name,
                    $tool->standard_spec ?: '-',
                    $tool->brand_model ?: '-',
                    $tool->serial_number ?: '-',
                    $tool->calibrator_ref ?: '-',
                    $tool->location ?: '-',
                    $tool->last_calibration_date ? $tool->last_calibration_date->format('Y-m-d') : '-',
                    $tool->next_calibration_date ? $tool->next_calibration_date->format('Y-m-d') : '-',
                    $tool->status_meta['label'],
                    $tool->photo_path ?: '-',
                    $tool->notes ?: '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
