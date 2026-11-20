<?php

namespace App\Http\Controllers;

use App\Models\AdhocDocument;
use App\Models\DocumentSigner;
use App\Models\User;
use App\Services\AdhocSignerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use Exception;

class AdhocDocumentController extends Controller
{
    protected AdhocSignerService $signerService;

    public function __construct(AdhocSignerService $signerService)
    {
        $this->signerService = $signerService;
    }

    /**
     * Document Dashboard List
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tab = $request->input('tab', 'my_requests');

        $myRequestsQuery = AdhocDocument::where('created_by', $user->id)
            ->with(['signers.user', 'creator'])
            ->latest();

        $needSignQuery = AdhocDocument::whereHas('signers', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('status', 'PENDING');
            })
            ->where('status', 'IN_PROGRESS')
            ->with(['signers.user', 'creator'])
            ->latest();

        $allDocsQuery = AdhocDocument::with(['signers.user', 'creator'])
            ->latest();

        $myRequestsCount = $myRequestsQuery->count();
        $needSignCount = $needSignQuery->count();

        $documents = match ($tab) {
            'need_sign' => $needSignQuery->paginate(15),
            'all' => $allDocsQuery->paginate(15),
            default => $myRequestsQuery->paginate(15),
        };

        return view('content.adhoc-document.index', compact('documents', 'tab', 'myRequestsCount', 'needSignCount'));
    }

    /**
     * Step 1: Upload File & Select Signers Form
     */
    public function create()
    {
        // Load internal users with their employee profile, departement, and position
        $users = User::where('is_active', true)
            ->with(['employee.positions', 'employee.departements'])
            ->orderBy('name', 'asc')
            ->get();

        return view('content.adhoc-document.create', compact('users'));
    }

    /**
     * Step 1 Submit: Save Document Draft & Signers, then redirect to Step 2 (Visual Tagging)
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'document_file' => 'required|file|mimes:pdf|max:25600', // 25MB max
            'workflow_type' => 'required|in:SEQUENTIAL,PARALLEL',
            'signers' => 'required|array|min:1',
            'signers.*.user_id' => 'required|exists:users,id',
            'signers.*.role_name' => 'nullable|string|max:100',
        ]);

        try {
            $user = Auth::user();
            $file = $request->file('document_file');

            $document = $this->signerService->uploadDocument($file, $request->all(), $user, $request);

            // Persist signers directly to database immediately
            $signersData = $request->input('signers', []);
            $order = 1;
            foreach ($signersData as $s) {
                if (empty($s['user_id'])) continue;
                DocumentSigner::create([
                    'document_id' => $document->id,
                    'user_id' => (int) $s['user_id'],
                    'order_index' => $order++,
                    'role_name' => !empty($s['role_name']) ? strip_tags($s['role_name']) : 'Signer ' . ($order - 1),
                    'status' => 'WAITING',
                ]);
            }

            session(['adhoc_signers_' . $document->uuid => $signersData]);

            Alert::success('Berhasil', 'Dokumen berhasil diunggah. Silakan atur posisi tanda tangan pada dokumen.');
            return redirect()->route('adhoc-document.tagging', ['uuid' => $document->uuid]);
        } catch (Exception $e) {
            Log::error('Adhoc document upload failed: ' . $e->getMessage());
            Alert::error('Gagal Mengunggah Dokumen', $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Step 2: Visual PDF Canvas Tagging Page
     */
    public function tagging(string $uuid)
    {
        $document = AdhocDocument::where('uuid', $uuid)->with('signers.user.employee.positions')->firstOrFail();

        // Security check: only creator or admin can configure tagging while in DRAFT
        if ($document->created_by !== Auth::id() && (!Auth::user() || !Auth::user()->hasRole('ADM'))) {
            Alert::warning('Perhatian', 'Dokumen tidak dalam status draft atau Anda bukan pemilik dokumen.');
            return redirect()->route('adhoc-document.show', ['uuid' => $uuid]);
        }

        if ($document->status !== 'DRAFT') {
            Alert::info('Info', 'Dokumen ini sudah dalam proses atau telah selesai.');
            return redirect()->route('adhoc-document.show', ['uuid' => $uuid]);
        }

        $signers = $document->signers;

        // If signers is empty in DB, try session fallback
        if ($signers->isEmpty()) {
            $signersData = session('adhoc_signers_' . $document->uuid, []);
            if (!empty($signersData)) {
                $order = 1;
                foreach ($signersData as $s) {
                    if (empty($s['user_id'])) continue;
                    DocumentSigner::create([
                        'document_id' => $document->id,
                        'user_id' => (int) $s['user_id'],
                        'order_index' => $order++,
                        'role_name' => !empty($s['role_name']) ? strip_tags($s['role_name']) : 'Signer ' . ($order - 1),
                        'status' => 'WAITING',
                    ]);
                }
                $signers = $document->signers()->with('user.employee.positions')->get();
            }
        }

        // If still empty, add creator as default Signer 1
        if ($signers->isEmpty()) {
            DocumentSigner::create([
                'document_id' => $document->id,
                'user_id' => $document->created_by ?: Auth::id(),
                'order_index' => 1,
                'role_name' => 'Signer 1',
                'status' => 'WAITING',
            ]);
            $signers = $document->signers()->with('user.employee.positions')->get();
        }

        $preparedSigners = [];
        foreach ($signers as $idx => $s) {
            $uObj = $s->user;
            $preparedSigners[] = [
                'key' => 'signer_' . $idx,
                'id' => $s->id,
                'user_id' => $s->user_id,
                'order' => $s->order_index,
                'name' => $uObj ? $uObj->name : 'Signer ' . ($idx + 1),
                'role_name' => $s->role_name ?: ($uObj && $uObj->employee && $uObj->employee->positions ? $uObj->employee->positions->name : 'Signer'),
                'email' => $uObj ? $uObj->email : '',
            ];
        }

        return view('content.adhoc-document.tagging', compact('document', 'preparedSigners'));
    }

