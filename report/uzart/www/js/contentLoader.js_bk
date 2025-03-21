if (typeof window.contentLoaderInitialized === 'undefined') {
    window.contentLoaderInitialized = true;

    $(document).ready(function() {
        //console.log("contentLoader.js initialized - Singleton");

        $("#toggleSidebar").off('click').on('click', function() {
            $(".sidebar").toggleClass("collapsed");
        });

        function initializePageScripts(page) {
            if (page === 'reports') {
                loadReportsScript();
            } else if (page === 'clients') {
                loadClientsScript();
            } else if (page === 'logs') {
                loadLogsScript();
            } else if (page === 'settings') {
                loadSettingsScript();
            }
        }

        // 보고서 페이지 스크립트
        function loadReportsScript() {
            $.getJSON("/uzart/www/api/reports_api.php?action=load_clients", function(data) {
                if (data.error) {
                    alert("클라이언트 데이터 오류: " + data.error);
                    return;
                }
                $("#client").empty().append('<option value="">-- 선택 --</option>');
                $.each(data.clients, function(i, client) {
                    $("#client").append(new Option(client, client));
                });
            }).fail(function() {
                alert("클라이언트 데이터를 불러오는 중 오류 발생");
            });

            $("#searchReport").off('click').on('click', function() {
                let startDate = $("#start_date").val();
                let endDate = $("#end_date").val();
                let client = $("#client").val();
                let server = $("#server").val();

                if (!client) {
                    alert("클라이언트를 선택하세요.");
                    return;
                }

                let today = new Date().toISOString().split('T')[0];
                if (!startDate && !endDate) {
                    startDate = today;
                    endDate = today;
                } else if (!startDate) {
                    startDate = today;
                } else if (!endDate) {
                    endDate = today;
                }

                $.getJSON("/uzart/www/api/reports_api.php?action=load_report", {
                    start_date: startDate,
                    end_date: endDate,
                    client: client,
                    server: server
                }).done(function(data) {
                    let tableBody = $("#reportResults");
                    tableBody.empty();
                    if (data.error) {
                        alert("데이터 불러오기 오류: " + data.error);
                        return;
                    }
                    if (data.length === 0) {
                        tableBody.append('<tr><td colspan="5">조회된 데이터가 없습니다.</td></tr>');
                    } else {
                        $.each(data, function(i, row) {
                            tableBody.append(`
                                <tr>
                                    <td><input type="checkbox" name="reportSelect[]" value="${row.id}" data-client="${row.client}" data-server="${row.server_name}" data-created="${row.created_at}" data-check="${row.check_status}"></td>
                                    <td>${row.server_name}</td>
                                    <td>${row.client}</td>
                                    <td>${row.created_at}</td>
                                    <td>${row.check_status}</td>
                                </tr>
                            `);
                        });
                        $("input[name='reportSelect[]']").off('change').on('change', function() {
                            var anySelected = $("input[name='reportSelect[]']:checked").length > 0;
                            $("#checkReport, #viewReport, #downloadReport").prop("disabled", !anySelected);
                        });
                    }
                }).fail(function(jqXHR, textStatus) {
                    alert("보고서 데이터 오류: " + textStatus);
                });
            });

            $("#selectAll").off('click').on('click', function(e) {
                e.preventDefault();
                if ($("input[name='reportSelect[]']").length === $("input[name='reportSelect[]']:checked").length) {
                    $("input[name='reportSelect[]']").prop("checked", false);
                } else {
                    $("input[name='reportSelect[]']").prop("checked", true);
                }
                var anySelected = $("input[name='reportSelect[]']:checked").length > 0;
                $("#checkReport, #viewReport, #downloadReport").prop("disabled", !anySelected);
            });

            function getSelectedIds() {
                return $("input[name='reportSelect[]']:checked").map(function() {
                    return $(this).val();
                }).get().join(",");
            }

            function showAlert(message, type) {
                let alertBox = $("#alertMessage");
                alertBox.text(message).removeClass("success error").addClass(type).fadeIn();
                setTimeout(function() { alertBox.fadeOut(); }, 3000);
            }

            if (window.pdfjsLib) {
                pdfjsLib.GlobalWorkerOptions.workerSrc = '/uzart/www/js/pdf.worker.min.js';
            }
            let scale = 1.3, pdfDoc, currentPage, totalPages;

            function renderPage(pageNumber) {
                pdfDoc.getPage(pageNumber).then(function(page) {
                    let viewport = page.getViewport({ scale: scale });
                    let canvas = document.createElement("canvas");
                    let context = canvas.getContext("2d");
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;
                    page.render({ canvasContext: context, viewport: viewport }).promise.then(function() {
                        $("#pdfViewer").empty().append(canvas);
                        $("#pageInfo").text(pageNumber + " / " + totalPages);
                        $("#prevPage").prop("disabled", currentPage <= 1);
                        $("#nextPage").prop("disabled", currentPage >= totalPages);
                    });
                });
            }

            $("#viewReport").off('click').on('click', function() {
                var combinedIds = getSelectedIds();
                if (!combinedIds) {
                    alert("보고서를 선택해주세요.");
                    return;
                }
                var pdfUrl = "/uzart/www/api/view_report.php?download=2&ids=" + combinedIds;
                pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
                    pdfDoc = pdf;
                    totalPages = pdf.numPages;
                    currentPage = 1;
                    renderPage(currentPage);
                    $("#modalDownloadReport").attr("href", "/uzart/www/api/view_report.php?download=1&ids=" + combinedIds);
                    $("#pdfModal").fadeIn();

                    $("#nextPage").off('click').on('click', function(e) {
                        e.preventDefault();
                        if (currentPage < totalPages) {
                            currentPage++;
                            renderPage(currentPage);
                        }
                    });
                    $("#prevPage").off('click').on('click', function(e) {
                        e.preventDefault();
                        if (currentPage > 1) {
                            currentPage--;
                            renderPage(currentPage);
                        }
                    });
                }).catch(function(error) {
                    alert("PDF 로드 오류: " + error);
                });
            });

            $("#downloadReport").off('click').on('click', function() {
                var combinedIds = getSelectedIds();
                if (!combinedIds) {
                    alert("다운로드할 보고서를 선택해주세요.");
                    return;
                }
                window.location.href = '/uzart/www/api/view_report.php?download=1&ids=' + combinedIds;
            });

            $("#checkReport").off('click').on('click', function() {
                var combinedIds = getSelectedIds();
                if (!combinedIds) {
                    alert("보고서를 선택해주세요.");
                    return;
                }
                var pdfUrl = "/uzart/www/api/check_report.php?download=2&ids=" + combinedIds;
                pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
                    pdfDoc = pdf;
                    totalPages = pdf.numPages;
                    currentPage = 1;

                    function renderPage(pageNum) {
                        pdfDoc.getPage(pageNum).then(function(page) {
                            let viewport = page.getViewport({ scale: scale });
                            let canvas = document.createElement("canvas");
                            let context = canvas.getContext("2d");
                            canvas.width = viewport.width;
                            canvas.height = viewport.height;
                            page.render({ canvasContext: context, viewport: viewport }).promise.then(function() {
                                $("#pdfViewer").empty().append(canvas);
                                $("#pageInfo").text(pageNum + " / " + totalPages);
                                $("#prevPage").prop("disabled", currentPage <= 1);
                                $("#nextPage").prop("disabled", currentPage >= totalPages);

                                if (pageNum === totalPages) {
                                    let checkForm = `
                                        <div id="checkForm">
                                            <h3>점검 내역 입력</h3>
                                            <input type="text" id="net_err" placeholder="네트워크 오류" value="이상 없음"><br>
                                            <input type="text" id="tmp_log" placeholder="TMP 로그" value="이상 없음"><br>
                                            <input type="text" id="web_log" placeholder="WEB 로그" value="해당사항 없음"><br>
                                            <input type="text" id="was_log" placeholder="WAS 로그" value="해당사항 없음"><br>
                                            <input type="text" id="db_log" placeholder="DB 로그" value="해당사항 없음"><br>
                                            <input type="text" id="sys_log" placeholder="시스템 로그" value="이상 없음"><br>
                                            <textarea id="comments" placeholder="코멘트">- 특이사항 없음</textarea><br>
                                            <button id="saveCheckReport">저장</button>
                                        </div>`;
                                    $("#pdfViewer").append(checkForm);

                                    $("#saveCheckReport").off('click').on('click', function() {
                                        $.ajax({
                                            url: "/uzart/www/api/add_check.php",
                                            method: "POST",
                                            data: {
                                                ids: combinedIds,
                                                check_status: "true",
                                                net_err: $("#net_err").val(),
                                                tmp_log: $("#tmp_log").val(),
                                                web_log: $("#web_log").val(),
                                                was_log: $("#was_log").val(),
                                                db_log: $("#db_log").val(),
                                                sys_log: $("#sys_log").val(),
                                                comments: $("#comments").val()
                                            },
                                            dataType: "json",
                                            success: function(response) {
                                                if (response.success) {
                                                    showAlert("✅ 점검이 저장되었습니다.", "success");
                                                    $("#pdfModal").fadeOut();
                                                } else {
                                                    showAlert("❌ 저장 오류: " + response.error, "error");
                                                }
                                            },
                                            error: function() {
                                                alert("저장 중 오류 발생");
                                            }
                                        });
                                    });
                                }
                            });
                        });
                    }

                    renderPage(currentPage);
                    $("#pdfModal").fadeIn();

                    $("#nextPage").off('click').on('click', function(e) {
                        e.preventDefault();
                        if (currentPage < totalPages) {
                            currentPage++;
                            renderPage(currentPage);
                        }
                    });
                    $("#prevPage").off('click').on('click', function(e) {
                        e.preventDefault();
                        if (currentPage > 1) {
                            currentPage--;
                            renderPage(currentPage);
                        }
                    });
                }).catch(function(error) {
                    alert("PDF 로드 오류: " + error);
                });
            });

            $(".close-btn").off('click').on('click', function() {
                $("#pdfModal").fadeOut();
            });
            $(document).off('click').on('click', function(event) {
                if ($(event.target).is("#pdfModal")) {
                    $("#pdfModal").fadeOut();
                }
            });
        }

        // 고객 관리 페이지 스크립트
        function loadClientsScript() {
	    //console.log("loadClientsScript called");
            function loadClients(page = 1) {
                let formData = {
                    client: $("#client").val(),
                    server_name: $("#server_name").val(),
                    limit: $("#limit").val(),
                    page: page
                };

                $.ajax({
                    url: "/uzart/www/api/clients_api.php?action=load_clients",
                    type: "GET",
                    data: formData,
                    dataType: "json",
                    success: function(response) {
                        //console.log("loadClients response: ", response);
                        let tableBody = $("#clientTable tbody");
                        let paginationDiv = $("#pagination");
                        tableBody.empty();
                        paginationDiv.empty();

                        if (response.clients.length > 0) {
                            $.each(response.clients, function(index, client) {
                                let blurredKey = "•".repeat(client.key_length);
                                tableBody.append(`
                                    <tr>
                                        <td>${client.client}</td>
                                        <td>${client.server_name}</td>
                                        <td><span class="api-key blurred" data-client="${client.client}" data-server="${client.server_name}">${blurredKey}</span></td>
                                        <td><button class="download-btn" data-client="${client.client}" data-server="${client.server_name}">📥</button></td>
                                    </tr>
                                `);
                            });
                            //console.log("API Key elements added to table");

		            $(document).off('click', '.api-key').on('click', '.api-key', function() {
		                //console.log("API Key clicked");
		                let keyElement = $(this);
		                let client = keyElement.data("client");
		                let server = keyElement.data("server");
		                //console.log("Clicked element: ", keyElement);
		
		                if (keyElement.hasClass("blurred")) {
		                    //console.log("Fetching API Key for client: " + client + ", server: " + server);
		                    $.getJSON(`/uzart/www/api/clients_api.php?action=get_api_key&client=${encodeURIComponent(client)}&server=${encodeURIComponent(server)}`, function(data) {
		                        //console.log("AJAX response: ", data);
		                        if (data.success) {
		                            keyElement.text(data.api_key).removeClass("blurred");
		                        } else {
		                            showAlert("❌ API Key 조회 실패: " + data.error, "error");
		                        }
		                    }).fail(function(jqXHR, textStatus) {
		                        //console.log("AJAX failed: " + textStatus);
		                        showAlert("❌ API Key 조회 오류", "error");
		                    });
		                } else {
		                    let blurredKey = "•".repeat(keyElement.text().length);
		                    keyElement.text(blurredKey).addClass("blurred");
		                }
		            });


			    $(document).off('click', '.download-btn').on('click', '.download-btn', function() {
	                        //console.log("Download button clicked (from loadClients)");
	                        let client = $(this).data("client");
	                        let server = $(this).data("server");
	                        $("#downloadForm").remove();
	                        let form = $("<form>")
	                            .attr("id", "downloadForm")
	                            .attr("method", "GET")
	                            .attr("action", "/uzart/www/api/download_api_key.php");
	                        form.append($("<input>").attr("type", "hidden").attr("name", "client").val(client));
	                        form.append($("<input>").attr("type", "hidden").attr("name", "server").val(server));
	                        $("body").append(form);
	                        form.submit();
	                    });


                            for (let i = 1; i <= response.total_pages; i++) {
                                let activeClass = (i === response.current_page) ? 'active' : '';
                                paginationDiv.append(`<button class="page-btn ${activeClass}" data-page="${i}">${i}</button>`);
                            }
                        } else {
                            tableBody.append('<tr><td colspan="4">조회된 고객이 없습니다.</td></tr>');
                        }
                    },
                    error: function() {
                        alert("고객 데이터 오류");
                    }
                });
            }

            $("#clientFilterForm").off('submit').on('submit', function(event) {
                event.preventDefault();
                loadClients();
            });


            $(document).off('click', '.page-btn').on('click', '.page-btn', function() {
                let page = $(this).data("page");
                loadClients(page);
            });

            $("#addClientBtn").off('click').on('click', function() {
                $("#addClientModal").fadeIn();
            });

            $("#cancelModal").off('click').on('click', function() {
                $("#addClientModal").fadeOut();
            });

            $(document).off('click').on('click', function(event) {
                if ($(event.target).is("#addClientModal")) {
                    $("#addClientModal").fadeOut();
                }
            });

            $("#confirmAddClient").off('click').on('click', function() {
                let newClient = $("#newClient").val().trim();
                let newServerName = $("#newServerName").val().trim();

                if (newClient === "" || newServerName === "") {
                    showAlert("⚠️ 클라이언트와 서버 이름을 입력하세요.", "error");
                    return;
                }

                $.ajax({
                    url: "/uzart/www/api/add_client.php",
                    type: "POST",
                    data: { client: newClient, server_name: newServerName },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            showAlert("✅ 고객이 추가되었습니다.", "success");
                            $("#addClientModal").fadeOut();
                            loadClients();
                        } else {
                            showAlert("❌ 오류: " + response.error, "error");
                        }
                    },
                    error: function() {
                        showAlert("❌ 고객 추가 오류", "error");
                    }
                });
            });

            function showAlert(message, type) {
                let alertBox = $("#alertMessage");
                alertBox.text(message).removeClass("success error").addClass(type).fadeIn();
                setTimeout(function() { alertBox.fadeOut(); }, 3000);
            }

            //loadClients();
        }

        // 로그 관리 페이지 스크립트
        function loadLogsScript() {
            function loadLogs(page = 1) {
                let formData = {
                    start_date: $("#start_date").val(),
                    end_date: $("#end_date").val(),
                    client: $("#client").val(),
                    server_name: $("#server_name").val(),
                    client_ip: $("#client_ip").val(),
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
                            tableBody.append('<tr><td colspan="6">조회된 로그가 없습니다.</td></tr>');
                        }
                    },
                    error: function() {
                        alert("로그 데이터 오류");
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
        }

        // 설정 페이지 스크립트
        function loadSettingsScript() {
            function loadUsers() {
                $.getJSON("/uzart/www/api/settings_api.php?action=load_users", function(data) {
                    let tableBody = $("#userTable tbody");
                    tableBody.empty();
                    if (data.users && data.users.length > 0) {
                        $.each(data.users, function(i, user) {
                            tableBody.append(`
                                <tr>
                                    <td>${user.username}</td>
                                    <td>${user.name}</td>
                                    <td>${user.position}</td>
                                    <td>${user.office_phone}</td>
                                    <td>${user.mobile_phone}</td>
                                    <td>${user.email}</td>
                                    <td>${user.group_name}</td>
                                </tr>
                            `);
                        });
                    } else {
                        tableBody.append('<tr><td colspan="6">조회된 사용자가 없습니다.</td></tr>');
                    }
                }).fail(function() {
                    alert("사용자 데이터 오류");
                });
            }

            $("#viewUserBtn").off('click').on('click', function() {
                loadUsers();
            });

            $("#addUserBtn").off('click').on('click', function() {
                $("#addUserModal").fadeIn();
                $.getJSON("/uzart/www/api/settings_api.php?action=load_groups", function(data) {
                    let groupSelect = $("#newGroupId");
                    groupSelect.empty();
                    if (data.groups && data.groups.length > 0) {
                        $.each(data.groups, function(i, group) {
                            groupSelect.append(new Option(group.group_name, group.id));
                        });
                    }
                });
            });

            $("#cancelModal").off('click').on('click', function() {
                $("#addUserModal").fadeOut();
            });

            $(document).off('click').on('click', function(event) {
                if ($(event.target).is("#addUserModal")) {
                    $("#addUserModal").fadeOut();
                }
            });

            $("#confirmAddUser").off('click').on('click', function() {
                let username = $("#newUsername").val().trim();
                let password = $("#newPassword").val().trim();
                let group_id = $("#newGroupId").val();
                let name = $("#newName").val().trim();
                let position = $("#newPosition").val().trim();
                let office_phone = $("#newOffice").val().trim();
                let mobile_phone = $("#newPhone").val().trim();
                let email = $("#newEmail").val().trim();

                if (!username || !password || !group_id) {
                    showAlert("⚠️ 필수 항목(아이디, 패스워드, 그룹)을 입력하세요.", "error");
                    return;
                }

                $.ajax({
                    url: "/uzart/www/api/settings_api.php",
                    type: "POST",
                    data: {
                        username: username,
                        password: password,
                        group_id: group_id,
                        name: name,
                        position: position,
                        office_phone: office_phone,
                        mobile_phone: mobile_phone,
                        email: email
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            showAlert("✅ 사용자가 추가되었습니다.", "success");
                            $("#addUserModal").fadeOut();
                            loadUsers();
                        } else {
                            showAlert("❌ 오류: " + response.error, "error");
                        }
                    },
                    error: function() {
                        showAlert("❌ 사용자 추가 오류", "error");
                    }
                });
            });

            function showAlert(message, type) {
                let alertBox = $("#alertMessage");
                alertBox.text(message).removeClass("success error").addClass(type).fadeIn();
                setTimeout(function() { alertBox.fadeOut(); }, 3000);
            }
        }

        $('.menu-link').off('click').on('click', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            //console.log("Menu clicked: " + url);

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'html',
                success: function(data) {
                    //console.log("AJAX success for: " + url);
                    var html = $('<div></div>').html(data);
                    html.find('script').remove();
                    var newContent = html.find('.content').html();

                    if (newContent) {
                        $('.content').html(newContent);
                        history.pushState(null, '', url);
                        //console.log("Content updated for: " + url);
                        var page = url.match(/page=([^&]+)/)?.[1] || 'home';
                        initializePageScripts(page);
                    } else {
                        //console.log("No .content found, redirecting to: " + url);
                        window.location.href = url;
                    }
                },
                error: function(jqXHR, textStatus) {
                    //console.log("AJAX error for: " + url + " - " + textStatus);
                    alert('페이지 로드 실패');
                }
            });
        });

        $(".logout-btn").off('click').on('click', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            //console.log("Logout clicked: " + href);
            window.location.href = href;
        });
    });
} else {
    //console.log("contentLoader.js already initialized - Skipped");
}
