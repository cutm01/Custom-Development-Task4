<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Records</title>

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/2.3.4/css/dataTables.dataTables.min.css">

    <!-- Font Awesome for the delete icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            padding: 20px;
            background-color: #f4f7f6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color: 0.2s ease-in-out;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        .modal-title {
            font-size: 1.25rem;
            margin-bottom: 15px;
        }
        .modal-body {
            margin-bottom: 20px;
        }
        .modal-buttons button {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            margin: 0 10px;
        }
        .modal-confirm-btn {
            background-color: #dc3545;
            color: white;
        }
        .modal-cancel-btn {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>CRM Records</h1>

    <table id="recordsTable" class="display" style="width:100%">
        <thead>
        <tr>
            <th>Title</th>
            <th>Type</th>
            <th>Created At</th>
            <th>Project Time</th>
            <th style="width: 80px; text-align: center;">Actions</th>
        </tr>
        </thead>
        <tbody>
        <!-- Rows will be populated by DataTables JS -->
        </tbody>
    </table>
</div>

<!-- Custom Modal -->
<div id="confirmModal" class="modal-overlay">
    <div class="modal-content">
        <h2 class="modal-title">Confirm Deletion</h2>
        <p class="modal-body">Are you sure you want to delete this record?</p>
        <div class="modal-buttons">
            <button id="modalCancel" class="modal-cancel-btn">Cancel</button>
            <button id="modalConfirm" class="modal-confirm-btn">Delete</button>
        </div>
    </div>
</div>


<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables JS -->
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        let ticketName = null;
        try {
            // Access the URL of the page containing the iframe
            const parentUrl = window.parent.location.href;
            // Use a regular expression to find the number after "/tickets/update/"
            const match = parentUrl.match(/\/tickets\/update\/(\d+)/);
            if (match && match[1]) {
                ticketName = match[1];
            }
        } catch (e) {
            console.error("Could not access parent window URL due to cross-origin restrictions.", e);
            // Fallback: Check the iframe's own URL as a last resort
            const selfUrl = window.location.href;
            const selfMatch = selfUrl.match(/\/tickets\/update\/(\d+)/);
            if(selfMatch && selfMatch[1]){
                ticketName = selfMatch[1];
            }
        }

        // Initialize DataTables
        const table = $('#recordsTable').DataTable({
            "processing": true,
            "ajax": {
                "url": "task4.php",
                "dataSrc": "data",
                "data": function(d) {
                    d.ticket = ticketName;
                }
            },
            "columns": [
                { "data": "title" },
                { "data": "type.title" },
                { "data": "created" },
                {
                    "data": "customFields.project_time",
                    "title": "Project Time",
                    "render": function(data, type, row) {
                        // Safely access the nested array data
                        if (data && Array.isArray(data) && data.length > 0) {
                            return data[0]; // Return the first element of the array
                        }
                        return 'N/A'; // Provide a fallback value
                    },
                    "className": "dt-center"
                },
                {
                    "data": null,
                    "orderable": false,
                    "searchable": false,
                    "render": function(data, type, row) {
                        return `<button class="delete-btn" data-name="${row.name}" title="Delete Record">` +
                            `<i class="fas fa-trash-alt"></i>` +
                            `</button>`;
                    },
                    "className": "dt-center"
                }
            ]
        });

        setInterval(function() {
            table.ajax.reload(null, false); // user paging is not reset on reload
        }, 30000); // 30000 milliseconds = 30 seconds

        // Logic for the delete confirmation modal
        const confirmModal = $('#confirmModal');
        let recordNameToDelete = null;
        let rowToDelete = null;

        $('#recordsTable tbody').on('click', '.delete-btn', function() {
            recordNameToDelete = $(this).data('name');
            rowToDelete = $(this).parents('tr');
            const recordTitle = $(this).closest('tr').find('td:first').text();
            confirmModal.find('.modal-body').text(`Are you sure you want to delete the record: "${recordTitle}"?`);
            confirmModal.css('display', 'flex');
        });

        $('#modalCancel').on('click', function() {
            confirmModal.hide();
        });

        $('#modalConfirm').on('click', function() {
            if (recordNameToDelete) {
                $.ajax({
                    url: 'task4.php',
                    type: 'POST',
                    data: {
                        action: 'delete',
                        name: recordNameToDelete,},
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            table.row(rowToDelete).remove().draw();
                            console.log(response.message);
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred while deleting the record.');
                        console.error("AJAX Error:", status, error);
                    },
                    complete: function() {
                        confirmModal.hide();
                        recordNameToDelete = null;
                        rowToDelete = null;
                    }
                });
            }
        });
    });
</script>

</body>
</html>