    /**
     * Step 2 Submit: Save Tagging & Start Workflow
     */
    public function saveTagging(Request $request, string $uuid)
    {
        $document = AdhocDocument::where('uuid', $uuid)->firstOrFail();

        if ($document->created_by !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'signers_json' => 'required|string',
            'fields_json' => 'required|string',
        ]);

        try {
            $signersData = json_decode($request->input('signers_json'), true);
            $fieldsData = json_decode($request->input('fields_json'), true);

            if (!is_array($signersData) || count($signersData) === 0) {
                throw new Exception('Daftar penanda tangan wajib diisi.');
            }

            if (!is_array($fieldsData) || count($fieldsData) === 0) {
                throw new Exception('Anda wajib menaruh minimal satu kotak Tanda Tangan pada dokumen.');
            }

            $this->signerService->setupSignersAndFields($document, $signersData, $fieldsData, $request);

            session()->forget('adhoc_signers_' . $document->uuid);

            Alert::success('Sukses', 'Permintaan tanda tangan berhasil dikirimkan ke penanda tangan.');
            return redirect()->route('adhoc-document.show', ['uuid' => $document->uuid]);
        } catch (Exception $e) {
            Log::error('Save tagging failed: ' . $e->getMessage());
            Alert::error('Gagal Memproses', $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Document Detail & Monitoring Page
     */
    public function show(string $uuid)
    {
        $document = AdhocDocument::where('uuid', $uuid)
            ->with(['creator', 'signers.user.employee.positions', 'fields', 'auditLogs'])
            ->firstOrFail();

        $user = Auth::user();
        $mySignerRecord = $document->signers->firstWhere('user_id', $user->id);

        return view('content.adhoc-document.show', compact('document', 'mySignerRecord'));
    }

    /**
     * Signer Review Page (Deep link from email / app)
     */
    public function review(string $uuid, Request $request)
    {
        $document = AdhocDocument::where('uuid', $uuid)
            ->with(['creator', 'signers.user.employee.positions', 'fields'])
            ->firstOrFail();

        $user = Auth::user();

        // 1. Authorize: check if user is a signer on this document
        $signer = $document->signers->firstWhere('user_id', $user->id);
        if (!$signer) {
            Alert::error('Akses Ditolak', 'Akun Anda tidak terdaftar sebagai penanda tangan pada dokumen ini.');
            return redirect()->route('adhoc-document.show', ['uuid' => $uuid]);
        }

        // 2. Check workflow turn (Sequential check)
        if ($signer->status === 'WAITING') {
            Alert::info('Belum Giliran Anda', 'Dokumen ini menggunakan mode berurutan (Sequential). Menunggu penanda tangan sebelum Anda menyelesaikan tanda tangan.');
            return redirect()->route('adhoc-document.show', ['uuid' => $uuid]);
        }

        if ($signer->status === 'SIGNED') {
            Alert::info('Sudah Ditandatangani', 'Anda telah menandatangani dokumen ini pada ' . $signer->signed_at->format('d/m/Y H:i'));
            return redirect()->route('adhoc-document.show', ['uuid' => $uuid]);
        }

        if ($signer->status === 'REJECTED') {
            Alert::warning('Dokumen Ditolak', 'Dokumen ini telah ditolak.');
            return redirect()->route('adhoc-document.show', ['uuid' => $uuid]);
        }

        // 3. Log DOCUMENT_VIEWED in audit trail
        $this->signerService->logAudit(
            $document->id,
            $user->id,
            $user->name,
            $user->email,
            'DOCUMENT_VIEWED',
            "Signer {$user->name} opened and reviewed document #{$document->uuid}",
            $request
        );

        // 4. Retrieve existing signature if available
        $existingSignatureUrl = null;
        if ($user->employee && !empty($user->employee->signature) && Storage::disk('public')->exists('signature/' . $user->employee->signature)) {
            $existingSignatureUrl = asset('storage/signature/' . $user->employee->signature);
        } elseif (!empty($user->signature) && Storage::disk('public')->exists('signature/' . $user->signature)) {
            $existingSignatureUrl = asset('storage/signature/' . $user->signature);
        }

        // Fields assigned specifically to this signer
        $myFields = $document->fields->where('document_signer_id', $signer->id);

        return view('content.adhoc-document.review', compact('document', 'signer', 'myFields', 'existingSignatureUrl'));
    }

    /**
     * Execute Sign & Approve action
     */
    public function sign(Request $request, string $uuid)
    {
        $document = AdhocDocument::where('uuid', $uuid)->firstOrFail();
        $user = Auth::user();

        $signer = $document->signers()->where('user_id', $user->id)->firstOrFail();

        $signatureDataUrl = $request->input('signature_data');

        try {
            $this->signerService->processSign($document, $signer, $signatureDataUrl, $request);

            Alert::success('Berhasil Ditandatangani', 'Terima kasih, dokumen telah berhasil Anda tandatangani!');
            return redirect()->route('adhoc-document.show', ['uuid' => $document->uuid]);
        } catch (Exception $e) {
            Log::error('Signing error: ' . $e->getMessage());
            Alert::error('Gagal Menandatangani', $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Execute Reject action
     */
    public function reject(Request $request, string $uuid)
    {
        $request->validate([
            'reject_reason' => 'required|string|min:5|max:1000',
        ]);

        $document = AdhocDocument::where('uuid', $uuid)->firstOrFail();
        $user = Auth::user();

        $signer = $document->signers()->where('user_id', $user->id)->firstOrFail();

        try {
            $this->signerService->processReject($document, $signer, $request->input('reject_reason'), $request);

            Alert::warning('Dokumen Ditolak', 'Dokumen telah ditolak dan alur persetujuan dihentikan.');
            return redirect()->route('adhoc-document.show', ['uuid' => $document->uuid]);
        } catch (Exception $e) {
            Log::error('Reject error: ' . $e->getMessage());
            Alert::error('Gagal Menolak Dokumen', $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Button 1: Download Clean Signed Document PDF
     */
    public function downloadSigned(string $uuid)
    {
        $document = AdhocDocument::where('uuid', $uuid)->firstOrFail();

        $filePath = $document->signed_file_path ?: $document->original_file_path;
        $fullPath = storage_path('app/public/' . $filePath);

        if (!file_exists($fullPath)) {
            abort(404, 'File dokumen tidak ditemukan.');
        }

        $downloadName = 'Signed_' . pathinfo($document->original_file_name, PATHINFO_FILENAME) . '.pdf';
        return response()->download($fullPath, $downloadName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Button 2: On-Demand Certificate of Completion (Audit History PDF)
     */
    public function downloadAuditHistory(string $uuid)
    {
        $document = AdhocDocument::where('uuid', $uuid)->firstOrFail();

        try {
            $pdfContent = $this->signerService->generateAuditCertificatePdf($document);
            $fileName = 'Certificate_of_Completion_' . $document->uuid . '.pdf';

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]);
        } catch (Exception $e) {
            Log::error('Audit certificate generation failed: ' . $e->getMessage());
            Alert::error('Gagal Mengunduh Sertifikat', $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Secure PDF Streaming for Browser Canvas Viewers (prevents direct unauthorized file access)
     */
    public function viewPdf(string $uuid)
    {
        $document = AdhocDocument::where('uuid', $uuid)->firstOrFail();

        // Security: user must be authenticated and either creator, signer, or admin
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }
        $isSigner = $document->signers()->where('user_id', $user->id)->exists();
        $isAdmin = $user->hasRole('ADM');

        if ($document->created_by !== $user->id && !$isSigner && !$isAdmin) {
            abort(403, 'Unauthorized access to document.');
        }

        $filePath = $document->signed_file_path ?: $document->original_file_path;
        $fullPath = storage_path('app/public/' . $filePath);

        if (!file_exists($fullPath)) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
        ]);
    }
}
