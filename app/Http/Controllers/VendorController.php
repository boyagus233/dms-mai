<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;
use Exception;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $vendors = Vendor::orderBy('name', 'asc')->get();

        confirmDelete('Hapus Vendor!', 'Apakah Anda yakin ingin menghapus vendor ini?');

        return view('content.vendor.index', compact('vendors'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('content.vendor.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code'          => 'required|string|max:50|unique:m_vendor,code',
            'name'          => 'required|string|max:255',
            'official_name' => 'nullable|string|max:255',
            'phone'         => 'nullable|string|max:100',
            'fax'           => 'nullable|string|max:100',
            'pic_name'      => 'nullable|string|max:150',
            'email'         => 'nullable|email|max:255',
            'postal_code'   => 'nullable|string|max:20',
            'address'       => 'nullable|string',
            'notes'         => 'nullable|string',
            'is_supplier'   => 'nullable|boolean',
            'is_customer'   => 'nullable|boolean',
            'status'        => 'required|in:0,1',
        ]);

        try {
            Vendor::create([
                'code'          => Str::upper(trim($request->code)),
                'name'          => trim($request->name),
                'official_name' => $request->official_name ? trim($request->official_name) : null,
                'phone'         => $request->phone,
                'fax'           => $request->fax,
                'pic_name'      => $request->pic_name,
                'email'         => $request->email,
                'postal_code'   => $request->postal_code,
                'address'       => $request->address,
                'notes'         => $request->notes,
                'is_supplier'   => $request->has('is_supplier') ? 1 : 0,
                'is_customer'   => $request->has('is_customer') ? 1 : 0,
                'status'        => (int) $request->status,
                'created_by'    => Auth::id(),
            ]);

            Alert::success('Berhasil', 'Vendor berhasil ditambahkan.');
        } catch (Exception $e) {
            Alert::error('Gagal', 'Terjadi kesalahan: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }

        return redirect()->route('vendor.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Vendor $vendor)
    {
        return view('content.vendor.view', compact('vendor'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vendor $vendor)
    {
        return view('content.vendor.edit', compact('vendor'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vendor $vendor)
    {
        $request->validate([
            'code'          => 'required|string|max:50|unique:m_vendor,code,' . $vendor->id,
            'name'          => 'required|string|max:255',
            'official_name' => 'nullable|string|max:255',
            'phone'         => 'nullable|string|max:100',
            'fax'           => 'nullable|string|max:100',
            'pic_name'      => 'nullable|string|max:150',
            'email'         => 'nullable|email|max:255',
            'postal_code'   => 'nullable|string|max:20',
            'address'       => 'nullable|string',
            'notes'         => 'nullable|string',
            'is_supplier'   => 'nullable|boolean',
            'is_customer'   => 'nullable|boolean',
            'status'        => 'required|in:0,1',
        ]);

        try {
            $vendor->update([
                'code'          => Str::upper(trim($request->code)),
                'name'          => trim($request->name),
                'official_name' => $request->official_name ? trim($request->official_name) : null,
                'phone'         => $request->phone,
                'fax'           => $request->fax,
                'pic_name'      => $request->pic_name,
                'email'         => $request->email,
                'postal_code'   => $request->postal_code,
                'address'       => $request->address,
                'notes'         => $request->notes,
                'is_supplier'   => $request->has('is_supplier') ? 1 : 0,
                'is_customer'   => $request->has('is_customer') ? 1 : 0,
                'status'        => (int) $request->status,
                'updated_by'    => Auth::id(),
            ]);

            Alert::success('Berhasil', 'Data vendor berhasil diperbarui.');
        } catch (Exception $e) {
            Alert::error('Gagal', 'Terjadi kesalahan: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }

        return redirect()->route('vendor.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vendor $vendor)
    {
        try {
            $vendor->status = 0;
            $vendor->save();
            Alert::success('Berhasil', 'Vendor berhasil dinonaktifkan.');
        } catch (Exception $e) {
            Alert::error('Gagal', 'Gagal menghapus vendor: ' . $e->getMessage());
        }

        return redirect()->route('vendor.index');
    }

    /**
     * Export all vendors to CSV
     */
    public function export()
    {
        $vendors = Vendor::orderBy('code', 'asc')->get();
        $fileName = 'Data_Vendor_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($vendors) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['Company Code', 'Company Name', 'Official Name', 'Address', 'Postal Code', 'Phone', 'Fax', 'PIC Name', 'Email', 'Supplier', 'Customer', 'Status', 'Notes']);

            foreach ($vendors as $v) {
                fputcsv($file, [
                    $v->code,
                    $v->name,
                    $v->official_name ?: '-',
                    $v->address ?: '-',
                    $v->postal_code ?: '-',
                    $v->phone ?: '-',
                    $v->fax ?: '-',
                    $v->pic_name ?: '-',
                    $v->email ?: '-',
                    $v->is_supplier ? 'Yes' : 'No',
                    $v->is_customer ? 'Yes' : 'No',
                    $v->status ? 'Active' : 'Inactive',
                    $v->notes ?: '-',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
