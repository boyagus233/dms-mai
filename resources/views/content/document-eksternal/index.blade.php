@extends('layouts/contentNavbarLayout')

@section('title', 'Document Eksternal - Form')

@section('styles')
  @vite('resources/css/datatables.css')

@endsection

@section('content')
<div class="row">
  <div class="card">
    <div class="card-header border-bottom">

        <h5 class="mb-0">Filter</h5>
        <div class="d-flex justify-content-between align-items-center row pt-4 gap-4 gap-md-0">
          <div class="col-md-4 eksternal_type">
            <input type="hidden" id="document_eksternal_type" name="document_eksternal_type" value="{{ $eksternaltype }}">
            <select name="eksternal_type" id="eksternal_type" class="form-select form-select-sm">
              <option value="ALL">All</option>
                @foreach($documentEksternalType as $item)
                  <option value="{{ $item->id }}" data-code="{{ $item->code }}" data-name="{{ $item->name }}"
                    {{ $item->code == $eksternaltype ? 'selected' : '' }}>
                    {{ $item->name }}
                  </option>
                @endforeach
            </select>
          </div>
          <div class="col-md-4 status">
            <select name="status" id="status" class="form-select form-select-sm">
              <option value="ALL">All</option>
              <option value="ACTIVE">Active</option>
              <option value="OBSOLETE">Obsolete</option>
            </select>
          </div>
          <div class="col-md-4 btn_search">
            <button type="button" id="btn-refresh" name="btn-refresh" class="btn btn-primary">Search</button>
          </div>
        </div>


    </div>
    <div class="d-flex gap-4 gap-md-0 justify-content-end">
      @if ($isDocControl === true)
        <a href="{{ route('document-eksternal.create') }}" class="btn btn-primary">Add Document</a>
      @endif
      </div>
    <div class="table-responsive">
      <table id="document-eksternal-table" class="table table-striped table-bordered datatable-responsive">
        <thead>
          <tr>

              <th width="5%">Action</th>

            <th>Document Eksternal</th>
            <th>Document No</th>
            <th>Document Name</th>
            <th>Revisi</th>
            <th>Status</th>
            <th>File Name</th>
            <th>Download</th>
            <th>Created At</th>
          </tr>
        </thead>

      </table>

      <!-- /upload modal -->

    </div>
  </div>
</div>

@endsection

@section('scripts')
  @vite('resources/js/datatables.js')

  <script type="module">
    $(document).ready(function() {

      getData();
    });
  </script>
  <script>
    function getData() {
      $('#document-eksternal-table').DataTable().clear().destroy();
      let document_eksternal_type = ($('#document_eksternal_type').val() === 'undefined') ? 'ALL' : $('#document_eksternal_type').val();
      let requestTable = $('#document-eksternal-table').DataTable({
        //processing: true,
        //serverSide: true,
          ajax: {
              url: '{{ route("document-eksternal.data") }}',
              type: 'GET',
              data: function(d) {
                  console.log("Sending status:", $('#status').val());
                  console.log("Sending type:", document_eksternal_type);
                  d.document_eksternal_type = document_eksternal_type;
                  d.status = $('#status').val();
              }
          },
          columns: [
              {
                data: 'id',
                render: function (data, type, row) {
                    //Only show edit icon if admin
                    if (!row.isDocControl) {
                        return '';
                    }

                    let url = "{{ route('document-eksternal.edit', ':id') }}";
                    url = url.replace(':id', row.id);

                    return `
                        <a href="${url}"
                          class="text-underline-hover"
                          data-bs-toggle="tooltip"
                          data-bs-placement="right"
                          title="View"><i class="ri-pencil-line me-1" style="color: rgb(64, 0, 255);"></i></a>`;
                }
              },
              { data: 'document_eksternal_type' },
              {
              data: 'id',
                  render: function (data, type, row) {
                    let url = "{{ route('document-eksternal.show', ':id') }}";
                    url = url.replace(':id', row.id);
                      return `<a href="${url}"
                                  class="text-underline-hover"
                                  data-bs-toggle="tooltip"
                                  data-bs-placement="right"
                                  title="View">${row.document_no}</a>`;
                  }
              },
              { data: 'document_name' },
              { data: 'revisi' },
              { data: 'status' },
              { data: 'file_name' },
              {
                data: 'id',
                render: function (data, type, row) {
                    //Only show Doenload icon if admin
                    if (!row.isDocEksternal) {
                        return '';
                    }

                    let url = "{{ route('document-eksternal.download', ':id') }}";
                    url = url.replace(':id', row.id);

                    return `
                        <a href="${url}"
                          class="btn btn-sm btn-primary"
                          title="Download"><i class="tf-icons ri-download-2-fill"></i></a>`;
                }
              },
              { data: 'created_at' },
          ],
          autoWidth: false, // disables automatic column width calculation
          responsive: true,
          scrollX: false,
          pageLength: 10,
          lengthMenu: [ [10, 25, 100], [5, 10, 25, 100] ]
      });
      requestTable.on('xhr', function () {
      const data = requestTable.ajax.json().data;

      if (data.length > 0 && data[0].isDocControl === false) {
              // hide action column (index 4)
              requestTable.column(0).visible(false);
          }
      if (data.length > 0 && data[0].isDocEksternal === false) {
            // hide action column (index 4)
            requestTable.column(7).visible(false);
        }
      });

    }
  </script>

  <!-- delete modal -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Attach event listener to modal
      const myModal = document.getElementById('deleteConfirmModal');
      if (myModal) {
          myModal.addEventListener('show.bs.modal', function () {
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
    document.getElementById('eksternal_type').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const id = option.value;
      const code = option.dataset.code;
      document.getElementById('document_eksternal_type').value = code;
    });

    $('#btn-refresh').on('click', function () {
        getData();
    });
  </script>
@endsection
