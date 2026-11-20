<?php
/**
 * DMS STANDALONE MAINTENANCE PORTAL
 * Place in: public/maintenance_portal.php
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\DocumentClassification;
use App\Models\DocumentFunction;
use App\Models\User;
use App\Models\Employee;

// 1. BOOTSTRAP LARAVEL FOR DB & ELOQUENT ACCESS
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// We only need the console kernel to initialize Laravel environment (DB, Config, Eloquent)
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 2. STANDALONE LOGIN SYSTEM (No Laravel Session)
session_start();

// Password statis untuk portal maintenance (Bisa diubah jika perlu)
$MAINTENANCE_PASSWORD = "admin";

// Proses Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: maintenance_portal.php");
    exit;
}

// Proses Login
$authError = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maintenance_login'])) {
    if ($_POST['password'] === $MAINTENANCE_PASSWORD) {
        $_SESSION['maintenance_logged_in'] = true;
        header("Location: maintenance_portal.php");
        exit;
    } else {
        $authError = "Password salah!";
    }
}

// Cek status login
if (!isset($_SESSION['maintenance_logged_in']) || $_SESSION['maintenance_logged_in'] !== true) {
    // TAMPILKAN FORM LOGIN
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Maintenance Login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: #f4f7f6; display: flex; align-items: center; justify-content: center; height: 100vh; }
            .login-card { width: 100%; max-width: 400px; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); background: white;}
        </style>
    </head>
    <body>
        <div class="login-card">
            <h4 class="text-center mb-4 text-primary">Maintenance Portal</h4>
            <?php if($authError): ?><div class="alert alert-danger py-2"><?php echo $authError; ?></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="maintenance_login" value="1">
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            <div class="text-center mt-3"><a href="/login" class="text-muted small">Back to Main App</a></div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$message = "";
$error = "";

// 3. ACTION HANDLER (POST REQUESTS)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- ACTION: BULK ACKNOWLEDGE ---
    if ($action === 'bulk_acknowledge') {
        try {
            $pendings = DB::select("
                SELECT tdu.id as document_id, tdu.document_no, tdu.document_name,
                       me.id as employee_id, me.name as employee_name,
                       me.departement as departement_id, md.name as departement_name,
                       usr.id as user_id
                FROM trx_document_upload tdu
                JOIN m_departement md ON FIND_IN_SET(md.id, tdu.department_ids)
                JOIN m_employee me ON md.id = me.departement
                JOIN users usr ON me.id = usr.employee_id
                LEFT JOIN tr_audit_trails tat ON (me.id = tat.employee_id AND tdu.id = tat.document_id AND tat.action = 'MARK_AS_READ')
                WHERE tdu.document_status = 'APPROVED'
                AND tat.id IS NULL
                AND me.name NOT IN (tdu.creator_name, tdu.checker_name, tdu.approver_name)
                LIMIT 5000
            ");

            $count = 0;
            $now = now();
            $inserts = [];
            foreach ($pendings as $p) {
                $inserts[] = [
                    'document_id' => $p->document_id,
                    'document_no' => $p->document_no,
                    'document_name' => $p->document_name,
                    'action' => 'MARK_AS_READ',
                    'employee_id' => $p->employee_id,
                    'employee_name' => $p->employee_name,
                    'departement_id' => $p->departement_id,
                    'departement_name' => $p->departement_name,
                    'user_id' => $p->user_id,
                    'notes' => 'System Bulk Acknowledge (Maintenance Portal)',
                    'created_at' => $now,
                    'updated_at' => $now
                ];
                $count++;
            }
            if(!empty($inserts)) {
                DB::table('tr_audit_trails')->insert($inserts);
            }
            $message = "Berhasil memproses Acknowledge massal untuk $count data.";
        } catch (\Exception $e) {
            $error = "Gagal Bulk Acknowledge: " . $e->getMessage();
        }
    }

    // --- ACTION: UPLOAD MISSING FILE ---
    if ($action === 'upload_missing_file') {
        try {
            $docType = $_POST['doc_type']; // 'main' or 'eksternal'
            $docNo = $_POST['doc_no'];
            $fileDb = $_POST['file_db'];

            if (isset($_FILES['pdf_upload']) && $_FILES['pdf_upload']['error'] === UPLOAD_ERR_OK) {
                $tmpPath = $_FILES['pdf_upload']['tmp_name'];
                $folder = $docType === 'main' ? 'documents/' : 'eksternal-document/';

                // Pindahkan file dari temp ke storage dengan nama yang sesuai di DB
                $destination = storage_path('app/public/' . $folder . $fileDb);
                if (move_uploaded_file($tmpPath, $destination)) {
                    $message = "File untuk dokumen $docNo ($fileDb) berhasil diunggah dan disinkronkan dengan Database.";
                } else {
                    $error = "Gagal memindahkan file ke direktori storage.";
                }
            } else {
                $error = "File tidak valid atau terjadi kesalahan saat upload.";
            }
        } catch (\Exception $e) {
            $error = "Gagal mengunggah file: " . $e->getMessage();
        }
    }

    // --- ACTION: DOCUMENT ADJUSTMENT (INTERNAL) ---
    if ($action === 'document_adjustment_internal') {
        try {
            $docId = $_POST['document_id'];

            // Resolve Names from IDs
            $deptName = '';
            if(isset($_POST['departement_id']) && $_POST['departement_id']) {
                $dept = DB::table('m_departement')->where('id', $_POST['departement_id'])->first();
                $deptName = $dept ? $dept->name : '';
            }

            $creatorName = '';
            if(isset($_POST['creator_id']) && $_POST['creator_id']) {
                $emp = DB::table('m_employee')->where('id', $_POST['creator_id'])->first();
                $creatorName = $emp ? $emp->name : '';
            }

            $checkerName = '';
            if(isset($_POST['checker_id']) && $_POST['checker_id']) {
                $emp = DB::table('m_employee')->where('id', $_POST['checker_id'])->first();
                $checkerName = $emp ? $emp->name : '';
            }

            $approverName = '';
            if(isset($_POST['approver_id']) && $_POST['approver_id']) {
                $emp = DB::table('m_employee')->where('id', $_POST['approver_id'])->first();
                $approverName = $emp ? $emp->name : '';
            }

            // Handle Multiple Department IDs array
            $deptIdsString = '';
            if(isset($_POST['department_ids']) && is_array($_POST['department_ids'])) {
                $deptIdsString = implode(',', $_POST['department_ids']);
            }

            $updateData = [];

            // Hanya update jika key benar-benar ada di $_POST
            if(array_key_exists('document_type_code', $_POST)) $updateData['document_type'] = $_POST['document_type_code'] !== '' ? $_POST['document_type_code'] : null;
            if(array_key_exists('document_classification_code', $_POST)) $updateData['document_classification'] = $_POST['document_classification_code'] !== '' ? $_POST['document_classification_code'] : null;
            if(array_key_exists('document_function_code', $_POST)) $updateData['document_function'] = $_POST['document_function_code'] !== '' ? $_POST['document_function_code'] : null;
            if(array_key_exists('document_name', $_POST)) $updateData['document_name'] = $_POST['document_name'] !== '' ? $_POST['document_name'] : null;
            if(array_key_exists('document_no', $_POST)) $updateData['document_no'] = $_POST['document_no'] !== '' ? $_POST['document_no'] : null;

            if(array_key_exists('departement_id', $_POST)) {
                $updateData['departement'] = $_POST['departement_id'] !== '' ? $_POST['departement_id'] : null;
                $updateData['departement_name'] = $deptName !== '' ? $deptName : null;
            }
            if(array_key_exists('department_ids', $_POST)) {
                $updateData['department_ids'] = $deptIdsString !== '' ? $deptIdsString : null;
            }

            if(array_key_exists('checker_id', $_POST)) {
                $updateData['checker'] = $_POST['checker_id'] !== '' ? $_POST['checker_id'] : null;
                $updateData['checker_name'] = $checkerName !== '' ? $checkerName : null;
            }
            if(array_key_exists('approver_id', $_POST)) {
                $updateData['approver'] = $_POST['approver_id'] !== '' ? $_POST['approver_id'] : null;
                $updateData['approver_name'] = $approverName !== '' ? $approverName : null;
            }
            if(array_key_exists('creator_id', $_POST)) {
                $updateData['created_by'] = $_POST['creator_id'] !== '' ? $_POST['creator_id'] : null;
                $updateData['creator_name'] = $creatorName !== '' ? $creatorName : null;
            }

            if(array_key_exists('rev_no', $_POST)) $updateData['rev_no'] = (int) $_POST['rev_no'];
            if(array_key_exists('reason_revisi', $_POST)) $updateData['reason_revisi'] = $_POST['reason_revisi'] !== '' ? $_POST['reason_revisi'] : null;
            if(array_key_exists('file_name', $_POST)) $updateData['file_name'] = $_POST['file_name'] !== '' ? $_POST['file_name'] : null;
            if(array_key_exists('file_attachment', $_POST)) $updateData['file_attachment'] = $_POST['file_attachment'] !== '' ? $_POST['file_attachment'] : null;
            if(array_key_exists('document_status', $_POST)) $updateData['document_status'] = $_POST['document_status'] !== '' ? $_POST['document_status'] : 'CREATED';

            // Bit Flags (Karena ini checkbox, jika tidak dicentang dia tidak terkirim, jadi kita asumsikan 0 jika tidak ada)
            $updateData['is_stamp_by_creator'] = isset($_POST['is_stamp_by_creator']) ? 1 : 0;
            $updateData['is_rejected_by_dc'] = isset($_POST['is_rejected_by_dc']) ? 1 : 0;
            $updateData['is_checked_by_dc'] = isset($_POST['is_checked_by_dc']) ? 1 : 0;
            $updateData['is_rejected_by_checker'] = isset($_POST['is_rejected_by_checker']) ? 1 : 0;
            $updateData['is_checked'] = isset($_POST['is_checked']) ? 1 : 0;
            $updateData['is_rejected_by_appr'] = isset($_POST['is_rejected_by_appr']) ? 1 : 0;
            $updateData['is_approved'] = isset($_POST['is_approved']) ? 1 : 0;
            $updateData['is_revise'] = isset($_POST['is_revise']) ? 1 : 0;
            $updateData['is_obsolete'] = isset($_POST['is_obsolete']) ? 1 : 0;
            $updateData['is_processing_revise_obsolete'] = isset($_POST['is_processing_revise_obsolete']) ? 1 : 0;

            // Dates
            if(array_key_exists('obsolete_date', $_POST)) $updateData['obsolete_date'] = !empty($_POST['obsolete_date']) ? $_POST['obsolete_date'] : null;
            if(array_key_exists('created_at', $_POST)) $updateData['created_at'] = !empty($_POST['created_at']) ? $_POST['created_at'] : null;

            $updateData['updated_at'] = now();

            if(!empty($updateData)) {
                DB::table('trx_document_upload')->where('id', $docId)->update($updateData);
            }
            $message = "Internal Document (ID: $docId) adjustment berhasil disimpan langsung ke database.";
        } catch (\Exception $e) {
            $error = "Gagal melakukan adjustment Internal Document: " . $e->getMessage();
        }
    }

    // --- ACTION: DOCUMENT ADJUSTMENT (EXTERNAL) ---
    if ($action === 'document_adjustment_external') {
        try {
            $docId = $_POST['document_id'];
            $updateData = [];

            if(array_key_exists('document_eksternal_type', $_POST)) $updateData['document_eksternal_type'] = $_POST['document_eksternal_type'] !== '' ? $_POST['document_eksternal_type'] : null;
            if(array_key_exists('document_no', $_POST)) $updateData['document_no'] = $_POST['document_no'] !== '' ? $_POST['document_no'] : null;
            if(array_key_exists('document_name', $_POST)) $updateData['document_name'] = $_POST['document_name'] !== '' ? $_POST['document_name'] : null;
            if(array_key_exists('revisi', $_POST)) $updateData['revisi'] = $_POST['revisi'] !== '' ? $_POST['revisi'] : null;
            if(array_key_exists('status', $_POST)) $updateData['status'] = $_POST['status'] !== '' ? $_POST['status'] : null;
            if(array_key_exists('file_name', $_POST)) $updateData['file_name'] = $_POST['file_name'] !== '' ? $_POST['file_name'] : null;
            if(array_key_exists('created_at', $_POST)) $updateData['created_at'] = !empty($_POST['created_at']) ? $_POST['created_at'] : null;

            $updateData['updated_at'] = now();

            if(!empty($updateData)) {
                DB::table('trx_document_eksternal')->where('id', $docId)->update($updateData);
            }
            $message = "Eksternal Document (ID: $docId) adjustment berhasil disimpan langsung ke database.";
        } catch (\Exception $e) {
            $error = "Gagal melakukan adjustment Eksternal Document: " . $e->getMessage();
        }
    }

    // --- ACTION: FORCE SEQUENCE ---
    if ($action === 'force_sequence') {
        try {
            $typeId = $_POST['document_type'];
            $classId = $_POST['document_classification'];
            $funcId = $_POST['document_function'] ?? null;
            $targetLastNumber = (int) $_POST['last_number'];

            $type = DocumentType::find($typeId);
            $class = DocumentClassification::find($classId);
            $funcCode = '';
            if($funcId) {
                $func = DocumentFunction::find($funcId);
                $funcCode = $func ? $func->code : '';
            }

            // Hitung dokumen request yang sudah ada untuk kategori ini
            $query = DocumentRequest::where('document_type', $typeId)
                ->where('document_classification', $classId);
            if($funcId) {
                $query->where('document_function', $funcId);
            }
            $currentCount = $query->count();

            if ($targetLastNumber > $currentCount) {
                $needed = $targetLastNumber - $currentCount;
                $inserts = [];
                for ($i = 1; $i <= $needed; $i++) {
                    $inserts[] = [
                        'document_type' => $typeId,
                        'document_type_code' => $type->code,
                        'document_classification' => $classId,
                        'document_classification_code' => $class->code,
                        'document_function' => $funcId,
                        'document_function_code' => $funcCode,
                        'document_no' => "DUMMY-" . $type->code . "-" . $class->code . "-" . uniqid(),
                        'document_name' => 'SYSTEM DUMMY SEQUENCE FILLER',
                        'document_status' => 'USED',
                        'document_parent_no' => 'DUMMY',
                        'document_form' => 'DUMMY',
                        'notes' => 'Generated by Maintenance Portal to force sequence to ' . $targetLastNumber,
                        'is_used' => 1,
                        'status' => 0, // 0 = tidak aktif / hidden
                        'created_by' => 1, // Defaulting to Admin ID 1 since we bypass Laravel Auth
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                DB::table('trx_document_request')->insert($inserts);
                $message = "Berhasil menyuntikkan $needed baris dummy. Nomor dokumen yang diregistrasi berikutnya akan dimulai dari " . ($targetLastNumber + 1);
            } else {
                $error = "Hitungan saat ini di database ($currentCount) sudah sama atau lebih besar dari target Anda ($targetLastNumber). Cut-off tidak diperlukan.";
            }
        } catch (\Exception $e) {
            $error = "Gagal memproses Sequence: " . $e->getMessage();
        }
    }

    // --- ACTION: SYSTEM TOOLS ---
    if ($action === 'fix_storage_link') {
        try {
            $publicStoragePath = __DIR__ . '/storage';
            $realStoragePath = realpath(__DIR__ . '/../storage/app/public');

            // Delete existing link/folder if exists to recreate properly
            if (file_exists($publicStoragePath) || is_link($publicStoragePath)) {
                // Determine if Windows or Linux to remove properly
                if (PHP_OS_FAMILY === 'Windows') {
                    exec('rmdir "' . $publicStoragePath . '"'); // Windows rmdir removes junctions
                } else {
                    unlink($publicStoragePath); // Linux unlink removes symlinks
                }
            }

            // Create new symlink
            if (symlink($realStoragePath, $publicStoragePath)) {
                $message = "Berhasil membuat ulang Storage Symlink!";
            } else {
                // Fallback for Windows if normal symlink fails (often needs admin rights)
                if (PHP_OS_FAMILY === 'Windows') {
                    $cmd = 'mklink /J "' . $publicStoragePath . '" "' . $realStoragePath . '"';
                    exec($cmd, $output, $returnVar);
                    if ($returnVar === 0) {
                        $message = "Berhasil membuat Storage Link menggunakan Windows Junction.";
                    } else {
                        $error = "Gagal membuat Windows Junction. Pastikan server/Laragon punya akses Administrator.";
                    }
                } else {
                    $error = "Gagal membuat symlink. Periksa permission folder Anda.";
                }
            }
        } catch (\Exception $e) {
            $error = "Error saat mencoba membuat Storage Link: " . $e->getMessage();
        }
    }

    if ($action === 'clear_cache') {
        try {
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            $message = "Application, Config, dan View Cache berhasil dibersihkan.";
        } catch (\Exception $e) {
            $error = "Gagal membersihkan cache: " . $e->getMessage();
        }
    }
}

// 4. PREPARE VIEW DATA
$menu = $_GET['menu'] ?? 'acknowledge';

// Get counts for badges
$pendingAcknowledgeCount = DB::selectOne("
    SELECT COUNT(*) as total
    FROM trx_document_upload tdu
    JOIN m_departement md ON FIND_IN_SET(md.id, tdu.department_ids)
    JOIN m_employee me ON md.id = me.departement
    JOIN users usr ON me.id = usr.employee_id
    LEFT JOIN tr_audit_trails tat ON (me.id = tat.employee_id AND tdu.id = tat.document_id AND tat.action = 'MARK_AS_READ')
    WHERE tdu.document_status = 'APPROVED'
    AND tat.id IS NULL
    AND me.name NOT IN (tdu.creator_name, tdu.checker_name, tdu.approver_name)
")->total;

$documentTypes = DocumentType::where('status', 1)->get();
$classifications = DocumentClassification::where('status', 1)->get();
$functions = DocumentFunction::where('status', 1)->get();

// Tambahan untuk Dropdown Modal Adjustment Internal
$allDepartments = DB::table('m_departement')->select('id', 'code', 'name')->where('status', 1)->get();
$allEmployees = DB::table('m_employee')->select('id', 'nik', 'name')->get();

// Tambahan untuk Dropdown Modal Adjustment Eksternal
$eksternalTypes = DB::table('m_document_eksternal_type')->select('code', 'name')->get();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMS Maintenance Portal</title>
    <!-- Use Bootstrap CSS from CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: #343a40; color: white; padding-top: 20px;}
        .sidebar a { color: #adb5bd; text-decoration: none; display: block; padding: 10px 20px; font-weight: 500;}
        .sidebar a:hover, .sidebar a.active { background: #495057; color: white; border-left: 4px solid #0d6efd;}
        .content-area { padding: 30px; }
        .card { box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); border: none; border-radius: 8px; margin-bottom:20px;}
        .card-header { background-color: white; border-bottom: 1px solid #edf2f9; font-weight: bold; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-md-2 sidebar">
            <h5 class="text-center mb-4"><i class="fa-solid fa-screwdriver-wrench text-warning"></i> DMS Maintenance</h5>
            <a href="?menu=acknowledge" class="<?= $menu == 'acknowledge' ? 'active' : '' ?>">
                <i class="fa-solid fa-envelope-open-text me-2"></i> Acknowledge Cleanup
                <?php if($pendingAcknowledgeCount > 0): ?>
                    <span class="badge bg-danger float-end"><?php echo $pendingAcknowledgeCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="?menu=sequence" class="<?= $menu == 'sequence' ? 'active' : '' ?>">
                <i class="fa-solid fa-arrow-up-9-1 me-2"></i> Numbering Cut-off
            </a>
            <a href="?menu=health_number" class="<?= $menu == 'health_number' ? 'active' : '' ?>">
                <i class="fa-solid fa-magnifying-glass-chart me-2"></i> Numbering Health
            </a>
            <a href="?menu=adjustment" class="<?= $menu == 'adjustment' ? 'active' : '' ?>">
                <i class="fa-solid fa-sliders me-2"></i> Document Adjustment
            </a>
            <a href="?menu=health_user" class="<?= $menu == 'health_user' ? 'active' : '' ?>">
                <i class="fa-solid fa-users-viewfinder me-2"></i> User Access & Health
            </a>
            <a href="?menu=health_file" class="<?= $menu == 'health_file' ? 'active' : '' ?>">
                <i class="fa-solid fa-file-circle-xmark me-2"></i> Missing Files Check
            </a>
            <a href="?menu=sys_tools" class="<?= $menu == 'sys_tools' ? 'active' : '' ?>">
                <i class="fa-solid fa-toolbox me-2"></i> System Tools (Cache & Link)
            </a>
            <hr class="bg-secondary">
            <a href="/dashboard" class="text-info"><i class="fa-solid fa-arrow-left me-2"></i> Back to Main App</a>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-md-10 content-area">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Maintenance Tools</h2>
                <div>
                    <span class="text-muted me-3"><i class="fa-solid fa-shield-halved"></i> Standalone Mode</span>
                    <a href="?logout=1" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-power-off"></i> Logout</a>
                </div>
            </div>

            <?php if($message): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="fa-solid fa-circle-check me-2"></i><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- MENU 1: ACKNOWLEDGE -->
            <?php if($menu == 'acknowledge'): ?>
                <div class="card">
                    <div class="card-header bg-primary text-white">Bulk Acknowledge (Clear Notifications)</div>
                    <div class="card-body">
                        <p>Fungsi ini akan secara massal menandai dokumen sebagai "Read" untuk karyawan yang di-*tag* namun belum membacanya. Ini akan membersihkan angka notifikasi merah di sidebar aplikasi.</p>
                        <h4 class="text-danger">Total Unread Documents: <?php echo $pendingAcknowledgeCount; ?></h4>

                        <?php if($pendingAcknowledgeCount > 0): ?>
                        <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memproses data Acknowledge? Proses ini akan mencatat aktivitas di Audit Trail.');">
                            <input type="hidden" name="action" value="bulk_acknowledge">
                            <button type="submit" class="btn btn-warning mt-3"><i class="fa-solid fa-broom"></i> Proses Mark All as Read</button>
                            <span class="text-muted ms-2">(Maksimal 5000 data per eksekusi untuk mencegah timeout)</span>
                        </form>
                        <?php else: ?>
                            <div class="alert alert-success mt-3">Semua dokumen aktif telah di-acknowledge oleh karyawan terkait. Tidak ada aksi yang diperlukan.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- MENU 2: SEQUENCE -->
            <?php if($menu == 'sequence'): ?>
                <div class="card">
                    <div class="card-header bg-success text-white">Numbering Sequence Cut-off</div>
                    <div class="card-body">
                        <p>Gunakan menu ini jika nomor registrasi dokumen saat ini tidak sesuai dengan kondisi arsip lapangan. Sistem akan memasukkan dokumen <i>dummy</i> tersembunyi agar konter registrasi selanjutnya sesuai dengan target Anda.</p>
                        <hr>
                        <form method="POST" class="row g-3" onsubmit="return confirm('Sistem akan menyuntikkan baris dummy ke database. Tindakan ini sulit dibatalkan. Lanjutkan?');">
                            <input type="hidden" name="action" value="force_sequence">
                            <div class="col-md-4">
                                <label class="form-label">Tipe Dokumen (Type)</label>
                                <select name="document_type" class="form-select" required>
                                    <option value="">-- Pilih Tipe --</option>
                                    <?php foreach($documentTypes as $t): ?>
                                        <option value="<?php echo $t->id; ?>"><?php echo $t->code; ?> - <?php echo $t->document_type; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Klasifikasi (Classification)</label>
                                <select name="document_classification" class="form-select" required>
                                    <option value="">-- Pilih Klasifikasi --</option>
                                    <?php foreach($classifications as $c): ?>
                                        <option value="<?php echo $c->id; ?>"><?php echo $c->code; ?> - <?php echo $c->name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fungsi (Function) - Opsional utk WI</label>
                                <select name="document_function" class="form-select">
                                    <option value="">-- Kosongkan jika bukan WI/STD --</option>
                                    <?php foreach($functions as $f): ?>
                                        <option value="<?php echo $f->id; ?>"><?php echo $f->code; ?> - <?php echo $f->name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mt-4">
                                <label class="form-label text-primary fw-bold">Target Nomor Terakhir Saat Ini</label>
                                <input type="number" name="last_number" class="form-control border-primary" placeholder="Contoh: 50" required min="1">
                                <small class="text-muted">Jika diisi 50, maka registrasi dokumen oleh user berikutnya akan mendapat nomor urut 51.</small>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-syringe"></i> Inject / Cut-off Sequence</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- MENU 2B: HEALTH CHECK NUMBERING -->
            <?php if($menu == 'health_number'): ?>
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">Numbering Gap & Consistency Check</div>
                    <div class="card-body">
                        <p>Sistem ini memindai seluruh nomor dokumen aktif di tabel <code>trx_document_upload</code> untuk mencari nomor urut yang melompat (hilang) di tengah jalan berdasarkan pola Prefix.</p>

                        <?php
                            $allDocs = DB::table('trx_document_upload')
                                        ->select('document_no', 'document_type', 'document_classification')
                                        ->where('document_status', 'APPROVED')
                                        ->where('is_obsolete', 0)
                                        ->get();

                            $groups = [];
                            $invalidFormat = [];

                            // 1. Parsing & Grouping Document Numbers
                            foreach($allDocs as $d) {
                                $no = trim($d->document_no);

                                // Deteksi format F (Form): e.g. P-M01-02/F1
                                if (strpos($no, '/F') !== false) {
                                    $parts = explode('/F', $no);
                                    $prefix = $parts[0] . '/F'; // Prefix: P-M01-02/F
                                    $seqStr = $parts[1] ?? '';
                                }
                                // Format Standard (P-M01-01 atau WI-M01-01-01)
                                else {
                                    $parts = explode('-', $no);
                                    $lastPart = end($parts);

                                    if(is_numeric($lastPart)) {
                                        array_pop($parts); // Hapus angka terakhir
                                        $prefix = implode('-', $parts); // Dapatkan prefixnya saja
                                        $seqStr = $lastPart;
                                    } else {
                                        $invalidFormat[] = $no;
                                        continue;
                                    }
                                }

                                if(is_numeric($seqStr)) {
                                    $seq = (int) $seqStr;
                                    if(!isset($groups[$prefix])) {
                                        $groups[$prefix] = [];
                                    }
                                    $groups[$prefix][] = $seq;
                                } else {
                                    $invalidFormat[] = $no;
                                }
                            }

                            // 2. Analisa Gap per Grup Prefix
                            $gapReports = [];
                            foreach($groups as $prefix => $sequences) {
                                sort($sequences);
                                $sequences = array_unique($sequences);
                                $maxSeq = end($sequences);
                                $minSeq = 1; // Selalu ekspektasi dimulai dari 1

                                $missing = [];
                                for($i = $minSeq; $i <= $maxSeq; $i++) {
                                    if(!in_array($i, $sequences)) {
                                        // Cari apakah nomor yg hilang ini ada di trx_document_request (sedang di booking)
                                        $expectedFullNo = $prefix === '' ? '' : (strpos($prefix, '/F') !== false ? $prefix . $i : $prefix . '-' . str_pad($i, 2, '0', STR_PAD_LEFT));

                                        $isBooked = DB::table('trx_document_request')->where('document_no', $expectedFullNo)->exists();

                                        $missing[] = [
                                            'no' => $i,
                                            'expected_full_no' => $expectedFullNo,
                                            'status' => $isBooked ? '<span class="badge bg-info">Currently Booked/Draft</span>' : '<span class="badge bg-danger">Missing/Gap</span>'
                                        ];
                                    }
                                }

                                if(count($missing) > 0) {
                                    $gapReports[$prefix] = [
                                        'existing' => $sequences,
                                        'missing' => $missing
                                    ];
                                }
                            }
                        ?>

                        <ul class="nav nav-tabs mb-3" id="numTab" role="tablist">
                          <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="gap-tab" data-bs-toggle="tab" data-bs-target="#tab-gap" type="button" role="tab">Gap Analysis <span class="badge bg-secondary"><?php echo count($gapReports); ?> Prefix Groups</span></button>
                          </li>
                          <li class="nav-item" role="presentation">
                            <button class="nav-link" id="format-tab" data-bs-toggle="tab" data-bs-target="#tab-format" type="button" role="tab">Invalid Format <span class="badge bg-secondary"><?php echo count($invalidFormat); ?> Issues</span></button>
                          </li>
                        </ul>

                        <div class="tab-content" id="numTabContent">
                            <div class="tab-pane fade show active" id="tab-gap" role="tabpanel">
                                <?php if(count($gapReports) > 0): ?>
                                    <div class="accordion" id="gapAccordion">
                                        <?php $i=0; foreach($gapReports as $prefix => $data): $i++; ?>
                                          <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading<?php echo $i; ?>">
                                              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $i; ?>" aria-expanded="false" aria-controls="collapse<?php echo $i; ?>">
                                                <strong>Prefix: <?php echo $prefix; ?></strong> &nbsp; - Found <?php echo count($data['missing']); ?> missing numbers
                                              </button>
                                            </h2>
                                            <div id="collapse<?php echo $i; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $i; ?>" data-bs-parent="#gapAccordion">
                                              <div class="accordion-body">
                                                <p class="small text-muted mb-2">Existing Sequences: <?php echo implode(', ', $data['existing']); ?></p>
                                                <table class="table table-sm table-bordered w-75">
                                                    <thead class="table-light"><tr><th>Missing Sequence No</th><th>Expected Document No</th><th>System Status</th></tr></thead>
                                                    <tbody>
                                                        <?php foreach($data['missing'] as $m): ?>
                                                        <tr>
                                                            <td><?php echo $m['no']; ?></td>
                                                            <td class="fw-bold"><?php echo $m['expected_full_no']; ?></td>
                                                            <td><?php echo $m['status']; ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                              </div>
                                            </div>
                                          </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-success mt-3"><i class="fa-solid fa-check"></i> Hebat! Tidak ditemukan nomor urut yang melompat pada seluruh kelompok Prefix dokumen Anda.</div>
                                <?php endif; ?>
                            </div>

                            <div class="tab-pane fade" id="tab-format" role="tabpanel">
                                <p class="text-muted">Dokumen-dokumen berikut memiliki pola penomoran yang tidak standar (tidak diakhiri dengan angka / format salah) sehingga tidak dapat dihitung sekuens-nya oleh sistem:</p>
                                <?php if(count($invalidFormat) > 0): ?>
                                    <ul class="list-group w-75">
                                        <?php foreach($invalidFormat as $inv): ?>
                                            <li class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($inv); ?></span>
                                                    <a href="?menu=adjustment" class="btn btn-sm btn-outline-primary" target="_blank">Fix in Adjustment</a>
                                                </div>
                                                <div class="small text-muted mt-1 mt-2">
                                                    <strong>Saran Perbaikan:</strong>
                                                    <?php
                                                    $saran = "";
                                                    if (preg_match('/-F(\d+)$/i', $inv, $matches)) {
                                                        // Kasus: P-M04-01-F1 (Tanda hubung sebelum F)
                                                        $angka = $matches[1];
                                                        $base = preg_replace('/-F\d+$/i', '', $inv);
                                                        $saran = "Ubah tanda hubung <code>-F</code> menjadi garis miring <code>/F</code>.<br> Seharusnya: <span class='badge bg-success'>$base/F$angka</span>";
                                                    } elseif (preg_match('/ [a-z]/i', $inv)) {
                                                        // Kasus: P-M04-01 REVISI
                                                        $saran = "Pastikan Document No tidak mengandung spasi atau keterangan tambahan. Keterangan revisi ditaruh di field Reason Revisi.";
                                                    } else {
                                                        // Default
                                                        $saran = "Pastikan karakter terakhir setelah tanda hubung (<code>-</code>) murni berisi angka (Nomor Urut).";
                                                    }
                                                    echo $saran;
                                                    ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-success"><i class="fa-solid fa-check"></i> Tidak ada format nomor dokumen yang menyimpang dari standar.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endif; ?>
            <?php if($menu == 'health_user'): ?>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">Users Tanpa Employee Terkait (Error Login)</div>
                            <div class="card-body" style="max-height: 400px; overflow-y:auto;">
                                <?php
                                $usersWithoutEmployee = User::with('roles')->whereNull('employee_id')->orWhereNotExists(function($query) {
                                    $query->select(DB::raw(1))->from('m_employee')->whereRaw('m_employee.id = users.employee_id');
                                })->get();
                                ?>
                                <?php if($usersWithoutEmployee->count() > 0): ?>
                                    <ul class="list-group">
                                        <?php foreach($usersWithoutEmployee as $u): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?php echo $u->user_name; ?></strong><br>
                                                    <small>
                                                        <?php echo $u->name; ?>
                                                        <br>Roles:
                                                        <?php
                                                            if($u->roles) {
                                                                $roles = [];
                                                                foreach($u->roles as $r) {
                                                                    $roles[] = $r->name;
                                                                }
                                                                echo implode(", ", $roles);
                                                            } else {
                                                                echo "No roles assigned";
                                                            }
                                                        ?>
                                                    </small>
                                                </div>
                                                <span class="badge bg-danger rounded-pill">Unlinked</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <small class="text-muted d-block mt-2">Dampak: User ini mungkin error saat membuat/menyetujui dokumen.</small>
                                <?php else: ?>
                                    <div class="text-success"><i class="fa-solid fa-check"></i> Semua akun User terhubung dengan baik.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">Employee Belum Dibuatkan Akun User</div>
                            <div class="card-body" style="max-height: 400px; overflow-y:auto;">
                                <?php
                                $employeesWithoutUser = Employee::whereNotExists(function($query) {
                                    $query->select(DB::raw(1))->from('users')->whereRaw('users.employee_id = m_employee.id');
                                })->get();
                                ?>
                                <?php if($employeesWithoutUser->count() > 0): ?>
                                    <ul class="list-group">
                                        <?php foreach($employeesWithoutUser as $e): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?php echo $e->nik; ?></strong><br>
                                                    <small><?php echo $e->name; ?></small>
                                                </div>
                                                <span class="badge bg-warning rounded-pill">No Account</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <small class="text-muted d-block mt-2">Dampak: Karyawan ini tidak bisa login ke aplikasi.</small>
                                <?php else: ?>
                                    <div class="text-success"><i class="fa-solid fa-check"></i> Semua Karyawan memiliki akun User.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ALL USERS & ROLES -->
                <div class="card border-info">
                    <div class="card-header bg-info text-white">All Users Access List (Normal & Admin)</div>
                    <div class="card-body">
                        <?php
                            $allUsers = User::with(['roles', 'employee.departements'])->get();
                        ?>
                        <div class="table-responsive" style="padding-top: 10px;">
                            <table class="table table-bordered table-striped table-sm dt-table">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>User Name (Login)</th>
                                        <th>Full Name</th>
                                        <th>Roles / Access Lists</th>
                                        <th>Linked Employee NIK</th>
                                        <th>Departement</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach($allUsers as $u): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo $u->user_name; ?></td>
                                        <td><?php echo $u->name; ?></td>
                                        <td>
                                            <?php
                                                if($u->roles->count() > 0) {
                                                    foreach($u->roles as $r) {
                                                        $badgeColor = $r->code == 'ADM' ? 'bg-danger' : 'bg-primary';
                                                        echo "<span class='badge $badgeColor me-1'>" . $r->name . "</span>";
                                                    }
                                                } else {
                                                    echo "<span class='text-muted fst-italic'>None</span>";
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo $u->employee ? $u->employee->nik : '<span class="text-danger fw-bold">Unlinked</span>'; ?></td>
                                        <td><?php echo ($u->employee && $u->employee->departements) ? $u->employee->departements->name : '-'; ?></td>
                                        <td>
                                            <?php if($u->is_active): ?>
                                                <span class="text-success"><i class="fa-solid fa-check-circle"></i> Active</span>
                                            <?php else: ?>
                                                <span class="text-danger"><i class="fa-solid fa-xmark-circle"></i> Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

            <!-- MENU 4: HEALTH CHECK FILES -->
            <?php if($menu == 'health_file'): ?>
                <div class="card border-dark">
                    <div class="card-header bg-dark text-white">Files Integrity Check (DB vs Storage)</div>
                    <div class="card-body">
                        <p>Memindai database dokumen dan mengecek sinkronisasi file fisiknya di server folder <code>storage/app/public/...</code>.</p>

                        <?php
                            // --- Cek File Upload (Main Documents) ---
                            $mainIssues = [];
                            $uploads = DB::table('trx_document_upload')->select('id', 'document_no', 'file_name')->whereNotNull('file_name')->where('file_name','<>','')->get();
                            $dbFilesMain = [];

                            foreach($uploads as $u) {
                                $dbFilesMain[] = $u->file_name;
                                if(!Storage::disk('public')->exists('documents/' . $u->file_name)) {
                                    $mainIssues[] = [
                                        'id' => $u->id,
                                        'doc_no' => $u->document_no,
                                        'file_db' => $u->file_name,
                                        'file_storage' => '-',
                                        'issue' => '<span class="badge bg-danger">Missing in Storage</span>'
                                    ];
                                }
                            }

                            // Cek File yang ada di Storage tapi tidak ada di DB (Orphan)
                            $storageFilesMain = Storage::disk('public')->files('documents');
                            foreach($storageFilesMain as $sf) {
                                $baseName = basename($sf);
                                if($baseName === '.gitignore') continue;
                                if(!in_array($baseName, $dbFilesMain)) {
                                    $mainIssues[] = [
                                        'id' => '-',
                                        'doc_no' => '-',
                                        'file_db' => '-',
                                        'file_storage' => $baseName,
                                        'issue' => '<span class="badge bg-warning text-dark">Missing in DB (Orphan)</span>'
                                    ];
                                }
                            }


                            // --- Cek File Eksternal ---
                            $eksternalIssues = [];
                            $eksternals = DB::table('trx_document_eksternal')->select('id', 'document_no', 'file_name')->whereNotNull('file_name')->where('file_name','<>','')->get();
                            $dbFilesEks = [];

                            foreach($eksternals as $e) {
                                $dbFilesEks[] = $e->file_name;
                                if(!Storage::disk('public')->exists('eksternal-document/' . $e->file_name)) {
                                    $eksternalIssues[] = [
                                        'id' => $e->id,
                                        'doc_no' => $e->document_no,
                                        'file_db' => $e->file_name,
                                        'file_storage' => '-',
                                        'issue' => '<span class="badge bg-danger">Missing in Storage</span>'
                                    ];
                                }
                            }

                            // Cek File yang ada di Storage Eksternal tapi tidak ada di DB (Orphan)
                            if(Storage::disk('public')->exists('eksternal-document')) {
                                $storageFilesEks = Storage::disk('public')->files('eksternal-document');
                                foreach($storageFilesEks as $sf) {
                                    $baseName = basename($sf);
                                    if($baseName === '.gitignore') continue;
                                    if(!in_array($baseName, $dbFilesEks)) {
                                        $eksternalIssues[] = [
                                            'id' => '-',
                                            'doc_no' => '-',
                                            'file_db' => '-',
                                            'file_storage' => $baseName,
                                            'issue' => '<span class="badge bg-warning text-dark">Missing in DB (Orphan)</span>'
                                        ];
                                    }
                                }
                            }
                        ?>

                        <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                          <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="main-tab" data-bs-toggle="tab" data-bs-target="#main" type="button" role="tab">Main Documents <span class="badge bg-secondary"><?php echo count($mainIssues); ?> Issues</span></button>
                          </li>
                          <li class="nav-item" role="presentation">
                            <button class="nav-link" id="eksternal-tab" data-bs-toggle="tab" data-bs-target="#eksternal" type="button" role="tab">Eksternal Documents <span class="badge bg-secondary"><?php echo count($eksternalIssues); ?> Issues</span></button>
                          </li>
                        </ul>

                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="main" role="tabpanel">
                                <?php if(count($mainIssues) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm dt-table">
                                            <thead class="table-light"><tr><th width="5%">No</th><th width="8%">DB ID</th><th>Document No</th><th>File Name (Database)</th><th>File Name (Storage)</th><th>Issue Type</th><th width="15%">Action</th></tr></thead>
                                            <tbody>
                                                <?php $nom = 1; foreach($mainIssues as $m): ?>
                                                <tr>
                                                    <td><?php echo $nom++; ?></td>
                                                    <td><?php echo $m['id'] !== '-' ? '<span class="badge bg-secondary">#'.$m['id'].'</span>' : '-'; ?></td>
                                                    <td><?php echo $m['doc_no']; ?></td>
                                                    <td class="<?php echo $m['file_db'] === '-' ? 'text-muted' : 'text-danger fw-bold'; ?>"><?php echo $m['file_db']; ?></td>
                                                    <td class="<?php echo $m['file_storage'] === '-' ? 'text-muted' : 'text-warning fw-bold'; ?>"><?php echo $m['file_storage']; ?></td>
                                                    <td><?php echo $m['issue']; ?></td>
                                                    <td>
                                                        <?php if($m['file_db'] !== '-'): // Jika DB mencatat ada file, tapi fisiknya hilang ?>
                                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal" data-doctype="main" data-docno="<?php echo $m['doc_no']; ?>" data-filedb="<?php echo $m['file_db']; ?>">
                                                                <i class="fa-solid fa-cloud-arrow-up"></i> Upload
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted small">No Action</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-success"><i class="fa-solid fa-check"></i> File dokumen utama tersinkronisasi 100%.</p>
                                <?php endif; ?>
                            </div>
                            <div class="tab-pane fade" id="eksternal" role="tabpanel">
                                <?php if(count($eksternalIssues) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm dt-table">
                                            <thead class="table-light"><tr><th width="5%">No</th><th>Document No</th><th>File Name (Database)</th><th>File Name (Storage)</th><th>Issue Type</th><th width="15%">Action</th></tr></thead>
                                            <tbody>
                                                <?php $noe = 1; foreach($eksternalIssues as $m): ?>
                                                <tr>
                                                    <td><?php echo $noe++; ?></td>
                                                    <td><?php echo $m['doc_no']; ?></td>
                                                    <td class="<?php echo $m['file_db'] === '-' ? 'text-muted' : 'text-danger fw-bold'; ?>"><?php echo $m['file_db']; ?></td>
                                                    <td class="<?php echo $m['file_storage'] === '-' ? 'text-muted' : 'text-warning fw-bold'; ?>"><?php echo $m['file_storage']; ?></td>
                                                    <td><?php echo $m['issue']; ?></td>
                                                    <td>
                                                        <?php if($m['file_db'] !== '-'): ?>
                                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal" data-doctype="eksternal" data-docno="<?php echo $m['doc_no']; ?>" data-filedb="<?php echo $m['file_db']; ?>">
                                                                <i class="fa-solid fa-cloud-arrow-up"></i> Upload
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted small">No Action</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-success"><i class="fa-solid fa-check"></i> File dokumen eksternal tersinkronisasi 100%.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- MENU 5: DOCUMENT ADJUSTMENT -->
            <?php if($menu == 'adjustment'): ?>
                <div class="alert alert-danger shadow-sm border-danger">
                    <i class="fa-solid fa-triangle-exclamation fs-5 me-2"></i>
                    <strong>SUPER ADMIN WARNING:</strong> Mengubah data melalui menu ini akan mem-<i>bypass</i> seluruh aturan workflow Laravel. Input langsung ke database.
                </div>

                <ul class="nav nav-tabs mb-3" id="adjTab" role="tablist">
                  <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="internal-tab" data-bs-toggle="tab" data-bs-target="#adj-internal" type="button" role="tab">Internal Documents (Upload)</button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link" id="external-tab" data-bs-toggle="tab" data-bs-target="#adj-external" type="button" role="tab">Eksternal Documents</button>
                  </li>
                </ul>

                <div class="tab-content" id="adjTabContent">

                    <!-- TAB INTERNAL -->
                    <div class="tab-pane fade show active" id="adj-internal" role="tabpanel">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">Adjustment: trx_document_upload</div>
                            <div class="card-body">
                                <?php
                            $docsInt = DB::table('trx_document_upload')->select('id', 'document_no', 'document_name', 'document_status', 'rev_no', 'file_name', 'file_attachment', 'is_stamp_by_creator', 'is_checked_by_dc', 'is_rejected_by_dc', 'is_checked', 'is_rejected_by_checker', 'is_approved', 'is_rejected_by_appr', 'is_revise', 'is_obsolete', 'is_processing_revise_obsolete', 'created_at', 'obsolete_date', 'document_type', 'document_classification', 'document_function', 'departement', 'department_ids', 'checker', 'approver', 'created_by', 'reason_revisi')->orderBy('created_at', 'desc')->get();
                        ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm dt-table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="5%">No</th>
                                                <th width="8%">DB ID</th>
                                                <th>Doc No</th>
                                                <th>Doc Name</th>
                                                <th>Status</th>
                                                <th>Rev</th>
                                                <th>File</th>
                                                <th width="10%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $n=1; foreach($docsInt as $d): ?>
                                            <tr>
                                                <td><?php echo $n++; ?></td>
                                                <td><span class="badge bg-secondary">#<?php echo $d->id; ?></span></td>
                                                <td><?php echo $d->document_no; ?></td>
                                                <td><?php echo $d->document_name; ?></td>
                                                <td><span class="badge bg-secondary"><?php echo $d->document_status; ?></span></td>
                                                <td><?php echo $d->rev_no; ?></td>
                                                <td><span class="badge bg-info" title="<?php echo $d->file_name; ?>"><i class="fa-solid fa-file-pdf"></i></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#adjInternalModal"
                                                        data-json='<?php echo json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                                                    >
                                                        <i class="fa-solid fa-sliders"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB EXTERNAL -->
                    <div class="tab-pane fade" id="adj-external" role="tabpanel">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">Adjustment: trx_document_eksternal</div>
                            <div class="card-body">
                                <?php
                                    $docsExt = DB::table('trx_document_eksternal')->orderBy('created_at', 'desc')->get();
                                ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm dt-table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="5%">No</th>
                                                <th width="8%">DB ID</th>
                                                <th>Doc No</th>
                                                <th>Doc Name</th>
                                                <th>Status</th>
                                                <th>Type</th>
                                                <th>File</th>
                                                <th width="10%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $n=1; foreach($docsExt as $e): ?>
                                            <tr>
                                                <td><?php echo $n++; ?></td>
                                                <td><span class="badge bg-secondary">#<?php echo $e->id; ?></span></td>
                                                <td><?php echo $e->document_no; ?></td>
                                                <td><?php echo $e->document_name; ?></td>
                                                <td><span class="badge bg-secondary"><?php echo $e->status; ?></span></td>
                                                <td><?php echo $e->document_eksternal_type; ?></td>
                                                <td><span class="badge bg-info" title="<?php echo $e->file_name; ?>"><i class="fa-solid fa-file-pdf"></i></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#adjExternalModal"
                                                        data-json='<?php echo json_encode($e, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                                                    >
                                                        <i class="fa-solid fa-sliders"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Modal Adjustment Internal -->
                <div class="modal fade" id="adjInternalModal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                      <form method="POST">
                          <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Adjust Internal Document (ID: <span id="int_id_label"></span>)</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body bg-light">
                            <input type="hidden" name="action" value="document_adjustment_internal">
                            <input type="hidden" name="document_id" id="int_id">

                            <div class="row">
                                <!-- Text Info Area -->
                                <div class="col-md-8">
                                    <div class="card p-3 mb-3 shadow-sm border-0">
                                        <h6 class="border-bottom pb-2 text-primary"><i class="fa-solid fa-font"></i> Text Fields</h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <label class="small fw-bold">Document No</label>
                                                <input type="text" class="form-control form-control-sm" name="document_no" id="int_document_no">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="small fw-bold">Document Name</label>
                                                <input type="text" class="form-control form-control-sm" name="document_name" id="int_document_name">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="small fw-bold">Type Code</label>
                                                <select class="form-select form-select-sm" name="document_type_code" id="int_document_type">
                                                    <option value="">- Kosong -</option>
                                                    <?php foreach($documentTypes as $t): ?>
                                                        <option value="<?php echo $t->code; ?>"><?php echo $t->code; ?> - <?php echo $t->document_type; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="small fw-bold">Class Code</label>
                                                <select class="form-select form-select-sm" name="document_classification_code" id="int_document_classification">
                                                    <option value="">- Kosong -</option>
                                                    <?php foreach($classifications as $c): ?>
                                                        <option value="<?php echo $c->code; ?>"><?php echo $c->code; ?> - <?php echo $c->name; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="small fw-bold">Function Code</label>
                                                <select class="form-select form-select-sm" name="document_function_code" id="int_document_function">
                                                    <option value="">- Kosong -</option>
                                                    <?php foreach($functions as $f): ?>
                                                        <option value="<?php echo $f->code; ?>"><?php echo $f->code; ?> - <?php echo $f->name; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-12 mb-2 mt-3">
                                                <h6 class="border-bottom pb-1 text-primary"><i class="fa-solid fa-sitemap"></i> Organization & Workflow Personnel</h6>
                                            </div>

                                            <div class="col-md-6 mb-2">
                                                <label class="small fw-bold">Owner Departement</label>
                                                <select class="form-select form-select-sm" name="departement_id" id="int_departement_id">
                                                    <option value="">- Pilih Dept -</option>
                                                    <?php foreach($allDepartments as $d): ?>
                                                        <option value="<?php echo $d->id; ?>"><?php echo $d->code; ?> - <?php echo $d->name; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="small fw-bold">Department Tag (Distributed to)</label>
                                                <!-- We use a hidden input logic or a multiple select. Here we use multiple select -->
                                                <select class="form-select form-select-sm" name="department_ids[]" id="int_department_ids" multiple style="height: 100px;">
                                                    <?php foreach($allDepartments as $d): ?>
                                                        <option value="<?php echo $d->id; ?>"><?php echo $d->code; ?> - <?php echo $d->name; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="text-muted">Gunakan Ctrl+Click untuk memilih beberapa.</small>
                                            </div>

                                            <div class="col-md-4 mb-2">
                                                <label class="small fw-bold">Creator (User ID)</label>
                                                <select class="form-select form-select-sm" name="creator_id" id="int_creator_id">
                                                    <option value="">- Pilih Karyawan -</option>
                                                    <?php foreach($allEmployees as $e): ?>
                                                        <option value="<?php echo $e->id; ?>"><?php echo $e->nik; ?> - <?php echo $e->name; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="small fw-bold">Checker</label>
                                                <select class="form-select form-select-sm" name="checker_id" id="int_checker_id">
                                                    <option value="">- Pilih Karyawan -</option>
                                                    <?php foreach($allEmployees as $e): ?>
                                                        <option value="<?php echo $e->id; ?>"><?php echo $e->nik; ?> - <?php echo $e->name; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="small fw-bold">Approver</label>
                                                <select class="form-select form-select-sm" name="approver_id" id="int_approver_id">
                                                    <option value="">- Pilih Karyawan -</option>
                                                    <?php foreach($allEmployees as $e): ?>
                                                        <option value="<?php echo $e->id; ?>"><?php echo $e->nik; ?> - <?php echo $e->name; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-12 mb-2 mt-3">
                                                <h6 class="border-bottom pb-1 text-primary"><i class="fa-solid fa-file"></i> Files & Reason</h6>
                                            </div>

                                            <div class="col-md-6 mb-2">
                                                <label class="small fw-bold">File Name (PDF)</label>
                                                <input type="text" class="form-control form-control-sm" name="file_name" id="int_file_name">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="small fw-bold">File Attachment</label>
                                                <input type="text" class="form-control form-control-sm" name="file_attachment" id="int_file_attachment">
                                            </div>
                                            <div class="col-md-12 mb-2">
                                                <label class="small fw-bold">Reason Revisi</label>
                                                <textarea class="form-control form-control-sm" name="reason_revisi" id="int_reason_revisi" rows="2"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Flags Area -->
                                <div class="col-md-4">
                                    <div class="card p-3 mb-3 shadow-sm border-0">
                                        <h6 class="border-bottom pb-2 text-warning"><i class="fa-solid fa-flag"></i> Workflow Flags & Status</h6>
                                        <div class="mb-2">
                                            <label class="small fw-bold">Document Status Text</label>
                                            <select class="form-select form-select-sm border-primary" name="document_status" id="int_document_status" required>
                                                <option value="">- Pilih Status -</option>
                                                <option value="REQUESTED">REQUESTED</option>
                                                <option value="BOOKED">BOOKED</option>
                                                <option value="CREATED">CREATED</option>
                                                <option value="EDIT">EDIT</option>
                                                <option value="REVISE">REVISE</option>
                                                <option value="PASS_BY_DC">PASS_BY_DC</option>
                                                <option value="REJECT_BY_DC">REJECT_BY_DC</option>
                                                <option value="CHECKED">CHECKED</option>
                                                <option value="REJECT_BY_CHECKER">REJECT_BY_CHECKER</option>
                                                <option value="APPROVED">APPROVED</option>
                                                <option value="REJECT_BY_APPROVER">REJECT_BY_APPROVER</option>
                                                <option value="OBSOLETE">OBSOLETE</option>
                                                <option value="ARCHIVED">ARCHIVED</option>
                                                <option value="CANCELLED">CANCELLED</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="small fw-bold">Rev No</label>
                                            <input type="number" class="form-control form-control-sm" name="rev_no" id="int_rev_no" required>
                                        </div>

                                        <div class="row g-1">
                                            <div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_stamp_by_creator" id="int_is_stamp_by_creator" value="1"><label class="small">stamp_by_creator</label></div></div>
                                            <div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_checked_by_dc" id="int_is_checked_by_dc" value="1"><label class="small">checked_by_dc</label></div></div>
                                            <div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_rejected_by_dc" id="int_is_rejected_by_dc" value="1"><label class="small text-danger">rejected_by_dc</label></div></div>
                                            <div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_checked" id="int_is_checked" value="1"><label class="small">is_checked</label></div></div>
                                            <div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_rejected_by_checker" id="int_is_rejected_by_checker" value="1"><label class="small text-danger">reject_by_checker</label></div></div>
                                            <div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_approved" id="int_is_approved" value="1"><label class="small fw-bold">is_approved</label></div></div>
                                            <div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_rejected_by_appr" id="int_is_rejected_by_appr" value="1"><label class="small text-danger">reject_by_appr</label></div></div>
                                            <div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_revise" id="int_is_revise" value="1"><label class="small">is_revise</label></div></div>
                                            <div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_obsolete" id="int_is_obsolete" value="1"><label class="small text-warning fw-bold">is_obsolete</label></div></div>
                                            <div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_processing_revise_obsolete" id="int_is_processing_revise_obsolete" value="1"><label class="small">process_rev_obs</label></div></div>
                                        </div>
                                    </div>

                                    <div class="card p-3 shadow-sm border-0">
                                        <h6 class="border-bottom pb-2 text-info"><i class="fa-solid fa-calendar"></i> Dates</h6>
                                        <div class="mb-2">
                                            <label class="small fw-bold">Created At</label>
                                            <input type="text" class="form-control form-control-sm" name="created_at" id="int_created_at" placeholder="YYYY-MM-DD HH:MM:SS">
                                        </div>
                                        <div class="mb-0">
                                            <label class="small fw-bold">Obsolete Date</label>
                                            <input type="text" class="form-control form-control-sm" name="obsolete_date" id="int_obsolete_date" placeholder="YYYY-MM-DD HH:MM:SS">
                                        </div>
                                    </div>
                                </div>
                            </div>

                          </div>
                          <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Anda yakin ingin mengubah status database secara langsung?')"><i class="fa-solid fa-save"></i> Save Internal</button>
                          </div>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- Modal Adjustment External -->
                <div class="modal fade" id="adjExternalModal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <form method="POST">
                          <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">Adjust External Document (ID: <span id="ext_id_label"></span>)</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body bg-light">
                            <input type="hidden" name="action" value="document_adjustment_external">
                            <input type="hidden" name="document_id" id="ext_id">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold">Document No</label>
                                    <input type="text" class="form-control border-success" name="document_no" id="ext_document_no" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold">Document Name</label>
                                    <input type="text" class="form-control border-success" name="document_name" id="ext_document_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold">Type Code</label>
                                    <select class="form-select border-success" name="document_eksternal_type" id="ext_document_eksternal_type" required>
                                        <option value="">- Pilih Tipe -</option>
                                        <?php foreach($eksternalTypes as $et): ?>
                                            <option value="<?php echo $et->code; ?>"><?php echo $et->code; ?> - <?php echo $et->name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold">Revisi</label>
                                    <input type="text" class="form-control border-success" name="revisi" id="ext_revisi">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold">Status</label>
                                    <select class="form-select border-success" name="status" id="ext_status" required>
                                        <option value="ACTIVE">ACTIVE</option>
                                        <option value="OBSOLETE">OBSOLETE</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold">Created At</label>
                                    <input type="text" class="form-control border-success" name="created_at" id="ext_created_at" placeholder="YYYY-MM-DD HH:MM:SS">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="fw-bold">File Name</label>
                                    <input type="text" class="form-control border-success" name="file_name" id="ext_file_name">
                                </div>
                            </div>
                          </div>
                          <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success" onclick="return confirm('Anda yakin ingin mengubah status external document ini?')"><i class="fa-solid fa-save"></i> Save External</button>
                          </div>
                      </form>
                    </div>
                  </div>
                </div>

            <?php endif; ?>

            <!-- MENU 6: SYSTEM TOOLS -->
            <?php if($menu == 'sys_tools'): ?>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">Storage Symlink Checker</div>
                            <div class="card-body">
                                <p>Fitur ini memastikan bahwa file yang diunggah (di dalam folder server <code>storage</code>) dapat diakses oleh browser melalui folder publik (<code>public/storage</code>).</p>

                                <?php
                                    $publicStoragePath = __DIR__ . '/storage';
                                    $realStoragePath = realpath(__DIR__ . '/../storage/app/public');

                                    $linkStatus = "Missing";
                                    $badgeClass = "bg-danger";
                                    $statusMsg = "Folder public/storage tidak ditemukan. File gambar/PDF tidak akan bisa dibuka di aplikasi.";

                                    if (file_exists($publicStoragePath)) {
                                        $resolvedPublicPath = realpath($publicStoragePath);
                                        if ($resolvedPublicPath === $realStoragePath) {
                                            $linkStatus = "Healthy";
                                            $badgeClass = "bg-success";
                                            $statusMsg = "Storage Link bekerja dengan sempurna. (Menunjuk ke: $resolvedPublicPath)";
                                        } else {
                                            if (is_link($publicStoragePath)) {
                                                $linkStatus = "Healthy (Symlink)";
                                                $badgeClass = "bg-success";
                                                $statusMsg = "Storage Link aktif sebagai symlink murni.";
                                            } else {
                                                $linkStatus = "Broken";
                                                $badgeClass = "bg-warning text-dark";
                                                $statusMsg = "Folder public/storage ada, tetapi berupa folder biasa (bukan shortcut/symlink). Hapus folder tersebut lalu perbaiki link.";
                                            }
                                        }
                                    }
                                ?>

                                <div class="alert alert-light border shadow-sm">
                                    <h6 class="mb-2 fw-bold">Current Status: <span class="badge <?php echo $badgeClass; ?>"><?php echo $linkStatus; ?></span></h6>
                                    <p class="small text-muted mb-0"><?php echo $statusMsg; ?></p>
                                </div>

                                <form method="POST" onsubmit="return confirm('Proses ini akan menghapus link lama dan membuat link baru. Jika Anda memakai Windows, Anda mungkin perlu menjalankan portal ini dengan mode Administrator. Lanjutkan?');">
                                    <input type="hidden" name="action" value="fix_storage_link">
                                    <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-link"></i> Fix Storage Link</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card border-secondary">
                            <div class="card-header bg-secondary text-white">Application Cache</div>
                            <div class="card-body">
                                <p>Bersihkan Cache Laravel jika Anda merasa tampilan web tidak ter-update setelah mengubah kode, atau jika dropdown database terasa lambat.</p>
                                <ul class="small text-muted mb-4">
                                    <li>Mengeksekusi <code>php artisan cache:clear</code></li>
                                    <li>Mengeksekusi <code>php artisan config:clear</code></li>
                                    <li>Mengeksekusi <code>php artisan view:clear</code></li>
                                </ul>

                                <form method="POST" onsubmit="return confirm('Ini akan membersihkan semua cache framework. Lanjutkan?');">
                                    <input type="hidden" name="action" value="clear_cache">
                                    <button type="submit" class="btn btn-outline-secondary w-100"><i class="fa-solid fa-broom"></i> Clear All Cache</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="POST" enctype="multipart/form-data">
                      <div class="modal-header">
                        <h5 class="modal-title" id="uploadModalLabel">Upload Missing File</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <p class="text-muted small">Sistem akan secara otomatis me-rename file yang Anda unggah agar sesuai dengan ekspektasi Database.</p>
                        <input type="hidden" name="action" value="upload_missing_file">
                        <input type="hidden" name="doc_type" id="modal_doc_type">
                        <input type="hidden" name="doc_no" id="modal_doc_no">
                        <input type="hidden" name="file_db" id="modal_file_db">

                        <div class="mb-3">
                            <label class="fw-bold">Document No</label>
                            <input type="text" class="form-control bg-light" id="display_doc_no" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Expected File Name (DB)</label>
                            <input type="text" class="form-control bg-light" id="display_file_db" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold text-primary">Pilih File PDF Fisik</label>
                            <input type="file" class="form-control" name="pdf_upload" accept=".pdf" required>
                        </div>
                      </div>
                      <div class="modal-header">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload & Sinkronkan</button>
                      </div>
                  </form>
                </div>
              </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('.dt-table').DataTable({
        pageLength: 10,
        responsive: true,
        language: {
            search: "Pencarian:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            paginate: {
                first: "Awal",
                last: "Akhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });

    // Populate Upload Modal Data
    $('#uploadModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var modal = $(this);
        modal.find('#modal_doc_type').val(button.data('doctype'));
        modal.find('#modal_doc_no').val(button.data('docno'));
        modal.find('#modal_file_db').val(button.data('filedb'));
        modal.find('#display_doc_no').val(button.data('docno'));
        modal.find('#display_file_db').val(button.data('filedb'));
    });

    // Populate Adjustment Internal Modal Data
    $('#adjInternalModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var data = button.data('json');
        var modal = $(this);

        modal.find('#int_id_label').text(data.id);
        modal.find('#int_id').val(data.id);

        // Text Fields
        modal.find('#int_document_no').val(data.document_no);
        modal.find('#int_document_name').val(data.document_name);

        // Select Options
        modal.find('#int_document_type').val(data.document_type);
        modal.find('#int_document_classification').val(data.document_classification);
        modal.find('#int_document_function').val(data.document_function);

        // Select Options ID Based
        modal.find('#int_departement_id').val(data.departement);

        // Multiple Select Option
        if(data.department_ids) {
            var selectedIds = data.department_ids.split(',');
            modal.find('#int_department_ids').val(selectedIds);
        } else {
            modal.find('#int_department_ids').val([]);
        }

        // Need ID for these, but DB stores name primarily.
        // We'll map it to ID if available, otherwise it might be empty
        // Assuming we fetched 'creator', 'checker', 'approver' IDs in the query earlier
        modal.find('#int_creator_id').val(data.created_by);
        modal.find('#int_checker_id').val(data.checker);
        modal.find('#int_approver_id').val(data.approver);

        modal.find('#int_file_name').val(data.file_name);
        modal.find('#int_file_attachment').val(data.file_attachment);
        modal.find('#int_reason_revisi').val(data.reason_revisi);

        // Status & Dates
        modal.find('#int_document_status').val(data.document_status);
        modal.find('#int_rev_no').val(data.rev_no);
        modal.find('#int_created_at').val(data.created_at);
        modal.find('#int_obsolete_date').val(data.obsolete_date);

        // Bit Flags (Checkboxes)
        modal.find('#int_is_stamp_by_creator').prop('checked', data.is_stamp_by_creator == 1);
        modal.find('#int_is_checked_by_dc').prop('checked', data.is_checked_by_dc == 1);
        modal.find('#int_is_rejected_by_dc').prop('checked', data.is_rejected_by_dc == 1);
        modal.find('#int_is_checked').prop('checked', data.is_checked == 1);
        modal.find('#int_is_rejected_by_checker').prop('checked', data.is_rejected_by_checker == 1);
        modal.find('#int_is_approved').prop('checked', data.is_approved == 1);
        modal.find('#int_is_rejected_by_appr').prop('checked', data.is_rejected_by_appr == 1);
        modal.find('#int_is_revise').prop('checked', data.is_revise == 1);
        modal.find('#int_is_obsolete').prop('checked', data.is_obsolete == 1);
        modal.find('#int_is_processing_revise_obsolete').prop('checked', data.is_processing_revise_obsolete == 1);
    });

    // Populate Adjustment External Modal Data
    $('#adjExternalModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var data = button.data('json');
        var modal = $(this);

        modal.find('#ext_id_label').text(data.id);
        modal.find('#ext_id').val(data.id);

        modal.find('#ext_document_no').val(data.document_no);
        modal.find('#ext_document_name').val(data.document_name);
        modal.find('#ext_document_eksternal_type').val(data.document_eksternal_type);
        modal.find('#ext_revisi').val(data.revisi);
        modal.find('#ext_status').val(data.status);
        modal.find('#ext_file_name').val(data.file_name);
        modal.find('#ext_created_at').val(data.created_at);
    });
});
</script>
</body>
</html>
