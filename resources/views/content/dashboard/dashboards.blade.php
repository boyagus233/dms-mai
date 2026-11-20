@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard')

@section('styles')
  <style>
    /* Ensure the dropdown aligns to the right
    .dropdown-menu-end {
      right: 0 !important;
      left: auto !important;
      transform: translateX(0);
    }

    .dropdown-overlap-left {
      right: 5% !important;
      left: auto !important;
    }*/

    /* Optional styling for list items
    #listdocument .dropdown-item {
      display: flex;
      flex-direction: column;
      white-space: normal;
    }

    #listdocument .message {
      font-size: 0.875rem;
      color: #888;
    }*/

    /* Limit height if many documents, make scrollable
    #listdocument ul {
      max-height: 300px;
      overflow-y: auto;
      padding: 0;
      margin: 0;
    }*/
  </style>

  <style>
    /* ================================
      THEME TOKENS
      ================================ */
    :root{
      --bg:#f8fafc; --text:#0f172a; --muted:#64748b;
      --radius:12px; --shadow:0 1px 2px rgba(0,0,0,.06), 0 12px 24px -16px rgba(2,6,23,.12);

      /* brand gradients untuk KPI */
      --indigo:#4f46e5; --indigo-700:#4338ca;
      --emerald:#10b981; --emerald-700:#059669;
      --sky:#0ea5e9; --sky-700:#0369a1;
      --amber:#f59e0b; --amber-700:#b45309;
      --rose:#f43f5e; --rose-700:#be123c;
      --violet:#8b5cf6; --violet-700:#6d28d9;

      /* chart height */
      --h-lg:220px; --h-md:170px; --h-sm:130px;

      /* calendar colors */
      --pill-dead-bg:#fee2e2; --pill-dead-fg:#991b1b;
      --pill-rev-bg:#dbeafe;  --pill-rev-fg:#1e3a8a;
    }

    /* ================================
      BASE
      ================================ */
    html,body{
      background:var(--bg);
      color:var(--text);
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Helvetica,Arial;
    }
    .wrap{max-width:1200px; margin:0 auto; padding:10px 12px}
    .text-11{font-size:11px}
    .metric{text-shadow:0 1px 8px rgba(0,0,0,.08)}
    .chip{
      display:inline-flex; gap:.35rem; align-items:center;
      border:1px solid #e5e7eb; background:#fff;
      padding:.2rem .5rem; border-radius:999px; font-size:11px
    }
    .card{
      background:#fff; border:1px solid #e5e7eb;
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      padding:10px;
      overflow:hidden; /* cegah konten memanjang kartu */
    }

    /* ================================
      KPI CARDS
      ================================ */
    .kpi{color:#fff; padding:10px; border-radius:var(--radius); box-shadow:var(--shadow)}
    .kpi .title{font-size:12px; opacity:.9}
    .kpi .metric{font-size:22px; font-weight:800}
    .kpi .delta{font-size:11px; opacity:.95}
    .card--indigo{background:linear-gradient(135deg,var(--indigo),var(--indigo-700))}
    .card--emerald{background:linear-gradient(135deg,var(--emerald),var(--emerald-700))}
    .card--sky{background:linear-gradient(135deg,var(--sky),var(--sky-700))}
    .card--amber{background:linear-gradient(135deg,var(--amber),var(--amber-700))}
    .card--rose{background:linear-gradient(135deg,var(--rose),var(--rose-700))}
    .card--violet{background:linear-gradient(135deg,var(--violet),var(--violet-700))}

    /* ================================
      GRID UTAMA
      ================================ */
    .g4{display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px}
    .g3{display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px}
    .g2{display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px}
    .g1{display:grid; grid-template-columns:1fr; gap:8px}

    .section{margin-bottom:10px}
    .section--kpi .g4 + .g4{margin-top:8px}
    .section--graphs .g4 + .g4{margin-top:8px}

    @media (max-width:1200px){
      .g4{
        grid-template-columns:none !important;
        grid-auto-flow:column;
        grid-auto-columns:minmax(260px,1fr);
        overflow-x:auto; overscroll-behavior-x:contain; -webkit-overflow-scrolling:touch;
      }
      .g4 > *{min-width:0}
      .g4::-webkit-scrollbar{height:8px}
      .g4::-webkit-scrollbar-thumb{background:#e5e7eb; border-radius:6px}

      .g3{
        grid-template-columns:none !important;
        grid-auto-flow:column;
        grid-auto-columns:minmax(300px,1fr);
        overflow-x:auto; overscroll-behavior-x:contain; -webkit-overflow-scrolling:touch;
      }
      .g3 > *{min-width:0}
    }

    /* ================================
      TABEL KECIL
      ================================ */
    .table-sm th,.table-sm td{
      padding:.4rem .55rem;
      border-bottom:1px solid #eef2f7;
      font-size:.84rem
    }
    .table-sm thead{background:#f8fafc}

    /* ================================
      CHART HEIGHT HELPERS
      ================================ */
    .h-lg{height:var(--h-lg) !important; max-height:var(--h-lg) !important;}
    .h-md{height:var(--h-md) !important; max-height:var(--h-md) !important;}
    .h-sm{height:var(--h-sm) !important; max-height:var(--h-sm) !important;}
    .card canvas{display:block; width:100% !important;}

    /* ================================
      KALENDER — COMPACT & DETAILED
      ================================ */
    .cal-grid{
      display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:.2rem;
    }

    /* Compact cell (kartu kecil) */

    .cal-date{font-size:10px; font-weight:700; line-height:1}
    .dot-row{
      position:absolute; left:.35rem; right:.35rem; bottom:.28rem;
      display:flex; gap:4px; align-items:center; flex-wrap:nowrap; overflow:hidden;
    }
    .dot{width:6px; height:6px; border-radius:999px; flex:0 0 6px}
    .dot-dead{background:var(--pill-dead-fg); opacity:.9}
    .dot-rev {background:var(--pill-rev-fg);  opacity:.9}
    .dot-more{
      margin-left:4px; padding:0 .25rem; height:14px; line-height:14px;
      font-size:9px; color:#475569; background:#f1f5f9; border-radius:999px; flex:0 0 auto
    }

    /* Detailed cell (modal besar) */
    .cal-cell.detailed{
      min-height:150px;
      border:1px solid #e5e7eb; border-radius:10px;
      padding:.5rem; background:#fff;
    }
    .cal-pill{
      display:block; border-radius:10px;
      padding:.28rem .4rem; font-size:12px; line-height:1.1
    }
    .cal-pill .no{display:block; font-weight:700}
    .cal-pill .nm{display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis}

    /* Modal */
    .modal{
      position:fixed; inset:0; display:none; place-items:center;
      background:rgba(2,6,23,.45); z-index:50
    }
    .modal.open{display:grid}
    .modal-card{
      width:min(800px,92vw); max-height:92vh; overflow:auto;
      background:#fff; border:1px solid #e5e7eb; border-radius:16px;
      padding:16px; box-shadow:0 30px 80px rgba(0,0,0,.35)
    }

    /* ================================
      MISC
      ================================ */
    .delta-up{color:#059669}
    .delta-down{color:#dc2626}

    /* Compact cell (harus ada agar dot-row bisa absolute & kotak) */
    .cal-cell.compact{
      position: relative;
      aspect-ratio: 1 / 1;                 /* bikin kotak */
      border:1px solid #e5e7eb; border-radius:10px;
      background:#fff; padding:.25rem .35rem;
      display:flex; flex-direction:column; align-items:flex-start; justify-content:flex-start;
      overflow:hidden;                      /* jaga badge +n tetap rapi */
      cursor: pointer;
    }

    .inline-div {
      display: inline-block; /* Makes divs display inline while retaining block properties */
      padding: 10px;
      border: 1px solid #ccc;
      margin: 5px;
    }

    .clickable {
      cursor: pointer;
    }
  </style>
@endsection

@section('vendor-style')

@endsection

@section('vendor-script')

@endsection

@section('page-script')
  @vite('resources/js/tailwind.js')
@endsection

@section('content')
<div class="row gy-6">
  <!-- Header -->
  <div class="flex items-center justify-between mb-2 gap-2">
    <div>
      <h1 class="text-xl font-bold m-0">Dashboard Control — DMS</h1>
      <p class="text-11" style="color:var(--muted)"></p>
    </div>
  </div>

  <!-- Filter -->
  <div class="card mb-2" style="padding:8px">
      <div class="g4">
        <div>
          <div class="text-11" style="color:var(--muted)">Departement</div>
          <select id="deptFilter" class="w-full text-sm border rounded-md px-2 py-1.5">
            <option value="0" selected>All Dept</option>
              @foreach($departement as $item)
                <option value="{{ $item->id }}" data-code="{{ $item->code }}">
                    {{ $item->code }} - {{ $item->name }}
                </option>
              @endforeach
          </select>
        </div>
        <div>
          <div class="text-11" style="color:var(--muted)">Doc Type</div>
          <select id="typeFilter" class="w-full text-sm border rounded-md px-2 py-1.5">
            <option value="ALL" selected>All Types</option>
            @foreach($documentType as $item)
              <option value="{{ $item->code }}" data-code="{{ $item->code }}" data-name="{{ $item->document_type }}">
                {{ $item->document_type }}
              </option>
            @endforeach
          </select>
        </div>
        <div style="display: none">
          @php
              $currentYear = date('Y');
              $previousYear = $currentYear - 1;
          @endphp
          <div class="text-11" style="color:var(--muted)">Tahun</div>
          <select id="yearFilter" class="w-full text-sm border rounded-md px-2 py-1.5">
            @foreach ($years as $year)
                <option value="{{ $year }}" {{ $year == $selectedYear ? 'selected' : '' }}>
                    {{ $year }}
                </option>
            @endforeach
          </select>
        </div>
        <div>
          <div class="text-11" style="color:var(--muted)"></br>
          </div>
            <button id="refreshBtn" class="px-3 py-1.5 rounded-md text-white" style="background:var(--indigo)">Refresh</button>
            <a href="{{ route('document-template.list-download') }}" class="px-3 py-1.5 rounded-md text-white" role="button" style="background:var(--emerald)">Document Template</a>
          </div>
        </div>

      </div>

    <div id="filterChips" class="flex flex-wrap gap-1.5 mt-2"></div>
  </div>

  <!-- KPI ROW 1 -->
  <div class="g4 mb-1.5">
    <div id="mainCardNeedApproval" class="kpi card--indigo clickable">
      <div class="title">Need My Approval</div>
      <div class="flex items-end gap-2 mt-0.5">
        <div id="cardNeedApproval" class="metric">{{ $stats['totalDocumentToApprove'] }}</div>
        <div id="deltaNeedApproval" class="delta"></div>
      </div>
    </div>
    <div id="mainCardBooked" class="kpi card--amber clickable">
      <div class="title">Booking ≤ 3 hari</div>
      <div class="flex items-end gap-2 mt-0.5">
        <div id="cardBooked" class="metric">{{ $stats['totalBookingGt3days'] }}</div>
        <div id="deltaBooked" class="delta"></div>
      </div>
    </div>
    <div id="mainCardActive" class="kpi card--emerald clickable">
      <div class="title">Dokumen Active</div>
      <div class="flex items-end gap-2 mt-0.5">
        <div id="cardActive" class="metric">{{ $stats['totalDocumentActive'] }}</div>
        <div id="deltaActive" class="delta"></div>
      </div>
    </div>
    <div id="mainCardObsolete" class="kpi card--rose clickable">
      <div class="title">Dokumen Obsolete</div>
      <div class="flex items-end gap-2 mt-0.5">
        <div id="cardObsolete" class="metric">{{ $stats['totalDocumentObsolete'] }}</div>
        <div id="deltaObsolete" class="delta"></div>
      </div>
    </div>
  </div>

  <!-- KPI ROW 2 -->
  <div class="g4 mb-2">
    <div id="mainCardTotal" class="kpi card--violet clickable">
      <div class="title">Total Dokumen</div>
      <div class="flex items-end gap-2 mt-0.5">
        <div id="cardTotal" class="metric">{{ $stats['totalDocument'] }}</div>
        <div id="deltaTotal" class="delta"></div></div>
    </div>
    <div id="mainCardProc" class="kpi card--sky clickable">
      <div class="title">Procedure (Active)</div>
      <div class="flex items-end gap-2 mt-0.5">
        <div id="cardProc" class="metric">{{ $stats['totalDocumentProcedure'] }}</div>
      </div>
    </div>
    <div id="mainCardWI" class="kpi card--indigo clickable">
      <div class="title">Work Instruction (Active)</div>
      <div class="flex items-end gap-2 mt-0.5">
        <div id="cardWI" class="metric">{{ $stats['totalDocumentWI'] }}</div>
      </div>
    </div>
    <div id="mainCardForm" class="kpi card--emerald clickable">
      <div class="title">Form (Active)</div>
      <div class="flex items-end gap-2 mt-0.5">
        <div id="cardForm" class="metric">{{ $stats['totalDocumentForm'] }}</div>
      </div>
    </div>
  </div>

  <!-- GRAPH ROW A <canvas id="deptBarChart" class="h-lg"></canvas> <canvas id="trendChart" class="h-lg"></canvas>-->
  <div class="g4">
    <div class="card">
      <div class="text-sm font-semibold">Dokumen & Obsolete per Dept</div>
      <div id="deptBarChartContainer"></div>
    </div>
    <div class="card">
      <div class="text-sm font-semibold">Trend 12 Bulan</div>
      <div id="trendChartContainer"></div>
    </div>
    <div class="card">
      <div class="text-sm font-semibold">Komposisi Dokumen</div>
      <div id="compositionChartContainer"></div>
      <div class="grid grid-cols-2 gap-1 mt-1 text-sm">
        <div class="p-2 rounded-md" style="background:#f1f5f9;border:1px solid #e5e7eb"><div style="color:var(--muted)">Active</div><div id="metaActive" class="font-bold text-lg">—</div></div>
        <div class="p-2 rounded-md" style="background:#f1f5f9;border:1px solid #e5e7eb"><div style="color:var(--muted)">Obsolete</div><div id="metaObs" class="font-bold text-lg">—</div></div>
      </div>
    </div>
    <div class="card">
      <div class="text-sm font-semibold">Coverage by Type (Active)</div>
      <div id="coverageStackedContainer"></div>
    </div>
  </div>

  <!-- GRAPH ROW B -->
  <div class="g4 mt-2">
    <div class="card">
      <div class="text-sm font-semibold">Active & Obsollete doc eksternal</div>
      <div id="dokEksternalChartContainer"></div>
    </div>
    <div class="card">
      <div class="text-sm font-semibold">Rata-rata Approval per Departemen</div>
      <div id="avgApprovalDeptChartContainer"></div>
    </div>
    <div class="card">
      <div class="text-sm font-semibold">Ringkasan Aktivitas Pengguna</div>
      <div id="activityToday" class="text-sm mb-1"></div>
      <div id="activityFeed" class="max-h-40 overflow-auto space-y-1.5 text-sm"></div>
    </div>
    <div class="card">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm font-semibold">Kalender</div>
          <div class="text-11" style="color:var(--muted)">
            <span class="cal-pill" style="display:inline-block;background:var(--pill-dead-bg);color:var(--pill-dead-fg);padding:.15rem .4rem">Deadline</span>
            <span class="cal-pill" style="display:inline-block;background:var(--pill-rev-bg);color:var(--pill-rev-fg);padding:.15rem .4rem">Review</span>
          </div>
        </div>
        <div class="flex items-center gap-1.5">
          <input type="hidden" id="current-year" name="current-year">
          <input type="hidden" id="current-month" name="current-month">
          <button id="prevMonth" class="px-2 py-1 text-sm rounded-md border" style="border-color:#e5e7eb">Prev</button>
          <div id="calTitle" class="text-sm font-semibold w-20 text-center"></div>
          <button id="nextMonth" class="px-2 py-1 text-sm rounded-md border" style="border-color:#e5e7eb">Next</button>
        </div>
      </div>
      <div class="grid grid-cols-7 text-11 mt-1" style="color:var(--muted)">
        <div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div>
      </div>
      <div id="calGrid" class="cal-grid mt-1">

      </div>
    </div>
  </div>

</div>

<!-- Modal Kalender -->
<div id="calendarModal" class="modal" aria-hidden="true">
  <div class="modal-card">
    <div class="flex items-center justify-between mb-2">
      <div class="flex items-center gap-2">
        <button id="mPrev" class="px-2.5 py-1.5 text-sm rounded-md border" style="border-color:#e5e7eb">Prev</button>
        <div id="mCalTitle" class="text-sm font-semibold w-48 text-center"></div>
        <button id="mNext" class="px-2.5 py-1.5 text-sm rounded-md border" style="border-color:#e5e7eb">Next</button>
      </div>
      <button id="closeModal" class="px-3 py-1.5 text-sm rounded-md text-white" style="background:var(--indigo)">Close</button>
    </div>
    <div class="grid grid-cols-7 text-11 mb-1" style="color:var(--muted)">
      <div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div>
    </div>
    <div id="mCalGrid" class="cal-grid"></div>
  </div>
</div>

<div id="calTip" style="
  position:fixed;display:none;z-index:60;
  max-width:280px;padding:.5rem .6rem;border-radius:10px;
  background:#111827;color:#f9fafb;font-size:12px;line-height:1.25;
  box-shadow:0 10px 30px rgba(0,0,0,.35)">
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 <script type="module">
  $(document).ready(function() {
    const transdata = @json($transactiondata);
    const transdataeksternal = @json($transactiondataEksternal);
    const userActivityLogs = @json($userActivityLogs);

    buildDeptBarChart(transdata);
    buildTrendChart(transdata);
    buildCompositionChart(transdata);
    buildCoverageStacked(transdata);
    //buildTop10Chart(transdata);
    buildDokEksternalChart(transdataeksternal);
    buildAvgApprovalDeptChart(transdata);
    displayUserActivity(userActivityLogs);

    let now = new Date();
    let currentYear = now.getFullYear();
    let month = now.getMonth() + 1;
    renderCalendars(currentYear, month);

  });
 </script>
  <script type="module">
    $('#refreshBtn').on('click', function () {
        //const department = "{{ request('department', 'ALL') }}"; // or get from dropdown if dynamic
        const yearfilter = document.getElementById('yearFilter').value;
        const department = document.getElementById('deptFilter').value;
        const type = document.getElementById('typeFilter').value;

        $.ajax({
            url: "{{ route('dashboard.data') }}",
            method: 'GET',
            data: { department: department, tahun: yearfilter, type:type },
            beforeSend: function () {
                $('#refreshBtn').text('🔄 Refreshing data...');
            },
            success: function (response) {
                let stats = response.stats;
                let transdata = stats.chartData;
                let transdataeksternal = stats.documentEksternals;
                let userActivityLogs = stats.userActivityLogs;
                $('#cardBooked').text(stats.totalBookingGt3days);
                $('#cardTotal').text(stats.totalDocument);
                $('#cardProc').text(stats.totalDocumentProcedure);
                $('#cardWI').text(stats.totalDocumentWI);
                $('#cardForm').text(stats.totalDocumentForm);
                $('#cardActive').text(stats.totalDocumentActive);
                $('#cardObsolete').text(stats.totalDocumentObsolete);
                $('#cardNeedApproval').text(stats.totalDocumentToApprove);
                $('#department-label').text(response.department);
                  buildDeptBarChart(transdata);
                  buildTrendChart(transdata);
                  buildCompositionChart(transdata);
                  buildCoverageStacked(transdata);
                  //buildTop10Chart(transdata);
                  buildDokEksternalChart(transdataeksternal);
                  buildAvgApprovalDeptChart(transdata);
                  displayUserActivity(userActivityLogs);
                $('#refreshBtn').text('Refresh');
            },
            error: function () {
                alert('Failed to refresh dashboard.');
                $('#refreshBtn').text('Refresh');
            }
        });
    });
  </script>

  <script>
    let deptBarChart = null;
    let trendChart = null;
    let compositionChart = null;
    let coverageStacked = null;
    let top10Chart = null;
    let avgApprovalDeptChart = null;
    const monthNameId=(y,m)=> new Date(y,m,1).toLocaleString('id-ID',{month:'short', year:'numeric'});
    const longMonthNameId=(y,m)=> new Date(y,m,1).toLocaleString('id-ID',{month:'long', year:'numeric'});
    const addYears=(d,n)=>{ const x=new Date(d); x.setFullYear(x.getFullYear()+n); return x; };
    const addDays=(d,n)=>{ const x=new Date(d); x.setDate(x.getDate()+n); return x; };
    const SLA_BOOK_DAYS=3, IDEAL_APPROVAL_DAYS=2;
    const _tip = document.getElementById('calTip');

    function buildDeptBarChart(datas) {
      const grouped = {};
      datas.forEach(item => {
        const dept = item.dept;
        const type = item.documenttype;

        if (!grouped[dept]) {
          grouped[dept] = { documentActive: 0, obsolete: 0 };
        }

        if (type === 'documentActive' || type === 'obsolete') {
          grouped[dept][type]++;
        }
      });

      // Step 2: Extract labels and datasets
      const labels = Object.keys(grouped);
      const data1 = labels.map(dept => grouped[dept].documentActive);   // type = 'Document'
      const data2 = labels.map(dept => grouped[dept].obsolete);  // type = 'Obsolete'

      // Output (for testing)
      console.log('Labels:', labels);
      console.log('Data1 (P):', data1);
      console.log('Data2 (WI):', data2);

      // 🔄 Check for existing canvas and remove it
      const container = document.getElementById('deptBarChartContainer');
      const existingCanvas = container.querySelector('canvas');
      if (existingCanvas) {
        existingCanvas.remove(); // cleanly removes old canvas
      }

      const canvas = document.createElement('canvas');
      canvas.id = 'deptBarChart';
      canvas.classList.add('h-lg');
      // canvas.width = 268;
      // canvas.height = 200;
      // canvas.style.maxWidth = '100%';
      // canvas.style.height = 'auto';
      container.appendChild(canvas);

      const ctx = canvas.getContext('2d');
      deptBarChart = new Chart(ctx,{
        type:'bar',
        data:{
          labels:labels,
          datasets:[
                    {
                      label:'Dokumen',
                      data:data1,
                      borderRadius:8,
                      barPercentage:.6,
                      categoryPercentage:.6
                    },
                    {
                      label:'Obsolete',
                      data:data2,
                      borderRadius:8,
                      barPercentage:.6,
                      categoryPercentage:.6
                    }
                  ]
          },
          options: {
            responsive:true,
            maintainAspectRatio:false,
            animation:false,
            plugins:{
                      legend:{
                              position:'bottom',
                              labels:{boxWidth:10}
                            }
                    },
            scales:{y:{beginAtZero:true}},
            onClick: (event, elements, chart) => {
                if (elements.length > 0) {

                  const elementIndex = elements[0].index;
                  const datasetIndex = elements[0].datasetIndex;
                  const label = chart.data.labels[elementIndex];
                  const value = chart.data.datasets[datasetIndex].data[elementIndex];

                  // set parameters
                  const docType = datasetIndex == 0 ? "approve" : "obsolete";

                  // Build URL with multiple parameters
                  const params = new URLSearchParams({
                      viewtype: docType
                  });

                  redirectPage("document-master-list?", {
                      viewtype: docType
                  });
                  
                }
            }
          }
      });
    }

    function buildCompositionChart(datas) {
      const metaActive = document.getElementById('metaActive');
      const metaObs = document.getElementById('metaObs');
      const typeCounts = {documentActive: 0,obsolete: 0};
      datas.forEach(item => {
        const type = item.documenttype;
        if (!typeCounts[type]) {
          typeCounts[type] = 0;
        }
        typeCounts[type]++;
      });
      console.log(typeCounts); // Output: { Document: 10, Obsolete: 5 }
      metaActive.textContent=typeCounts.documentActive;
      metaObs.textContent=typeCounts.obsolete;
      const container = document.getElementById('compositionChartContainer');
      const existingCanvas = container.querySelector('canvas');
      if (existingCanvas) {
        existingCanvas.remove();
      }
      const canvas = document.createElement('canvas');
      canvas.id = 'compositionChart';
      canvas.classList.add('h-md');
      container.appendChild(canvas);
      const ctx = canvas.getContext('2d');
      compositionChart = new Chart(ctx,{
        type:'doughnut',
        data:{labels:['Active','Obsolete'],
              datasets:[{data:[typeCounts.documentActive, typeCounts.obsolete]}]
        },
        options:{
          responsive:true,
          maintainAspectRatio:false,
          animation:false,
          plugins:{legend:{position:'bottom'}},
          cutout:'60%',
            onClick: (event, elements, chart) => {
              if (elements.length > 0) {
                  const elementIndex = elements[0].index;
                  const datasetIndex = elements[0].datasetIndex;
                  const label = chart.data.labels[elementIndex];
                  const value = chart.data.datasets[datasetIndex].data[elementIndex];
                  // set parameters
                  const docType = label === "Active" ? "approve" : "obsolete";
                  redirectPage("document-master-list?", {
                      viewtype: docType
                  });
              }
            }
          }
      });
    }

    // function getLast12Months() {
    //   const months = [];
    //   const now = new Date();
    //   for (let i = 11; i >= 0; i--) {
    //     const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
    //     const label = d.toLocaleString('default', { month: 'short', year: 'numeric' });
    //     const key = d.toISOString().slice(0, 7); // 'YYYY-MM'
    //     months.push({ label, key });
    //   }
    //   return months;
    // }

    function buildTrendChart(datas) {
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      const countByMonthAndDocumentStatus = {
        documentActive: new Array(12).fill(0),
        obsolete: new Array(12).fill(0)
      };

      datas.forEach(item => {
        const date = new Date(item.createdAt);
        const monthIndex = date.getMonth(); // 0 (Jan) to 11 (Dec)
        const type = item.documenttype;
        if (type === 'documentActive' || type === 'obsolete') {
          countByMonthAndDocumentStatus[type][monthIndex]++;
        }
      });

      const container = document.getElementById('trendChartContainer');
      const existingCanvas = container.querySelector('canvas');
      if (existingCanvas) {
        existingCanvas.remove(); // cleanly removes old canvas
      }
      const canvas = document.createElement('canvas');
      canvas.id = 'trendChart';
      canvas.classList.add('h-lg');
      container.appendChild(canvas);

      const ctx = canvas.getContext('2d');
      trendChart = new Chart(ctx,{
        type:'line',
        data:{
          labels:months,
          datasets:[
            {label:'Active',data: countByMonthAndDocumentStatus.documentActive,tension:.25,pointRadius:2,fill:true},
            {label:'Obsolete',data: countByMonthAndDocumentStatus.obsolete,tension:.25,pointRadius:2,fill:true}
          ]
        },
        options:{
          responsive:true,
          maintainAspectRatio:false,
          animation:false,
          plugins:{legend:{position:'bottom'}},
          scales:{y:{beginAtZero:true}},
          onClick: (event, elements, chart) => {
              if (elements.length > 0) {
                  const elementIndex = elements[0].index;
                  const datasetIndex = elements[0].datasetIndex;
                  const label = chart.data.labels[elementIndex];
                  const value = chart.data.datasets[datasetIndex].data[elementIndex];
                  // set parameters
                  const docType = datasetIndex == 0 ? "approve" : "obsolete";
                  redirectPage("document-master-list?", {
                      viewtype: docType
                  });
              }
            }
        }});
    }

    function buildCoverageStacked(datas) {
      const grouped = {};

      datas.forEach(item => {
        const dept = item.dept;
        const type = item.document_type;

        if (!grouped[dept]) {
          grouped[dept] = { P: 0, F: 0, WI: 0 };
        }
        if (type === 'P' || type === 'F' || type === 'WI') {
          grouped[dept][type]++;
        }
      });
      const labels = Object.keys(grouped);
      const P = labels.map(dept => grouped[dept].P);
      const WI = labels.map(dept => grouped[dept].WI);
      const FORM = labels.map(dept => grouped[dept].F);

      const container = document.getElementById('coverageStackedContainer');
      const existingCanvas = container.querySelector('canvas');
      if (existingCanvas) {
        existingCanvas.remove(); // cleanly removes old canvas
      }
      const canvas = document.createElement('canvas');
      canvas.id = 'coverageStacked';
      canvas.classList.add('h-lg');
      container.appendChild(canvas);

      const ctx = canvas.getContext('2d');
      coverageStacked = new Chart(ctx, {
        type:'bar',
        data:{
          labels:labels,
          datasets:[
                    {label:'P',data:P,stack:'x',borderRadius:6,barPercentage:.6,categoryPercentage:.6},
                    {label:'WI',data:WI,stack:'x',borderRadius:6,barPercentage:.6,categoryPercentage:.6},
                    {label:'FORM',data:FORM,stack:'x',borderRadius:6,barPercentage:.6,categoryPercentage:.6}
                  ]
        },
        options:{
          responsive:true,
          maintainAspectRatio:false,
          animation:false,
          plugins:{legend:{position:'bottom'}},
          scales:{x:{stacked:true},y:{stacked:true,beginAtZero:true}},
          onClick: (event, elements, chart) => {
              if (elements.length > 0) {
                  redirectPage("document-upload?", {
                      viewtype: "create"
                  });
              }
            }
        }
      });
    }

    function buildTop10Chart(datas){
      const container = document.getElementById('top10ChartContainer');
      const existingCanvas = container.querySelector('canvas');
      if (existingCanvas) {
        existingCanvas.remove(); // cleanly removes old canvas
      }
      const canvas = document.createElement('canvas');
      canvas.id = 'top10Chart';
      canvas.classList.add('h-md');
      container.appendChild(canvas);
      const ctx = canvas.getContext('2d');
      const byOwner={};
      datas.forEach(d=>byOwner[d.owner]=(byOwner[d.owner]||0)+1);
      const items=Object.entries(byOwner).map(([owner,count])=>({owner,count})).sort((a,b)=>b.count-a.count).slice(0,10);
      top10Chart = new Chart(ctx,{type:'bar',data:{labels:items.map(i=>i.owner),datasets:[{label:'Jumlah',data:items.map(i=>i.count),borderRadius:6,barPercentage:.6,categoryPercentage:.6}]},
        options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true}}}});
    }

    function buildDokEksternalChart(datas) {
      const grouped = {};

      datas.forEach(item => {
        const type = item.type;
        const status = item.status;

        if (!grouped[type]) {
          grouped[type] = { ACTIVE: 0, OBSOLETE: 0 };
        }

        if (status === 'ACTIVE' || status === 'OBSOLETE') {
          grouped[type][status]++;
        }
      });

      // Step 2: Extract labels and datasets
      const labels = Object.keys(grouped);
      const data1 = labels.map(type => grouped[type].ACTIVE);   // type = 'Active'
      const data2 = labels.map(type => grouped[type].OBSOLETE);  // type = 'Obsolete'

      // Output (for testing)
      //console.log('Labels:', labels);
      //console.log('Data1 (P):', data1);
      //console.log('Data2 (WI):', data2);

      // 🔄 Check for existing canvas and remove it
      const container = document.getElementById('dokEksternalChartContainer');
      const existingCanvas = container.querySelector('canvas');
      if (existingCanvas) {
        existingCanvas.remove(); // cleanly removes old canvas
      }

      const canvas = document.createElement('canvas');
      canvas.id = 'dokEksternalBarChart';
      canvas.classList.add('h-lg');
      container.appendChild(canvas);

      const ctx = canvas.getContext('2d');
      deptBarChart = new Chart(ctx,{
        type:'bar',
        data:{
          labels:labels,
          datasets:[
                    {
                      label:'Active',
                      data:data1,
                      borderRadius:8,
                      barPercentage:.6,
                      categoryPercentage:.6
                    },
                    {
                      label:'Obsolete',
                      data:data2,
                      borderRadius:8,
                      barPercentage:.6,
                      categoryPercentage:.6
                    }
                  ]
          },
          options: {
            responsive:true,
            maintainAspectRatio:false,
            animation:false,
            plugins:{
                      legend:{
                              position:'bottom',
                              labels:{boxWidth:10}
                            }
                    },
            scales:{y:{beginAtZero:true}},
            onClick: (event, elements, chart) => {
              if (elements.length > 0) {
                  redirectPage("document-eksternal", {});
              }
            }
          }
      });
    }

    function buildAvgApprovalDeptChart(datas){
      const diffDays=(a,b)=> Math.round((new Date(a)-new Date(b))/(1000*60*60*24));
      const container = document.getElementById('avgApprovalDeptChartContainer');
      const existingCanvas = container.querySelector('canvas');
      if (existingCanvas) {
        existingCanvas.remove(); // cleanly removes old canvas
      }
      const canvas = document.createElement('canvas');
      canvas.id = 'avgApprovalDeptChart';
      canvas.classList.add('h-md');
      container.appendChild(canvas);
      const ctx = canvas.getContext('2d');
      const by={}; datas.forEach(d=>{ if(!d.updatedAt) return;
      const dd=Math.max(0,diffDays(d.updatedAt,d.createdAt)); (by[d.dept]=by[d.dept]||[]).push(dd);});
      const labels=Object.keys(by).sort();
      const avgs=labels.map(k=>{const a=by[k]; return a.length?Math.round(a.reduce((x,y)=>x+y,0)/a.length):0;});

      avgApprovalDeptChart = new Chart(ctx,{type:'bar',data:{labels, datasets:[{label:'Avg (hari)', data:avgs, borderRadius:6, barPercentage:.6, categoryPercentage:.6}]},
      options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{precision:0}}}}});
    }

    function computeAvgApprovalByDept(datas){
      const by={}; datas.forEach(d=>{ if(!d.updatedAt) return; const dd=Math.max(0,diffDays(d.updatedAt,d.createdAt)); (by[d.dept]=by[d.dept]||[]).push(dd);});
      const labels=Object.keys(by).sort(); const avgs=labels.map(k=>{const a=by[k]; return a.length?Math.round(a.reduce((x,y)=>x+y,0)/a.length):0;});
      return {labels,avgs};
    }


    function displayUserActivity(datas) {
      let today = new Date();
      let year = today.getFullYear();
      let month = String(today.getMonth() + 1).padStart(2, '0'); // months are 0–11
      let day = String(today.getDate()).padStart(2, '0');
      let formattedToday = `${year}-${month}-${day}`;

      const activityToday = document.getElementById('activityToday');
      const activityFeed = document.getElementById('activityFeed');
      //const today=datas.filter(a=>a.time.startsWith(todayStr));
      activityToday.innerHTML = `Hari ini ada <b>${datas.filter(a => a.action === 'CREATED').length}</b> upload dan <b>${datas.filter(a => a.action === 'APPROVED').length}</b> approve.`;
      activityFeed.innerHTML=datas.slice().sort((a,b)=>new Date(b.created_at)-new Date(a.created_at)).map(a=>`
        <div class="p-2 rounded-md" style="border:1px solid #e5e7eb;background:#fff">
          <span class="text-11" style="color:#64748b">${new Date(a.created_at).toLocaleString('id-ID')}</span>
          <div>${a.employee_name} <b>${a.action}</b> ${a.document_no}</div>
        </div>`).join('');
    }

    function renderCalendars(currentYear, month) {
      $.ajax({
          url: '/dashboard/datacalendar/' + currentYear + '/' + month,
          method: 'GET',
          beforeSend: function() {
              console.log("Loading get calendar...");
          },
          success: function(response) {
              let datas = response.data;
              document.getElementById('current-year').value = currentYear;
              document.getElementById('current-month').value = month;
              drawCalendarGrid('calGrid','calTitle',currentYear,month-1,datas,'compact');
              drawCalendarGrid('mCalGrid','mCalTitle',currentYear,month-1,datas,'detailed');
              console.log("Success get calendar:", response);
          },
          error: function(xhr) {
              console.error("Error get calendar:", xhr.responseText);
          }
      });
    }

    function drawCalendarGrid(hostId, titleId, year, month, datas, mode) {
      //JS months go from 0 to 11
      const grid=document.getElementById(hostId),
      title=document.getElementById(titleId);
      title.textContent=monthNameId(year,month);
      grid.innerHTML='';
      const first=new Date(year,month,1), nday=new Date(year,month+1,0).getDate(), ev=groupCalendarEvents(datas);

      // spacer agar mulai di weekday yang tepat (samakan tinggi baris)
      for(let i=0;i<first.getDay();i++){
        const s=document.createElement('div');
        s.style.minHeight = (mode==='detailed') ? '110px' : '56px';
        grid.appendChild(s);
      }

      for(let d=1; d<=nday; d++){
        const day=new Date(year,month,d), key=day.toDateString();
        const pack=ev[key]||{deadline:[],review:[]};

        const cell=document.createElement('div');
        cell.className='cal-cell '+(mode==='detailed'?'detailed':'compact');
        cell.dataset.day = String(d);

        const head=document.createElement('div');
        head.className='cal-date';
        head.textContent=d;
        cell.appendChild(head);

        if(mode==='compact') {
          const row=document.createElement('div');
          row.className='dot-row';
          const items=[...pack.deadline.map(doc=>({...doc,_t:'dead'})), ...pack.review.map(doc=>({...doc,_t:'rev'}))];
          const maxDots=4;
          const shown=items.slice(0,maxDots);
          const remain=Math.max(0,items.length-maxDots);

          shown.forEach(it=>{
            const b=document.createElement('span');
            b.className='dot '+(it._t==='dead'?'dot-dead':'dot-rev');
            b.addEventListener('mouseenter',(e)=> showTip(e.clientX,e.clientY,`<b>${it.document_no}</b><br>${it.document_name}`));
            b.addEventListener('mouseleave', hideTip);
            b.addEventListener('click',(e)=>{
              if(_tip && _tip.style.display==='block') hideTip(); else showTip(e.clientX,e.clientY,`<b>${it.document_no}</b><br>${it.document_name}`);
              e.stopPropagation();
            });
            row.appendChild(b);
          });

          if(remain>0){
            const more=document.createElement('span');
            more.className='dot-more';
            more.textContent='+'+remain;
            more.title='Lihat detail';
            more.addEventListener('click',()=>{
              _lastClickedDay=d;
              openCalendar();
              setTimeout(()=>{
                const mGrid=document.getElementById('mCalGrid');
                const target=[...mGrid.querySelectorAll('.cal-cell')].find(el=>el.dataset.day===String(_lastClickedDay));
                if(target){ target.scrollIntoView({block:'center'}); target.style.boxShadow='0 0 0 2px var(--indigo) inset'; setTimeout(()=>target.style.boxShadow='none',800); }
              },50);
            });
            row.appendChild(more);
          }

          // klik area kosong sel → buka modal & fokus ke tanggal
          cell.addEventListener('click',()=>{
            _lastClickedDay=d;
            openCalendar();
            setTimeout(()=>{
              const mGrid=document.getElementById('mCalGrid');
              const target=[...mGrid.querySelectorAll('.cal-cell')].find(el=>el.dataset.day===String(_lastClickedDay));
              if(target){ target.scrollIntoView({block:'center'}); target.style.boxShadow='0 0 0 2px var(--indigo) inset'; setTimeout(()=>target.style.boxShadow='none',800); }
            },50);
          });

          cell.appendChild(row);
        } else {
          // DETAIL (modal)
          title.textContent=longMonthNameId(year,month);
          const wrap=document.createElement('div');
          wrap.className='mt-1 space-y-1';
          const pill=(doc,isDead)=>`<span class="cal-pill" style="background:${isDead?'var(--pill-dead-bg)':'var(--pill-rev-bg)'};color:${isDead?'var(--pill-dead-fg)':'var(--pill-rev-fg)'}" title="${doc.document_no} — ${doc.document_name}">
            <span class="no">${doc.document_no}</span><span class="nm">${doc.document_name}</span></span>`;
          const limit=8;
          pack.deadline.slice(0,limit).forEach(dc=>wrap.insertAdjacentHTML('beforeend',pill(dc,true)));
          pack.review.slice(0,limit).forEach(dc=>wrap.insertAdjacentHTML('beforeend',pill(dc,false)));
          const more=(pack.deadline.length>limit?pack.deadline.length-limit:0)+(pack.review.length>limit?pack.review.length-limit:0);
          if(more>0) wrap.insertAdjacentHTML('beforeend',`<div class="text-11" style="color:#64748b">+${more} lainnya…</div>`);
          cell.appendChild(wrap);
        }

        grid.appendChild(cell);
      }
    }

    function groupCalendarEvents(docs) {
      const map={};
      docs.forEach(d=>{
        const review=new Date(d.baseDate);
        const k=review.toDateString();
        (map[k]=map[k]||{deadline:[],review:[]}).review.push(d);

        if(d.document_status !== 'APPROVED') {
          const dead=addDays(new Date(d.created_at), IDEAL_APPROVAL_DAYS);
          const k2=dead.toDateString();
          (map[k2]=map[k2]||{deadline:[],review:[]}).deadline.push(d);
        }
      });
      return map;
    }
    function showTip(x,y,html) {
      if(!_tip) return;
      _tip.innerHTML=html;
      _tip.style.left=(x+8)+'px';
      _tip.style.top=(y+8)+'px';
      _tip.style.display='block';
    }
    function hideTip(){ if(!_tip) return; _tip.style.display='none'; }

    function openCalendar() {
      modal.classList.add('open'); modal.setAttribute('aria-hidden','false');
      modal.setAttribute('tabindex','-1'); modal.style.outline='none'; modal.focus();
    };

    function shiftMonth(delta) {
      let currentYear = document.getElementById('current-year').value;
      let currentMonth = document.getElementById('current-month').value;
      let date = new Date(currentYear, currentMonth - 1 + delta, 1);
      newcalYear = date.getFullYear();
      newcalMonth = date.getMonth() + 1;
      renderCalendars(newcalYear, newcalMonth);
    };

    // Calendar buttons
    prevMonth.addEventListener('click', ()=> shiftMonth(-1));
    nextMonth.addEventListener('click', ()=> shiftMonth(+1));

    // Modal
    const modal=document.getElementById('calendarModal');
    document.getElementById('closeModal').addEventListener('click', ()=>{
      modal.classList.remove('open'); modal.setAttribute('aria-hidden','true');
    });
    modal.addEventListener('click', (e)=>{
      if(e.target===modal){ modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); }
    });
    document.getElementById('mPrev').addEventListener('click', ()=> shiftMonth(-1));
    document.getElementById('mNext').addEventListener('click', ()=> shiftMonth(+1));
    modal.addEventListener('keydown', (e)=>{
      if(e.key==='ArrowLeft') shiftMonth(-1);
      if(e.key==='ArrowRight') shiftMonth(+1);
    });

    function redirectPage(baseUrl, params = {}) {
      const url = new URL(baseUrl, window.location.origin);
      Object.keys(params).forEach(key => {
        url.searchParams.set(key, params[key]);
      });
      window.location.href = url.toString();
    }

    document.getElementById("mainCardNeedApproval").addEventListener("click", function () {
        redirectPage("document-upload", {
            viewtype: "required"
        });
    });

    document.getElementById("mainCardBooked").addEventListener("click", function () {
        redirectPage("document-upload", {
            viewtype: "create"
        });
    });

    document.getElementById("mainCardActive").addEventListener("click", function () {
        redirectPage("document-upload", {
            viewtype: "create"
        });
    });

    document.getElementById("mainCardObsolete").addEventListener("click", function () {
        redirectPage("document-master-list", {
            viewtype: "obsolete"
        });
    });

    document.getElementById("mainCardTotal").addEventListener("click", function () {
        redirectPage("document-master-list", {
            viewtype: "approve"
        });
    });

    document.getElementById("mainCardProc").addEventListener("click", function () {
        redirectPage("document-upload", {
            viewtype: "create"
        });
    });

    document.getElementById("mainCardWI").addEventListener("click", function () {
        redirectPage("document-upload", {
            viewtype: "create"
        });
    });

    document.getElementById("mainCardForm").addEventListener("click", function () {
        redirectPage("document-upload", {
            viewtype: "create"
        });
    });
  </script>
  <script>
    function getOutstandingDocument() {
      const lstdocument = document.getElementById('listdocument');
      let i = 1;
      var html = '';
      while (i <= 5) {
        let id = 1;
        let documentno = 'P-C01-01';
        let msg = 'waiting approval';
          html +='<li class="nav-item lh-1 me-4">'
							+ '<a class="dropdown-item" href="#">'
							+ '<span><span>Document No: ' + documentno +'</span></span>'
							+ '<span class="message">' + msg + '</span>'
							+ '</a>'
							+ '</li>';
          i++;
      }
      $('#listdocument').html(html);
    }
    const transaction_data = [
      { no:'PROD-001', name:'Prosedur Mixing A', dept:'PROD', type:'P',   uploadedAt:'2025-08-30', approvedAt:'2025-09-01', baseDate:'2024-09-05', owner:'Andi' },
      { no:'PROD-002', name:'WI Line 1 StartUp', dept:'PROD', type:'WI', uploadedAt:'2025-09-20', approvedAt:null,         baseDate:'2024-10-01', owner:'Andi' },
      { no:'PROD-003', name:'Checksheet L1',     dept:'PROD', type:'P',uploadedAt:'2025-09-05', approvedAt:'2025-09-06', baseDate:'2025-01-15', owner:'Sari' },
      { no:'PROD-004',    name:'Prosedur Sampling', dept:'QC',   type:'P',   uploadedAt:'2025-09-10', approvedAt:'2025-09-12', baseDate:'2024-10-02', owner:'Budi' },
      { no:'PROD-005',   name:'WI Caliper',        dept:'QC',   type:'WI',  uploadedAt:'2025-09-22', approvedAt:null,         baseDate:'2024-10-05', owner:'Budi' },
      { no:'PROD-006',  name:'Form Acc Quality',  dept:'QC',   type:'P',uploadedAt:'2025-07-18', approvedAt:'2025-07-19', baseDate:'2024-09-30', owner:'Rina' },
      { no:'PROD-007',   name:'Prosedur PM1',      dept:'MTC',  type:'P',   uploadedAt:'2025-08-01', approvedAt:'2025-08-03', baseDate:'2024-08-10', owner:'Galih' },
      { no:'PROD-008',  name:'WI Greasing',       dept:'MTC',  type:'WI',  uploadedAt:'2025-09-23', approvedAt:null,         baseDate:'2024-10-15', owner:'Galih' },
      { no:'PROD-009', name:'Form PM Checklist', dept:'MTC',  type:'P',uploadedAt:'2025-06-10', approvedAt:'2025-06-11', baseDate:'2024-06-12', owner:'Lia' },
      { no:'PROD-010',  name:'Prosedur Cuti',     dept:'HRGA', type:'P',   uploadedAt:'2025-05-02', approvedAt:'2025-05-03', baseDate:'2024-05-07', owner:'Nana' },
      { no:'PROD-011', name:'WI Rekap Absensi',  dept:'HRGA', type:'WI',  uploadedAt:'2025-09-18', approvedAt:'2025-09-19', baseDate:'2024-09-25', owner:'Nana' },
      { no:'PROD-012',name:'Form Lembur',       dept:'HRGA', type:'P',uploadedAt:'2025-09-21', approvedAt:null,         baseDate:'2024-10-20', owner:'Nana' },
      { no:'PROD-013',   name:'Prosedur Kas',      dept:'FIN',  type:'P',   uploadedAt:'2025-09-01', approvedAt:'2025-09-02', baseDate:'2024-09-10', owner:'Dian' },
      { no:'PROD-014',  name:'WI Petty Cash',     dept:'FIN',  type:'WI',  uploadedAt:'2025-09-24', approvedAt:null,         baseDate:'2024-11-01', owner:'Dian' },
      { no:'PROD-015', name:'Form Reimburse',    dept:'FIN',  type:'P',uploadedAt:'2025-08-29', approvedAt:'2025-08-30', baseDate:'2024-09-01', owner:'Dian' }
    ];
  </script>

@endsection
