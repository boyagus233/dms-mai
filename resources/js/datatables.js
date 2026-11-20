import $ from 'jquery';
window.$ = window.jQuery = $;

// DataTables core + Bootstrap 5 integration
import 'datatables.net';
import 'datatables.net-bs5';
import 'datatables.net-responsive';
import 'datatables.net-responsive-bs5';
import 'datatables.net-scroller';
import 'datatables.net-scroller-bs5';

// Initialize table
$(document).ready(function () {
  $('#datatable-responsive').DataTable({
    responsive: true,
    scroller: false,
    autoWidth: false
  });
});
