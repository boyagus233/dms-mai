@extends('layouts/contentNavbarLayout')

@section('title', 'Document Upload - Form')

@section('styles')
    @vite('resources/css/datatables.css')
@endsection

@section('content')
    <?php
    use App\Enums\DocumentStatus;
    use App\Enums\GroupProcess;
    ?>
    <div class="row">
        <div class="accordion" id="accordionExample">
            @if ($viewtype == 'create')
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse"
                            data-bs-target="#accordionTwo" aria-expanded="false" aria-controls="accordionTwo">
                            Book No Doc
                        </button>
                    </h2>
                    <div id="accordionTwo" class="accordion-collapse" aria-labelledby="headingTwo"
                        data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <div>
                                <h5 class="card-header mb-2">
                                    <div class="d-flex justify-content-end">
                                        <button class="btn btn-primary" id="btnAddRequestDoc">
                                            <i class="bi bi-plus-circle"></i> New Request
                                        </button>
                                    </div>
                                </h5>
                                <div class="table-responsive">
                                    <table id="document-request-table"
                                        class="table table-striped table-bordered document-request-table"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Document Number</th>
                                                <th>Document Type</th>
                                                <th>Proc</th>
                                                <th>Func</th>
                                                <th>Document Name</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                    </table>

                                    <!-- Add Request Document Modal -->
                                    <div class="modal fade" id="requestDocModal" tabindex="-1"
                                        aria-labelledby="requestDocModal" aria-hidden="true">
                                        <div class="modal-dialog modal-xl">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="requestDocModal">Request Document Number
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form id="requestDocForm">
                                                        @csrf
                                                        <input type="hidden" id="id" name="id" value="0">
                                                        <input type="hidden" id="document-type-code"
                                                            name="document-type-code" value="">
                                                        <input type="hidden" id="document-type-name"
                                                            name="document-type-name" value="">
                                                        <input type="hidden" id="document-classification-code"
                                                            name="document-classification-code" value="">
                                                        <input type="hidden" id="document-classification-name"
                                                            name="document-classification-name" value="">
                                                        <input type="hidden" id="wi-function-code" name="wi-function-code"
                                                            value="">
                                                        <input type="hidden" id="wi-function-name" name="wi-function-name"
                                                            value="">
                                                        <div class="row mb-3">
                                                            <label class="col-sm-3 col-form-label text-end"
                                                                for="management-process">Management Process (PROC)*</label>
                                                            <div class="col-sm-6">
                                                                <select name="management-process" id="management-process"
                                                                    class="form-select" required>
                                                                    <option value="">Select Management Process
                                                                    </option>
                                                                    @foreach ($grouped as $groupKey => $classifications)
                                                                        @php
                                                                            // convert string back to enum
                                                                            $groupEnum = GroupProcess::tryFrom(
                                                                                $groupKey,
                                                                            );
                                                                        @endphp
                                                                        <optgroup
                                                                            label="{{ $groupEnum }} - {{ $groupEnum?->label() }}">
                                                                            @foreach ($classifications as $classification)
                                                                                <option value="{{ $classification->id }}"
                                                                                    data-code="{{ $classification->code }}"
                                                                                    data-name="{{ $classification->name }}">
                                                                                    {{ $classification->code }} -
                                                                                    {{ $classification->name }}
                                                                                </option>
                                                                            @endforeach
                                                                        </optgroup>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-3 col-form-label text-end"
                                                                for="document-type">Document Type*</label>
                                                            <div class="col-sm-6">
                                                                <select name="document-type" id="document-type"
                                                                    class="form-select" required>
                                                                    <option value="">Select Document Type</option>
                                                                    @foreach ($documentType as $item)
                                                                        <option value="{{ $item->id }}"
                                                                            data-code="{{ $item->code }}"
                                                                            data-name="{{ $item->document_type }}">
                                                                            {{ $item->document_type }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3 d-none" id="funcRow">
                                                            <label class="col-sm-3 col-form-label text-end">
                                                                Function</label>
                                                            <div class="col-sm-6">
                                                                <select name="wi-function" id="wi-function"
                                                                    class="form-select">
                                                                    <option value="">Select Function</option>
                                                                    @foreach ($documentFunction as $item)
                                                                        <option value="{{ $item->id }}"
                                                                            data-code="{{ $item->code }}"
                                                                            data-name="{{ $item->name }}">
                                                                            {{ $item->code }} - {{ $item->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3 d-none" id="parentRow">
                                                            <label class="col-sm-3 col-form-label text-end">Choose Parent
                                                                Number</label>
                                                            <div class="col-sm-6">
                                                                <input type="hidden" id="document-type-parent"
                                                                    name="document-type-parent" value="">
                                                                <input type="hidden" id="document-type-code-parent"
                                                                    name="document-type-code-parent" value="">
                                                                <input type="hidden" id="document-function-code-parent"
                                                                    name="document-function-code-parent" value="">
                                                                <select name="parent-number" id="parent-number"
                                                                    class="form-select">
                                                                    <option value="">Select Parent number</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-3 col-form-label text-end"
                                                                for="name">Document Name*</label>
                                                            <div class="col-sm-6">
                                                                <input type="text" class="form-control" id="name"
                                                                    name="name" placeholder="document name"
                                                                    required="required" />
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <label class="col-sm-3 col-form-label text-end"
                                                                for="notes">Notes</label>
                                                            <div class="col-sm-6">
                                                                <textarea rows="3" class="form-control" id="notes" name="notes"
                                                                    placeholder="Referensi, catatan singkat, link, dsb"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <div class="col-sm-3">
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <button type="button" class="btn btn-primary"
                                                                    id="btnCancel">Cancel</button>
                                                                <button type="submit"
                                                                    class="btn btn-success">Submit</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /delete modal -->
                                    <div id="deleteConfirmModal" class="modal fade bs-example-modal-sm" tabindex="-1"
                                        role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title" id="myModalLabel2"><i
                                                            class="fa fa-times-circle fa-lg red"></i> <span>Delete</span>
                                                    </h4>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close"><span aria-hidden="true">×</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete this item?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-light"
                                                        data-dismiss="modal">Cancel</button>
                                                    <a href="#" type="button"
                                                        class="btn btn-sm btn-danger">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /delete modal -->

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="accordion-item active">
                <h2 class="accordion-header" id="headingOne">
                    <button type="button" class="accordion-button" data-bs-toggle="collapse"
                        data-bs-target="#accordionOne" aria-expanded="true" aria-controls="accordionOne">
                        @if ($viewtype == 'create')
                            <b>Document List</b>
                        @else
                            <b>Required Review Document List</b>
                        @endif
                    </button>
                </h2>
                <div id="accordionOne" class="accordion-collapse collapse show" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <h5 class="card-header mb-2">
                            <div class="d-flex justify-content-end">
                                @if ($viewtype == 'create' && $issadddocument === true)
                                    <a href="{{ route('document-upload.create', ['viewtype' => 'create']) }}"
                                        class="btn btn-primary">Add Document</a>
                                @endif
                            </div>
                        </h5>
                        <div class="table-responsive">
                            <table id="datatable-responsive"
                                class="table table-striped table-bordered datatable-responsive">
                                <thead>
                                    <tr>
                                        <th width="8%"> Action </th>
                                        <th>Document No</th>
                                        <th>Document Name</th>
                                        <th>Departement</th>
                                        <th>Departement Distibution</th>
                                        <th>Revisi</th>
                                        <th>Creator</th>
                                        <th>Checker</th>
                                        <th>Approver</th>
                                        @if ($viewtype == 'required')
                                            <th>Document Type</th>
                                        @endif
                                        <th>Document Status</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    @if (!empty($documentUploads))
                                        @foreach ($documentUploads as $document)
                                            @php
                                                $statusLabel = DocumentStatus::from(
                                                    $document->document_status,
                                                )->label();
                                                //if ($document->is_obsolete===1 && $document->document_status != 'APPROVED') {
                                                //  $statusLabel = 'Obsolete-' . $statusLabel;
                                                //}
                                            @endphp
                                            <tr>
                                                <td width="10%">
                                                    @if (
                                                        $document->document_status == 'APPROVED' &&
                                                            $document->is_obsolete == 0 &&
                                                            $document->is_processing_revise_obsolete == 0)
                                                        <a href="{{ route('document-upload.edit', [$document->id, 'action' => 'revise', 'viewtype' => $viewtype]) }}"
                                                            class="btn-sm action-btn" data-bs-placement="top"
                                                            data-bs-toggle="tooltip" title="Revise Document"><i
                                                                class="ri-edit-2-fill me-2"
                                                                style="color: rgb(64, 0, 255);"></i></a>
                                                        <a href="{{ route('document-upload.edit', [$document->id, 'action' => 'obsolete', 'viewtype' => $viewtype]) }}"
                                                            class="btn-sm action-btn" data-bs-placement="top"
                                                            data-bs-toggle="tooltip" title="Obsolete Document"><i
                                                                class="ri-archive-2-fill me-2"
                                                                style="color: rgb(154, 43, 43);"></i></a>
                                                    @endif
                                                    @if (
                                                        ($document->document_status == 'CREATED' ||
                                                            $document->document_status == 'EDIT' ||
                                                            $document->document_status == 'REVISE' ||
                                                            $document->document_status == 'REJECT_BY_DC' ||
                                                            $document->document_status == 'REJECT_BY_CHECKER' ||
                                                            $document->document_status == 'REJECT_BY_APPROVER') &&
                                                            $document->is_stamp_by_creator == false)
                                                        <a href="{{ route('document-upload.edit', [$document->id, 'action' => 'edit', 'viewtype' => $viewtype]) }}"
                                                            class="btn-sm action-btn" data-bs-placement="top"
                                                            data-bs-toggle="tooltip" title="Edit Document"><i
                                                                class="ri-pencil-line me-2"
                                                                style="color: rgb(64, 0, 255);"></i></a>
                                                    @endif
                                                    @if (
                                                        ($document->document_status == 'CREATED' ||
                                                            $document->document_status == 'EDIT' ||
                                                            $document->document_status == 'REVISE') &&
                                                            $document->is_stamp_by_creator == false)
                                                        <a href="{{ route('document-upload.edit', [$document->id, 'action' => 'stamp', 'viewtype' => $viewtype]) }}"
                                                            class="btn-sm action-btn" data-bs-placement="top"
                                                            data-bs-toggle="tooltip" title="Stamp Document"><i
                                                                class="ri-album-fill me-2"></i></a>
                                                    @endif
                                                    @if (
                                                        ($document->document_status == 'CREATED' || $document->document_status == 'OBSOLETE') &&
                                                            $document->is_stamp_by_creator == true &&
                                                            $isdoccontrol === true)
                                                        <a href="{{ route('document-upload.edit', [$document->id, 'action' => 'doccontrol', 'viewtype' => $viewtype]) }}"
                                                            class="btn-sm action-btn" data-bs-placement="top"
                                                            data-bs-toggle="tooltip" title="Check Document"><i
                                                                class="ri-file-check-fill me-2"></i></a>
                                                    @endif
                                                    @if ($document->document_status == 'PASS_BY_DC' && $ischecker === true)
                                                        <a href="{{ route('document-upload.edit', [$document->id, 'action' => 'check', 'viewtype' => $viewtype]) }}"
                                                            class="btn-sm action-btn" data-bs-placement="top"
                                                            data-bs-toggle="tooltip" title="Approve Document"><i
                                                                class="ri-checkbox-circle-line me-2"></i></a>
                                                    @endif
                                                    @if ($document->document_status == 'CHECKED' && $isapprover === true)
                                                        <a href="{{ route('document-upload.edit', [$document->id, 'action' => 'approve', 'viewtype' => $viewtype]) }}"
                                                            class="btn-sm action-btn" data-bs-placement="top"
                                                            data-bs-toggle="tooltip" title="Approve Document"><i
                                                                class="ri-checkbox-circle-line me-2"></i></a>
                                                    @endif
                                                    @if ($viewtype == 'create' && $document->created_by === $userId && $document->is_approved === 0)
                                                        <a href="{{ route('document-upload.destroy', [$document->id, 'viewtype' => $viewtype]) }}"
                                                            class="btn-sm action-btn" data-bs-placement="top"
                                                            data-bs-toggle="tooltip" title="Delete"
                                                            data-confirm-delete="true"><i
                                                                class="ri-delete-bin-6-line me-2"
                                                                style="color: red;"></i></a>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('document-upload.view', [$document->id, 'viewtype' => $viewtype]) }}"
                                                        class="text-underline-hover" data-bs-placement="right"
                                                        data-bs-toggle="tooltip"
                                                        title="View">{{ $document->document_no }}</a>
                                                </td>
                                                <td>{{ $document->document_name }}</td>
                                                <td>{{ $document->departement_name }}</td>
                                                <td>{{ $document->distribution_departement }}</td>
                                                <td>
                                                    {{ $document->rev_no }}
                                                </td>
                                                <td>{{ $document->creator_name }}</td>
                                                <td>{{ $document->checker_name }}</td>
                                                <td>{{ $document->approver_name }}</td>
                                                @if ($viewtype == 'required')
                                                    <td>
                                                        @if ($document->rev_no == 0 && $document->is_obsolete === 0)
                                                            <span class="badge s-NEW-REGISTRASI">New Registrasi</span>
                                                        @elseif ($document->rev_no > 0 && $document->is_obsolete === 0)
                                                            <span class="badge s-NEW-REVISE">Revise</span>
                                                        @elseif ($document->is_obsolete === 1)
                                                            <span class="badge s-NEW-OBSOLETE">Obsollete</span>
                                                        @endif
                                                    </td>
                                                @endif
                                                <td>
                                                    @if ($document->document_status == 'PASS_BY_DC')
                                                        <span class="badge s-PASS-BY-DC">{{ $statusLabel }}</span>
                                                    @elseif (
                                                        $document->document_status == 'REJECT_BY_DC' ||
                                                            $document->document_status == 'REJECT_BY_CHECKER' ||
                                                            $document->document_status == 'REJECT_BY_APPROVER')
                                                        <span class="badge s-REJECTED">{{ $statusLabel }}</span>
                                                    @elseif ($document->document_status == 'EDIT')
                                                        <span class="badge s-CREATED">{{ $statusLabel }}</span>
                                                    @elseif ($document->document_status == 'OBSOLETE')
                                                        <span class="badge s-OBSOLETED">{{ $statusLabel }}</span>
                                                    @else
                                                        <span
                                                            class="badge s-{{ $document->document_status }}">{{ $statusLabel }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                            <!-- /upload modal -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    @vite('resources/js/datatables.js')

    <script>
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            // Attach event listener to modal
            const myModal = document.getElementById('myModal');

            if (myModal) {
                myModal.addEventListener('shown.bs.modal', function() {
                    // Call your function here
                    myCustomFunction();
                });
            }

            // Your custom function
            function myCustomFunction() {
                alert('dfdfdfd')
                console.log('Modal is now shown!');
                // You can do AJAX calls, DOM updates, etc.
            }
        });
    </script>

    <!-- delete modal -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Attach event listener to modal
            const myModal = document.getElementById('deleteConfirmModal');
            if (myModal) {
                myModal.addEventListener('show.bs.modal', function() {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-bs-id');

                    var modal = $(this)
                    modal.find('.modal-footer a').attr('href', '/employee/delete/' + id);
                });
            }
        });
    </script>
    <!-- /delete modal -->

    <script type="module">
        $(document).ready(function() {
            let requestTable = $('#document-request-table').DataTable({
                ajax: '{{ route('document-request.data') }}',
                columns: [{
                        data: 'id',
                        render: function(data, type, row) {
                            return `<a href="#"
                                  class="text-underline-hover"
                                  data-bs-toggle="tooltip"
                                  data-bs-placement="right"
                                  title="View">${row.document_no}</a>`;
                        }
                    },
                    {
                        data: 'document_type_code'
                    },
                    {
                        data: 'document_classification_code'
                    },
                    {
                        data: 'document_function_code'
                    },
                    {
                        data: 'document_name'
                    },
                    {
                        data: 'is_used',
                        render: function(data, type, row) {
                            if (row.is_used === 0) {
                                return `<span class="badge s-AVAILABLE">${row.document_status}</span>`;
                            } else {
                                return `<span class="badge s-BOOKED">${row.document_status}</span>`;
                            }
                        }
                    },
                ],
                autoWidth: false, // disables automatic column width calculation
                responsive: true,
                scrollX: false,
                pageLength: 5,
                lengthMenu: [
                    [5, 10, 25, 100],
                    [5, 10, 25, 100]
                ]
            })

            const tableEl = $('#datatable-responsive');

            if (!$.fn.DataTable.isDataTable(tableEl)) {
                tableEl.DataTable({
                    pageLength: 5,
                    lengthMenu: [
                        [5, 10, 25, 100],
                        [5, 10, 25, 100]
                    ],
                });
            }

            // Show modal when clicking "New User"
            $('#btnAddRequestDoc').on('click', function() {
                $('#requestDocForm')[0].reset();
                $('#requestDocModal').modal('show');
            });
            // Cancel button click
            $('#btnCancel').on('click', function() {
                $('#requestDocForm')[0].reset(); // clear form fields
                $('#requestDocModal').modal('hide'); // hide modal
            });

            // Handle save (AJAX)
            $('#requestDocForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: '/document-request', // resource route POST /document-request
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(res) {
                        $('#requestDocModal').modal('hide');
                        requestTable.ajax.reload(); // refresh table
                        //alert(res.message);
                        Swal.fire({
                            icon: res.icon,
                            title: 'Info',
                            text: res.message
                        });
                    },
                    error: function(err) {
                        if (err.status === 422) {
                            let errors = err.responseJSON.errors;
                            let messages = Object.values(errors).map(e => e.join(', ')).join(
                                '\n');
                            alert('Validation error:\n' + messages);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res.message
                            });
                        } else if (err.status === 500) {
                            //alert('Server error: ' + err.responseJSON.message);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: err.responseJSON.message
                            });
                        } else {
                            //alert('Something went wrong!');
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Something went wrong!'
                            });
                        }
                    }
                });
            });
        });
    </script>
    <script>
        document.getElementById('management-process').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const documentClassificationId = option.value;
            const documentClassificationCode = option.dataset.code;
            const documentclassificationname = option.dataset.name;
            const selectedtext = option.text;
            document.getElementById('document-classification-code').value = documentClassificationCode;
            document.getElementById('document-classification-name').value = documentclassificationname;
            const optionDocumentType = document.getElementById('document-type');
            const selectedDocumentTypeValue = optionDocumentType.value;
            const documentTypeCode = optionDocumentType.dataset.code;
            if (documentTypeCode === 'F') {
                loadParentNumber();
            }
        });

        document.getElementById('wi-function').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const id = option.value;
            const code = option.dataset.code;
            const name = option.dataset.name;
            document.getElementById('wi-function-code').value = code;
            document.getElementById('wi-function-name').value = name;
        });

        document.getElementById('parent-number').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const parent_no = option.value;
            const id = option.dataset.type;
            const code = (option.dataset.typecode == 'null') ? "" : option.dataset.typecode;
            const functioncode = (option.dataset.functioncode == 'null') ? "" : option.dataset.functioncode;
            document.getElementById('document-type-parent').value = id;
            document.getElementById('document-type-code-parent').value = code;
            document.getElementById('document-function-code-parent').value = functioncode;
        });

        document.getElementById('document-type').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const id = option.value;
            const code = option.dataset.code;
            const name = option.dataset.name;
            document.getElementById('document-type-code').value = code;
            document.getElementById('document-type-name').value = name;
            const funcRow = document.getElementById('funcRow');
            const parentRow = document.getElementById('parentRow');

            // WI, STD, OPL : butuh function (tampilkan WI Function)
            if (code === 'WI' || code === 'STD' || code === 'OPL') {
                funcRow.classList.remove('d-none');
                parentRow.classList.add('d-none');
            }
            // F : butuh parent number (tampilkan Choose Parent Number)
            else if (code === 'F') {
                funcRow.classList.add('d-none');
                parentRow.classList.remove('d-none');
                loadParentNumber();
            }
            // P, MB, dan lainnya : tidak butuh function & parent
            else {
                funcRow.classList.add('d-none');
                parentRow.classList.add('d-none');
            }
        });

        function loadParentNumber() {
            const parentnumberSel = document.getElementById('parent-number');
            const dataclassification = document.getElementById('management-process').value;

            // Clear current options
            parentnumberSel.innerHTML = '<option value="">-- Loading... --</option>';

            fetch(`/getParentnumber/${dataclassification}`)
                .then(response => response.json())
                .then(data => {
                    parentnumberSel.innerHTML = '<option value="">-- Select Parent number --</option>';
                    data.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.document_parent_no;
                        option.text = sub.document_parent_no;

                        option.setAttribute('data-type', sub.document_type);
                        option.setAttribute('data-typecode', sub.document_type_code);
                        option.setAttribute('data-functioncode', sub.document_function_code);
                        parentnumberSel.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading subcategories:', error);
                    parentnumberSel.innerHTML = '<option value="">-- Error loading --</option>';
                });
        }
    </script>
@endsection
