<?php

/**
 * Advanced Maintenance Data Tool for DMS Portal
 * Access: /maintenance-data.php
 */

use Illuminate\Support\Facades\DB;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Models\Employee;
use App\Models\DocumentType;
use App\Models\DocumentClassification;

define('LARAVEL_START', microtime(true));

// 1. Load Laravel Bootstrap
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

if (ob_get_length()) ob_end_clean();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$message = '';

// --- ACTIONS ---

if ($action === 'set_numbering_bulk') {
    $typeId = $_POST['type_id'];
    $classId = $_POST['class_id'];
    $typeCode = $_POST['type_code'];
    $classCode = $_POST['class_code'];
    $targetNum = (int)$_POST['target_num'];

    try {
        DB::beginTransaction();
        for ($i = 1; $i < $targetNum; $i++) {
            $numStr = str_pad($i, 2, '0', STR_PAD_LEFT);
            $fullNo = "{$typeCode}-{$classCode}-{$numStr}";

            if (!DocumentRequest::where('document_no', $fullNo)->exists()) {
                DocumentRequest::create([
                    'document_type' => $typeId,
                    'document_type_code' => $typeCode,
                    'document_classification' => $classId,
                    'document_classification_code' => $classCode,
                    'document_form' => '',
                    'document_parent_no' => $fullNo,
                    'document_no' => $fullNo,
                    'document_name' => 'SYSTEM DUMMY (MAINTENANCE CUT-OFF)',
                    'document_status' => 'BOOKED',
                    'notes' => 'Bulk cut-off sequence',
                    'is_used' => 1,
                    'status' => 0,
                    'created_by' => Auth::id() ?: 1,
                ]);
            }
        }
        DB::commit();
        $message = "<div class='alert alert-success'>Successfully filled sequence up to " . ($targetNum - 1) . ". Next number will be $targetNum.</div>";
    } catch (\Exception $e) {
        DB::rollBack();
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

if ($action === 'fix_single_user_sync') {
    $userId = $_POST['user_id'];
    try {
        $user = User::findOrFail($userId);
        Employee::create([
            'nik' => $user->user_name,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 1,
            'created_by' => Auth::id() ?: 1
        ]);
        $message = "<div class='alert alert-success'>User $user->name synced to Employee table.</div>";
    } catch (\Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

if ($action === 'mark_read_bulk') {
    $docId = $_POST['doc_id'] ?? null;
    $empId = $_POST['employee_id'] ?? null;
    $target = $_POST['target'] ?? 'single';

    try {
        DB::beginTransaction();

        $employees = [];
        if ($target === 'all_users' || $target === 'everything') {
            $employees = Employee::all();
        } else {
            if (empty($empId)) throw new Exception("Please select an employee.");
            $employees = [Employee::find($empId)];
        }

        $docs = [];
        if ($target === 'all_docs' || $target === 'everything') {
            $docs = DB::table('trx_document_upload')->where('document_status', 'APPROVED')->where('is_obsolete', 0)->get();
        } else {
            if (empty($docId)) throw new Exception("Please select a document.");
            $docs = [DB::table('trx_document_upload')->find($docId)];
        }

        $count = 0;
        foreach ($employees as $emp) {
            if (!$emp) continue;

            // Find corresponding User ID if exists
            $userId = DB::table('users')->where('user_name', $emp->nik)->value('id');
            $deptName = DB::table('m_departement')->where('id', $emp->departement ?? 0)->value('name') ?? 'N/A';

            foreach ($docs as $doc) {
                if (!$doc) continue;

                $exists = DB::table('tr_audit_trails')
                    ->where('document_id', $doc->id)
                    ->where('employee_id', $emp->id) // Based on the Index and Query in Controller
                    ->where('action', 'MARK_AS_READ')
                    ->exists();

                if (!$exists) {
                    DB::table('tr_audit_trails')->insert([
                        'document_id' => $doc->id,
                        'document_no' => $doc->document_no,
                        'document_name' => $doc->document_name,
                        'action' => 'MARK_AS_READ',
                        'file_name' => $doc->file_name,
                        'notes' => 'Bulk Fixed via Maintenance Tool',
                        'user_id' => $userId,
                        'employee_id' => $emp->id,
                        'employee_name' => $emp->name,
                        'departement_id' => $emp->departement ?? 0,
                        'departement_name' => $deptName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $count++;
                }
            }
        }
        DB::commit();
        $message = "<div class='alert alert-success'>Successfully added $count missing 'Mark as Read' records based on Employee data.</div>";
    } catch (\Exception $e) {
        DB::rollBack();
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// --- DATA FETCHING ---

// 8. Get current sequences with names
$sequences = DB::table('trx_document_request as tdr')
    ->leftJoin('m_document_type as mdt', 'tdr.document_type', '=', 'mdt.id')
    ->leftJoin('m_document_classification as mdc', 'tdr.document_classification', '=', 'mdc.id')
    ->select(
        'tdr.document_type',
        'tdr.document_type_code',
        'mdt.document_type as type_name',
        'tdr.document_classification',
        'tdr.document_classification_code',
        'mdc.name as class_name',
        DB::raw('MAX(CAST(SUBSTRING_INDEX(tdr.document_no, "-", -1) AS UNSIGNED)) as last_num')
    )
    ->groupBy('tdr.document_type', 'tdr.document_type_code', 'mdt.document_type', 'tdr.document_classification', 'tdr.document_classification_code', 'mdc.name')
    ->get();

// 10. Find Orphan Users (No employee record)
$orphanUsers = User::whereNotExists(function($query) {
    $query->select(DB::raw(1))
          ->from('m_employee')
          ->whereRaw('m_employee.nik = users.user_name');
})->get();

// 9. Recent Approved Documents for Acknowledgement Fix
$recentDocs = DB::table('trx_document_upload')
    ->where('document_status', 'APPROVED')
    ->where('is_obsolete', 0)
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

$allEmployees = Employee::orderBy('name')->get();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMS Maintenance Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style type="text/tailwindcss">
        @layer components {
            .nav-tab {
                @apply px-6 py-3 font-medium text-gray-500 border-b-2 border-transparent transition-all duration-200 cursor-pointer outline-none;
            }
            .nav-tab.active {
                @apply border-blue-600 text-blue-600;
            }
            .card {
                @apply bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden;
            }
            .btn-primary {
                @apply bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200 text-sm disabled:opacity-50;
            }
            .btn-success {
                @apply bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200 text-sm;
            }
            .btn-warning {
                @apply bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200 text-sm;
            }
            .input-field {
                @apply block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2;
            }
            .badge {
                @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<nav class="bg-gray-900 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <div class="flex items-center">
                <i class="ri-tools-line text-blue-400 text-2xl mr-3"></i>
                <span class="text-white text-xl font-bold tracking-tight">DMS Maintenance Pro</span>
            </div>
            <div class="text-gray-400 text-sm">
                <span class="mr-2">Logged in as:</span>
                <span class="text-gray-200 font-medium"><?= Auth::user() ? Auth::user()->name : 'Guest' ?></span>
            </div>
        </div>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <?php if($message): ?>
        <div class="mb-6">
            <?= str_replace(
                ['alert alert-success', 'alert alert-danger', 'alert alert-warning'],
                ['bg-emerald-50 border-l-4 border-emerald-500 p-4 text-emerald-700 rounded shadow-sm', 'bg-red-50 border-l-4 border-red-500 p-4 text-red-700 rounded shadow-sm', 'bg-amber-50 border-l-4 border-amber-500 p-4 text-amber-700 rounded shadow-sm'],
                $message
            ) ?>
        </div>
    <?php endif; ?>

    <div class="mb-8 border-b border-gray-200">
        <nav class="flex space-x-8" aria-label="Tabs">
            <button onclick="switchTab('sequence', this)" class="nav-tab active">Numbering Cut-off</button>
            <button onclick="switchTab('notif', this)" class="nav-tab">Acknowledge Fix</button>
            <button onclick="switchTab('sync', this)" class="nav-tab">User/Employee Sync</button>
        </nav>
    </div>

    <div id="tab-content">
        <!-- TAB 8: SEQUENCE -->
        <div id="tab-sequence" class="tab-pane block">
            <div class="card">
                <div class="px-6 py-4 bg-white border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Current Numbering Sequences</h3>
                        <p class="text-sm text-gray-500">View current max numbers and "jump" them if needed.</p>
                    </div>
                    <div class="w-full md:w-80 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-search-line text-gray-400"></i>
                        </div>
                        <input type="text" id="searchSeq" class="input-field pl-10" placeholder="Search prefix or name...">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="seqTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Classification</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Last Seq</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Set Next Number</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($sequences as $seq): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="badge bg-gray-100 text-gray-800 border border-gray-200 mb-1 font-mono"><?= $seq->document_type_code ?></span>
                                    <div class="text-xs text-gray-500 font-medium"><?= $seq->type_name ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="badge bg-blue-50 text-blue-700 border border-blue-100 mb-1 font-mono"><?= $seq->document_classification_code ?></span>
                                    <div class="text-xs text-gray-500 font-medium"><?= $seq->class_name ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center font-mono font-bold text-gray-900"><?= $seq->last_num ?></td>
                                <form method="POST">
                                    <input type="hidden" name="action" value="set_numbering_bulk">
                                    <input type="hidden" name="type_id" value="<?= $seq->document_type ?>">
                                    <input type="hidden" name="class_id" value="<?= $seq->document_classification ?>">
                                    <input type="hidden" name="type_code" value="<?= $seq->document_type_code ?>">
                                    <input type="hidden" name="class_code" value="<?= $seq->document_classification_code ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="number" name="target_num" class="input-field w-24 text-center font-mono" value="<?= $seq->last_num + 1 ?>">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <button type="submit" class="btn-primary" onclick="return confirm('Apply cut-off for <?= $seq->document_type_code ?>-<?= $seq->document_classification_code ?>?')">
                                            Apply
                                        </button>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-8 bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-700">
                <h4 class="text-white font-semibold mb-4 flex items-center">
                    <i class="ri-add-circle-line mr-2 text-blue-400"></i> Register New Sequence Prefix
                </h4>
                <form class="grid grid-cols-1 md:grid-cols-6 gap-4" method="POST">
                    <input type="hidden" name="action" value="set_numbering_bulk">
                    <div><input type="text" name="type_code" placeholder="Type (P)" class="input-field bg-gray-700 border-gray-600 text-white placeholder-gray-400" required></div>
                    <div><input type="text" name="class_code" placeholder="Class (C08)" class="input-field bg-gray-700 border-gray-600 text-white placeholder-gray-400" required></div>
                    <div><input type="number" name="type_id" placeholder="Type ID" class="input-field bg-gray-700 border-gray-600 text-white placeholder-gray-400" required></div>
                    <div><input type="number" name="class_id" placeholder="Class ID" class="input-field bg-gray-700 border-gray-600 text-white placeholder-gray-400" required></div>
                    <div><input type="number" name="target_num" placeholder="Start From" class="input-field bg-gray-700 border-gray-600 text-white placeholder-gray-400" required></div>
                    <div><button type="submit" class="btn-primary w-full h-full bg-blue-500 hover:bg-blue-400 border-none">Initialize</button></div>
                </form>
            </div>
        </div>

        <!-- TAB 9: NOTIF FIX -->
        <div id="tab-notif" class="tab-pane hidden">
            <div class="card">
                <div class="px-6 py-4 bg-white border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Manual Acknowledge Injector</h3>
                    <p class="text-sm text-gray-500">Correct notification counts by manually marking documents as read.</p>
                </div>
                <div class="p-6">
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <input type="hidden" name="action" value="mark_read_bulk">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-2">1. Scope</label>
                            <select name="target" class="input-field" id="acknowledgeScope" onchange="toggleInputs()">
                                <option value="single">Single Employee + Single Doc</option>
                                <option value="all_users">ALL EMPLOYEES + Single Doc</option>
                                <option value="all_docs">Single Employee + ALL DOCS</option>
                                <option value="everything">ALL EMPLOYEES + ALL DOCS (Add Missing Logs)</option>
                            </select>
                        </div>
                        <div id="docInput">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-2">2. Document</label>
                            <select name="doc_id" class="input-field">
                                <option value="">-- Choose Approved Doc --</option>
                                <?php foreach($recentDocs as $rd): ?>
                                    <option value="<?= $rd->id ?>"><?= $rd->document_no ?> - <?= $rd->document_name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="userInput">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-2">3. Employee</label>
                            <select name="employee_id" class="input-field">
                                <option value="">-- Choose Employee --</option>
                                <?php foreach($allEmployees as $ae): ?>
                                    <option value="<?= $ae->id ?>"><?= $ae->name ?> (<?= $ae->nik ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="btn-success w-full flex items-center justify-center space-x-2 py-2.5" onclick="return confirm('Process bulk insertion?')">
                                <i class="ri-check-double-line"></i>
                                <span>Execute Process</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- TAB 10: SYNC -->
        <div id="tab-sync" class="tab-pane hidden">
            <div class="card">
                <div class="px-6 py-4 bg-white border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Inconsistent Data Detect</h3>
                        <p class="text-sm text-gray-500">Users found in <code>users</code> but missing from <code>m_employee</code>.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="relative w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ri-search-line text-gray-400"></i>
                            </div>
                            <input type="text" id="searchSync" class="input-field pl-10" placeholder="Search user...">
                        </div>
                        <span class="badge bg-red-100 text-red-700 px-3 py-1 border border-red-200"><?= count($orphanUsers) ?> Detected</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="syncTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">NIK / Username</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Display Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if(count($orphanUsers) > 0): ?>
                                <?php foreach($orphanUsers as $ou): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap font-mono text-sm text-gray-900"><?= $ou->user_name ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium"><?= $ou->name ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $ou->email ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="fix_single_user_sync">
                                            <input type="hidden" name="user_id" value="<?= $ou->id ?>">
                                            <button type="submit" class="btn-warning text-xs uppercase tracking-wider px-3 py-1.5">Create Record</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="ri-checkbox-circle-fill text-emerald-500 text-5xl mb-2"></i>
                                            <span class="text-gray-500 font-medium">All users are correctly synced with employee records.</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabName, el) {
    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');

    document.querySelectorAll('.tab-pane').forEach(p => {
        p.classList.add('hidden');
        p.classList.remove('block');
    });

    const target = document.getElementById('tab-' + tabName);
    target.classList.remove('hidden');
    target.classList.add('block');
}

function toggleInputs() {
    const scope = document.getElementById('acknowledgeScope').value;
    const docInput = document.getElementById('docInput');
    const userInput = document.getElementById('userInput');

    docInput.style.display = (scope === 'all_docs' || scope === 'everything') ? 'none' : 'block';
    userInput.style.display = (scope === 'all_users' || scope === 'everything') ? 'none' : 'block';
}

function setupSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    if (!input) return;

    input.addEventListener('keyup', function() {
        const value = this.value.toLowerCase();
        const rows = document.querySelectorAll(`#${tableId} tbody tr`);

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(value) ? '' : 'none';
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    toggleInputs();
    setupSearch('searchSeq', 'seqTable');
    setupSearch('searchSync', 'syncTable');
});
</script>
</body>
</html>
