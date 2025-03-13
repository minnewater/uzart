$(document).ready(function() {
    function loadLogs(page = 1) {
        let formData = {
            start_date: $("#start_date").val(),
            end_date: $("#end_date").val(),
            client: $("#client").val(),
            server_name: $("#server_name").val(),
            client_ip: $("#client_ip").val(),
            message: $("#message").val(),
            limit: $("#limit").val(),
            page: page
        };

        $.ajax({
            url: "/uzart/www/api/logs_api.php",
            type: "GET",
            data: formData,
            dataType: "json",
            success: function(response) {
                let tableBody = $("#logTable tbody");
                let paginationDiv = $("#pagination");
                tableBody.empty();
                paginationDiv.empty();

                if (response.logs.length > 0) {
                    $.each(response.logs, function(index, log) {
                        tableBody.append(`
                            <tr>
                                <td>${log.log_time}</td>
                                <td>${log.level}</td>
                                <td>${log.message}</td>
                                <td>${log.client}</td>
                                <td>${log.server_name}</td>
                                <td>${log.client_ip}</td>
                            </tr>
                        `);
                    });
                    for (let i = 1; i <= response.total_pages; i++) {
                        let activeClass = (i === response.current_page) ? 'active' : '';
                        paginationDiv.append(`<button class="page-btn ${activeClass}" data-page="${i}">${i}</button>`);
                    }
                } else {
                    tableBody.append('<tr><td colspan="6" style="text-align: center;">조회된 로그가 없습니다.</td></tr>');
                }
            },
            error: function() {
                alert("로그 데이터를 불러오는 중 오류가 발생했습니다.");
            }
        });
    }

    $("#logFilterForm").off('submit').on('submit', function(event) {
        event.preventDefault();
        loadLogs();
    });

    $(document).off('click', '.page-btn').on('click', '.page-btn', function() {
        let page = $(this).data("page");
        loadLogs(page);
    });

    loadLogs();
});
