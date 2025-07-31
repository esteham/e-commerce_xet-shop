<div class="container mt-4">
    <h1 class="mb-4">Datewise Sales Report</h1>
    <form id="dateFilterForm" class="row g-3 mb-4">
        <div class="col-md-3">
            <label for="fromDate" class="form-label">From Date:</label>
            <input type="date" class="form-control" id="fromDate" name="fromDate" required>
        </div>
        <div class="col-md-3">
            <label for="toDate" class="form-label">To Date:</label>
            <input type="date" class="form-control" id="toDate" name="toDate" required>
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">Generate Report</button>
            <button type="button" class="btn btn-secondary me-2" id="resetFilter">Reset</button>
            <button type="button" class="btn btn-success me-2" id="exportExcel" style="display:none;">Export Excel</button>
            <button type="button" class="btn btn-info" id="printReport" style="display:none;">Print Report</button>
        </div>
    </form>
    <div id="reportData"></div>
</div>

<script>
$(document).ready(function() {
    // Hide export/print buttons initially
    $('#exportExcel').hide();
    $('#printReport').hide();
    
    // Event handler for form submission
    $('#dateFilterForm').on('submit', function(e) {
        e.preventDefault();
        loadReportData();
    });

    // Reset button handler
    $('#resetFilter').on('click', function() {
        $('#fromDate').val('');
        $('#toDate').val('');
        $('#reportData').html('');
        $('#exportExcel').hide();
        $('#printReport').hide();
    });

    function loadReportData() {
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();

        if (!fromDate || !toDate) {
            $('#reportData').html('<div class="alert alert-warning">Please select both dates.</div>');
            $('#exportExcel').hide();
            $('#printReport').hide();
            return;
        }

        $.ajax({
            url: 'pages/ajax/fetch_datewise_sales.php',
            type: 'POST',
            data: { 
                fromDate: fromDate, 
                toDate: toDate 
            },
            beforeSend: function() {
                $('#reportData').html('<div class="text-center my-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
                $('#exportExcel').hide();
                $('#printReport').hide();
            },
            success: function(response) {
                $('#reportData').html(response);
                
                // Only show export/print buttons if we have a table with data
                if ($('#reportTable').length && $('#reportTable tbody tr').length > 0) {
                    $('#exportExcel').show();
                    $('#printReport').show();
                    initializeDataTable();
                } else {
                    $('#exportExcel').hide();
                    $('#printReport').hide();
                }
            },
            error: function(xhr, status, error) {
                $('#reportData').html('<div class="alert alert-danger">Error loading report: ' + error + '</div>');
                $('#exportExcel').hide();
                $('#printReport').hide();
                console.error("AJAX Error:", status, error);
            }
        });
    }

    function initializeDataTable() {
        if ($.fn.DataTable && $('#reportTable').length) {
            $('#reportTable').DataTable({
                dom: '<"top"lf>rt<"bottom"ip>',
                responsive: true,
                pageLength: 25,
                order: [0, 'asc'] // Order by first column (date) ascending
            });
        }
    }

    function exportToExcel() {
        const table = document.getElementById('reportTable');
        if (!table) {
            alert('No data available to export.');
            return;
        }

        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();
        const fileName = `Sales_Report_${fromDate}_to_${toDate}.xlsx`;
        
        const wb = XLSX.utils.table_to_book(table);
        XLSX.writeFile(wb, fileName);
    }

    function printReport() {
        const printContent = $('#reportData').html();
        const originalTitle = document.title;
        
        // Create a new window for printing
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>${originalTitle} - Print</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    @media print {
                        body { padding: 20px; }
                        .no-print { display: none !important; }
                        .print-header {
                            margin-bottom: 20px;
                            text-align: center;
                        }
                        table { width: 100%; }
                    }
                </style>
            </head>
            <body>
                <div class="print-header">
                    <h1>Datewise Sales Report</h1>
                    <p>From: ${$('#fromDate').val()} To: ${$('#toDate').val()}</p>
                </div>
                ${printContent}
                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                            window.close();
                        }, 200);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }

    // Event handlers for export and print
    $('#exportExcel').on('click', exportToExcel);
    $('#printReport').on('click', printReport);
});
</script>