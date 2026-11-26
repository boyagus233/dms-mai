<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\DocumentFormController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\DocumentFunctionController;
use App\Http\Controllers\DocumentClassificationController;
use App\Http\Controllers\DocumentLevelController;
use App\Http\Controllers\DocumentEksternalTypeController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\DepartementController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\KalibrasiController;
use App\Http\Controllers\DocumentUploadController;
use App\Http\Controllers\DocumentRequestController;
use App\Http\Controllers\DocumentAcknowledgeController;
use App\Http\Controllers\DocumentRequiredController;
use App\Http\Controllers\DocumentLogController;
use App\Http\Controllers\DocumentMasterListController;
use App\Http\Controllers\DocumentEksternalController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AdhocDocumentController;
use App\Http\Controllers\SuratJalanController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot.password');
Route::post('/process-forgot-password', [AuthController::class, 'sendResetLink'])
    ->name('password.email');

Route::fallback(function () {
    return view('content.404');
});

Route::group(['middleware' => 'auth'], function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'getData'])->name('dashboard.data');
    Route::get('/dashboard/datacalendar/{currentYear}/{month}', [DashboardController::class, 'getCalendarTransaction'])->name('dashboard.data-calendar');
});

Route::middleware('guest')->controller(AuthController::class)->group(function () {
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login')->name('login.perform');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('users', UserController::class);
    route::post('/users/{userId}/add-role/', [UserController::class, 'addRole'])->name('users.addRole');
    route::get('/users/{userId}/delete-role/{roleId}', [UserController::class, 'deleteRole'])->name('users.deleteRole');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('role', RoleController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::resource('document-type', DocumentTypeController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::resource('document-function', DocumentFunctionController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::resource('document-classification', DocumentClassificationController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::resource('document-level', DocumentLevelController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::resource('document-form', DocumentFormController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::resource('document-eksternal-type', DocumentEksternalTypeController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/document-template/index-download', [DocumentTemplateController::class, 'indexDownload'])->name('document-template.list-download');
    Route::get('/document-template/download/{id}', [DocumentTemplateController::class, 'download'])
        ->name('document-template.download');
    Route::resource('document-template', DocumentTemplateController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::delete('/document-upload/delete/{id}/{viewtype}', [DocumentUploadController::class, 'destroy'])
        ->name('document-upload.destroy');
    Route::resource('document-upload', DocumentUploadController::class)->except(['edit', 'destroy']);
    Route::get('document-upload/{documentUpload}/edit/{action}/{viewtype}', [DocumentUploadController::class, 'edit'])
        ->name('document-upload.edit');
    Route::get('document-upload/{documentUpload}/view/{viewtype}', [DocumentUploadController::class, 'view'])
        ->name('document-upload.view');
    Route::get('/document-upload', [DocumentUploadController::class, 'index'])->name('document-upload.index');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/document-master-list/export', [DocumentMasterListController::class, 'export'])->name('document-master-list.export');
    //Route::get('/document-master-list/export2', [DocumentMasterListController::class, 'export2'])->name('document-master-list.export2');
    Route::resource('document-master-list', DocumentMasterListController::class);
    Route::post('document-master-list/send-email', [DocumentMasterListController::class, 'sendEmail'])->name('document-master-list.email');
    Route::get('document-master-list/downloadfile/{id}/{downloadtype}', [DocumentMasterListController::class, 'downloadfile'])->name('document-master-list.download');
    Route::get('document-master-list/download-attachment/{id}', [DocumentMasterListController::class, 'downloadAttachment'])->name('document-master-list.downloadattachment');
    Route::get('document-master-list/download-obsolete/{id}', [DocumentMasterListController::class, 'downloadObsolete'])->name('document-master-list.downloadobsolete');
    Route::get('/document-master-list', [DocumentMasterListController::class, 'index'])->name('document-master-list.index');
    Route::get('document-master-list/{documentUpload}/view/{viewtype}', [DocumentMasterListController::class, 'view'])
        ->name('document-master-list.view');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/document-log/adhoc/export-all', [DocumentLogController::class, 'exportAllAdhoc'])->name('document-log.adhoc-export-all');
    Route::get('/document-log/adhoc/{id}/logs', [DocumentLogController::class, 'getAdhocLogs'])->name('document-log.adhoc-logs');
    Route::get('/document-log/{id}/logs', [DocumentLogController::class, 'getLogs']);
    Route::get('/document-log/{id}/export', [DocumentLogController::class, 'export'])->name('document-log.export');
    Route::resource('document-log', DocumentLogController::class)->except(['show']);
});

Route::middleware(['auth'])->group(function () {
    Route::resource('document-acknowledge', DocumentAcknowledgeController::class);
    route::post('/document-acknowledge/read-document', [DocumentAcknowledgeController::class, 'markAsRead'])->name('document-acknowledge.read-document');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('document-required', DocumentRequiredController::class);
    Route::get('/document-required/approve/{id}', [DocumentRequiredController::class, 'approve'])->name('document-required.approve');
    Route::get('/document-required/check/{id}', [DocumentRequiredController::class, 'check'])->name('document-required.check');
    route::post('/document-required/save-checked', [DocumentRequiredController::class, 'saveChecked'])->name('document-required.save-checked');
    route::post('/document-required/approve-document', [DocumentRequiredController::class, 'approveDocument'])->name('document-required.approve-document');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('departement', DepartementController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::resource('position', PositionController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::resource('employee', EmployeeController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('/vendor', '/master-vendor');
    Route::get('/master-vendor/export', [VendorController::class, 'export'])->name('vendor.export');
    Route::resource('master-vendor', VendorController::class)->names('vendor')->parameters(['master-vendor' => 'vendor']);
    Route::get('/vendors', [VendorController::class, 'index'])->name('vendor.alias');
});

Route::middleware(['auth'])->prefix('kalibrasi')->name('kalibrasi.')->group(function () {
    Route::get('/', [KalibrasiController::class, 'index'])->name('index');
    Route::get('/internal/{alatUkur}', [KalibrasiController::class, 'createInternal'])->name('create-internal');
    Route::post('/internal/{alatUkur}', [KalibrasiController::class, 'storeInternal'])->name('store-internal');
    Route::post('/eksternal/{alatUkur}/mark', [KalibrasiController::class, 'markEksternal'])->name('mark-eksternal');
    Route::get('/pending-return', [KalibrasiController::class, 'pendingReturn'])->name('pending-return');
    Route::get('/return/{alatUkur}', [KalibrasiController::class, 'returnForm'])->name('return-form');
    Route::post('/return/{alatUkur}', [KalibrasiController::class, 'storeReturn'])->name('store-return');
    Route::get('/approval-queue', [KalibrasiController::class, 'approvalQueue'])->name('approval-queue');
    Route::get('/{kalibrasi}/preview', [KalibrasiController::class, 'preview'])->name('preview');
    Route::post('/{kalibrasi}/approve', [KalibrasiController::class, 'approve'])->name('approve');
    Route::post('/{kalibrasi}/reject', [KalibrasiController::class, 'reject'])->name('reject');
    Route::get('/archive', [KalibrasiController::class, 'archive'])->name('archive');
    Route::get('/{kalibrasi}/pdf', [KalibrasiController::class, 'downloadPdf'])->name('download-pdf');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/document-request/data', [DocumentRequestController::class, 'getData'])->name('document-request.data');
    Route::resource('document-request', DocumentRequestController::class);
    Route::put('/document-request/{id}/terbitkannomor', [DocumentRequestController::class, 'terbitkannomor'])->name('document-request.terbitkannomor');
    Route::get('/getParentnumber/{dataclassification}', [DocumentRequestController::class, 'getParentnumber'])->name('document-upload.getParentnumber');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/document-eksternal/data', [DocumentEksternalController::class, 'getData'])->name('document-eksternal.data');
    Route::post('/document-eksternal/{id}', [DocumentEksternalController::class, 'update'])->name('document-eksternal.update');
    Route::get('/document-eksternal/download/{id}', [DocumentEksternalController::class, 'download'])
        ->name('document-eksternal.download');
    Route::resource('document-eksternal', DocumentEksternalController::class)->except(['update']);
    //Route::get('/document-eksternal-list', [DocumentEksternalController::class, 'index'])->name('document-eksternal-list.index');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/search', [SearchController::class, 'search'])->name('search.data');
});

// Surat Jalan (Digitalisasi Form Barang Keluar + Online Sign Approval)
Route::middleware(['auth'])->prefix('surat-jalan')->name('surat-jalan.')->controller(SuratJalanController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/data', 'data')->name('data');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{suratJalan}', 'show')->name('show');
    Route::get('/{suratJalan}/edit', 'edit')->name('edit');
    Route::put('/{suratJalan}', 'update')->name('update');
    Route::delete('/{suratJalan}', 'destroy')->name('destroy');
    Route::post('/{suratJalan}/submit', 'submit')->name('submit');
    Route::post('/{suratJalan}/approve', 'approve')->name('approve');
    Route::post('/{suratJalan}/reject', 'reject')->name('reject');
    Route::get('/{suratJalan}/pdf', 'pdf')->name('pdf');
    Route::get('/{suratJalan}/audit-history/export', 'auditHistory')->name('audit-history');
    Route::get('/{suratJalan}/stamp/{step}', 'stamp')->whereNumber('step')->name('stamp');
});

Route::get('/speedtest', function () {
    return "OK";
});

Route::get('/speedtest-view', function () {
    return view('your-view', [
        'data' => []
    ]);
});

Route::get('/debug-documents', function () {
    try {
        $documents = DB::table('trx_document_upload')
            ->select('id', 'document_name', 'file_name', 'file_attachment')
            ->limit(999)
            ->get();

        return response()->json([
            'status' => 'success',
            'total' => $documents->count(),
            'documents' => $documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'document_name' => $doc->document_name,
                    'file_name' => $doc->file_name,
                    'file_attachment' => $doc->file_attachment,
                ];
            }),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/show-pdf/{id}', function ($id) {
    try {
        $doc = DB::table('trx_document_upload')->where('id', $id)->first();

        if (!$doc) {
            return response()->json(['error' => 'Dokumen tidak ditemukan'], 404);
        }

        // Coba cari file
        $fileNames = [$doc->file_name, $doc->file_attachment];
        $paths = [];

        foreach ($fileNames as $fileName) {
            if (empty($fileName)) continue;

            $possiblePaths = [
                storage_path('app/public/documents/' . $fileName),
                storage_path('app/public/pdfs/' . $fileName),
                storage_path('app/public/' . $fileName),
                public_path('storage/documents/' . $fileName),
                public_path('storage/pdfs/' . $fileName),
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path) && is_readable($path)) {
                    return response()->file($path, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline',
                        'Access-Control-Allow-Origin' => '*',
                    ]);
                }
                $paths[] = $path;
            }
        }

        return response()->json([
            'error' => 'File tidak ditemukan',
            'doc' => [
                'id' => $doc->id,
                'document_name' => $doc->document_name,
                'file_name' => $doc->file_name,
                'file_attachment' => $doc->file_attachment,
            ],
            'searched_paths' => $paths,
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/check-storage', function () {
    $publicStoragePath = public_path('storage');
    $realStoragePath = storage_path('app/public');

    $status = [
        'public_path' => $publicStoragePath,
        'real_target' => $realStoragePath,
    ];

    // 1. Cek apakah folder public/storage fisik ada
    if (File::exists($publicStoragePath)) {
        $status['exists'] = true;

        // 2. Trik Windows/Linux: Baca tujuan akhir sebenarnya dari folder tersebut
        $resolvedPublicPath = realpath($publicStoragePath);
        $resolvedRealTarget = realpath($realStoragePath);

        // Jika tujuan akhir public/storage SAMA PERSIS dengan storage/app/public, berarti shortcut bekerja!
        if ($resolvedPublicPath === $resolvedRealTarget) {
            $status['is_symlink'] = true;
            $status['message'] = '✅ AMAN: public/storage terhubung sempurna ke storage/app/public (Support Windows Junction & Linux Symlink).';
            $status['points_to'] = $resolvedPublicPath;
        } else {
            // is_link() tetap dipakai sebagai cadangan jika realpath beda tipis karena format path OS
            if (is_link($publicStoragePath)) {
                $status['is_symlink'] = true;
                $status['message'] = '✅ AMAN: Dideteksi sebagai Symlink murni.';
            } else {
                $status['is_symlink'] = false;
                $status['message'] = '❌ ERROR: public/storage ada, TAPI berupa folder biasa. Hapus public/storage lalu jalankan ulang perintah symlink/mklink.';
                $status['points_to_tested'] = $resolvedPublicPath;
            }
        }
    } else {
        $status['exists'] = false;
        $status['is_symlink'] = false;
        $status['message'] = '❌ ERROR: public/storage TIDAK DITEMUKAN. Anda belum membuat symlink.';
    }

    return response()->json($status);
});

// Ad-Hoc / All Document Approval (Custom E-Signature) Routes
Route::middleware(['auth'])->prefix('adhoc-document')->name('adhoc-document.')->group(function () {
    Route::get('/', [AdhocDocumentController::class, 'index'])->name('index');
    Route::get('/create', [AdhocDocumentController::class, 'create'])->name('create');
    Route::post('/', [AdhocDocumentController::class, 'store'])->name('store');
    Route::get('/{uuid}', [AdhocDocumentController::class, 'show'])->name('show');
    Route::get('/{uuid}/tagging', [AdhocDocumentController::class, 'tagging'])->name('tagging');
    Route::post('/{uuid}/tagging', [AdhocDocumentController::class, 'saveTagging'])->name('save-tagging');
    Route::get('/{uuid}/review', [AdhocDocumentController::class, 'review'])->name('review');
    Route::post('/{uuid}/sign', [AdhocDocumentController::class, 'sign'])->name('sign');
    Route::post('/{uuid}/reject', [AdhocDocumentController::class, 'reject'])->name('reject');
    Route::get('/{uuid}/download-signed', [AdhocDocumentController::class, 'downloadSigned'])->name('download-signed');
    Route::get('/{uuid}/download-with-audit', [AdhocDocumentController::class, 'downloadWithAudit'])->name('download-with-audit');
    Route::get('/{uuid}/download-audit-history', [AdhocDocumentController::class, 'downloadAuditHistory'])->name('download-audit-history');
    Route::get('/{uuid}/export-audit-csv', [AdhocDocumentController::class, 'exportAuditCsv'])->name('export-audit-csv');
    Route::get('/{uuid}/signature/{signerId}', [AdhocDocumentController::class, 'signatureImage'])->name('signature-image');
    Route::get('/{uuid}/view-pdf', [AdhocDocumentController::class, 'viewPdf'])->name('view-pdf');
});

// Deep link alias as specified in PRD
Route::middleware(['auth'])->get('/documents/{uuid}/review', function ($uuid) {
    return redirect()->route('adhoc-document.review', ['uuid' => $uuid]);
});
Route::middleware(['auth'])->get('/documents/{uuid}/audit-history/export', function ($uuid) {
    return redirect()->route('adhoc-document.download-audit-history', ['uuid' => $uuid]);
});
